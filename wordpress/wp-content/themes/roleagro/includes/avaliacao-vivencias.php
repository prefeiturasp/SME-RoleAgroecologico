<?php

function criar_tabela_avaliacoes_vivencias() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'avaliacoes_vivencias';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        vivencia_id BIGINT UNSIGNED NOT NULL,
        tipo VARCHAR(30) NOT NULL,
        identificador VARCHAR(100) DEFAULT NULL,
        nome VARCHAR(255) DEFAULT NULL,
        respostas LONGTEXT NOT NULL,
        comentario TEXT DEFAULT NULL,
        data_avaliacao DATETIME NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME DEFAULT NULL,

        PRIMARY KEY  (id),
        KEY vivencia_id (vivencia_id),
        KEY tipo (tipo),
        KEY vivencia_tipo (vivencia_id, tipo),
        UNIQUE KEY avaliacao_unica (vivencia_id, tipo, identificador)
    ) {$charset_collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( $sql );
}

function verificar_tabela_avaliacoes_vivencias() {
    global $wpdb;

    $versao_atual = '1.0';
    $versao_instalada = get_option( 'versao_tabela_avaliacoes_vivencias' );

    if ( $versao_instalada === $versao_atual ) {
        return;
    }

    criar_tabela_avaliacoes_vivencias();

    $table_name = $wpdb->prefix . 'avaliacoes_vivencias';

    $tabela_existe = $wpdb->get_var(
        $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) )
    );

    if ( $tabela_existe === $table_name ) {
        update_option( 'versao_tabela_avaliacoes_vivencias', $versao_atual );
    }
}

add_action( 'admin_init', 'verificar_tabela_avaliacoes_vivencias' );

function get_perguntas_avaliacao_vivencia( $tipo = 'professor' ) {

    $perguntas = [];

    switch ( $tipo ) {

        case 'professor':

            $perguntas = [
                'organizacao' => [
                    'pergunta' => 'Como você avalia a organização da vivência?',
                    'tipo'     => 'nota',
                ],

                'acolhimento' => [
                    'pergunta' => 'Como você avalia o acolhimento durante a vivência?',
                    'tipo'     => 'nota',
                ],

                'conteudo' => [
                    'pergunta' => 'Como você avalia o conteúdo da vivência?',
                    'tipo'     => 'nota',
                ],

                'relevancia' => [
                    'pergunta' => 'Como você avalia a relevância da vivência para os alunos?',
                    'tipo'     => 'nota',
                ],

                'avaliacao_geral' => [
                    'pergunta' => 'Qual sua avaliação geral da vivência?',
                    'tipo'     => 'nota',
                ],
            ];

            break;

        case 'unidade':

            $perguntas = [
                'organizacao' => [
                    'pergunta' => 'Como você avalia a organização prévia da vivência, incluindo a comunicação e as orientações recebidas?',
                    'tipo'     => 'nota',
                ],

                'pontualidade' => [
                    'pergunta' => 'Como você avalia o cumprimento dos horários e dos combinados estabelecidos para a visita?',
                    'tipo'     => 'nota',
                ],

                'participacao' => [
                    'pergunta' => 'Como você avalia o interesse e a participação dos estudantes durante as atividades?',
                    'tipo'     => 'nota',
                ],

                'interacao' => [
                    'pergunta' => 'Como você avalia a interação e o respeito do grupo com os responsáveis, o espaço e as atividades da Unidade Produtiva?',
                    'tipo'     => 'nota',
                ],

                'avaliacao_geral' => [
                    'pergunta' => 'De modo geral, como você avalia a experiência de receber a unidade educacional para esta vivência?',
                    'tipo'     => 'nota',
                ],
            ];

            break;
    }

    return $perguntas;
}

function salvar_avaliacao_vivencia( $dados ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'avaliacoes_vivencias';

    /*
     * Dados principais da avaliação.
     */
    $vivencia_id = absint( $dados['vivencia_id'] ?? 0 );
    $tipo        = sanitize_key( $dados['tipo'] ?? '' );

    /*
     * Validação dos dados obrigatórios.
     */
    if ( ! $vivencia_id || ! $tipo ) {
        return new WP_Error(
            'dados_invalidos',
            'Os dados da avaliação são inválidos.'
        );
    }

    /*
     * Verifica se a vivência realmente existe.
     */
    $vivencia = get_post( $vivencia_id );

    if ( ! $vivencia ) {
        return new WP_Error(
            'vivencia_invalida',
            'Vivência não encontrada.'
        );
    }

    /*
     * A avaliação somente pode ser realizada
     * quando a vivência estiver com status "realizado".
     */
    $status = get_post_meta(
        $vivencia_id,
        'status_inscricao',
        true
    );

    if ( $status !== 'realizado' ) {
        return new WP_Error(
            'vivencia_nao_realizada',
            'Esta vivência ainda não pode ser avaliada.'
        );
    }

    /*
     * Validação das respostas.
     *
     * Importante:
     * 0 é uma nota válida.
     */
    $respostas = [];

    foreach ( $dados['respostas'] ?? [] as $pergunta => $nota ) {

        /*
         * Campo sem resposta.
         */
        if ( $nota === '' || $nota === null ) {
            continue;
        }

        /*
         * Aceita somente números inteiros.
         *
         * FILTER_VALIDATE_INT retorna:
         *
         * 0     -> nota válida
         * 1-5   -> nota válida
         * false -> valor inválido
         */
        $nota = filter_var(
            $nota,
            FILTER_VALIDATE_INT
        );

        /*
         * Usamos comparação estrita porque
         * a nota 0 NÃO pode ser considerada inválida.
         */
        if ( $nota === false ) {
            continue;
        }

        /*
         * Nossa escala aceita somente notas
         * entre 0 e 5.
         */
        if ( $nota < 0 || $nota > 5 ) {
            continue;
        }

        $respostas[
            sanitize_key( $pergunta )
        ] = $nota;
    }

    /*
     * Nenhuma resposta válida encontrada.
     */
    if ( empty( $respostas ) ) {
        return new WP_Error(
            'sem_respostas',
            'Nenhuma resposta válida foi informada.'
        );
    }

    /*
     * Data atual utilizando o timezone
     * configurado no WordPress.
     */
    $agora = current_time( 'mysql' );

    /*
     * Grava a avaliação.
     */
    $resultado = $wpdb->insert(
        $table_name,
        [
            'vivencia_id'    => $vivencia_id,
            'tipo'           => $tipo,

            'identificador'  => sanitize_text_field(
                $dados['identificador'] ?? ''
            ),

            'nome'           => sanitize_text_field(
                $dados['nome'] ?? ''
            ),

            'respostas'      => wp_json_encode(
                $respostas
            ),

            'comentario'     => sanitize_textarea_field(
                $dados['comentario'] ?? ''
            ),

            'data_avaliacao' => $agora,
            'created_at'     => $agora,
        ],
        [
            '%d', // vivencia_id
            '%s', // tipo
            '%s', // identificador
            '%s', // nome
            '%s', // respostas
            '%s', // comentario
            '%s', // data_avaliacao
            '%s', // created_at
        ]
    );

    /*
     * Erro ao inserir no banco.
     */
    if ( $resultado === false ) {
        return new WP_Error(
            'erro_banco',
            'Não foi possível salvar a avaliação.'
        );
    }

    /*
     * Retorna o ID da avaliação criada.
     */
    return (int) $wpdb->insert_id;
}

function processar_formulario_avaliacao_vivencia() {

    /*
     * Verifica se é o envio do formulário.
     */
    if (
        $_SERVER['REQUEST_METHOD'] !== 'POST' ||
        empty( $_POST['acao'] ) ||
        $_POST['acao'] !== 'avaliar_vivencia'
    ) {
        return null;
    }

    /*
     * Validação do nonce.
     */
    if (
        empty( $_POST['_wpnonce'] ) ||
        ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ),
            'avaliar_vivencia'
        )
    ) {
        return new WP_Error(
            'nonce_invalido',
            'Não foi possível validar o envio da avaliação.'
        );
    }

    /*
     * Identifica a vivência.
     */
    $vivencia_id = isset( $_POST['vivencia_id'] )
        ? absint( $_POST['vivencia_id'] )
        : 0;

    if ( ! $vivencia_id || ! get_post( $vivencia_id ) ) {
        return new WP_Error(
            'vivencia_invalida',
            'Vivência inválida.'
        );
    }

    /*
     * Identifica o tipo de avaliação.
     * Mantém professor como padrão para compatibilidade
     * com o formulário existente.
     */
    $tipo = isset( $_POST['tipo'] )
        ? sanitize_key( wp_unslash( $_POST['tipo'] ) )
        : 'professor';

    if ( ! in_array( $tipo, [ 'professor', 'unidade' ], true ) ) {
        return new WP_Error(
            'tipo_invalido',
            'Tipo de avaliação inválido.'
        );
    }

    /*
     * A vivência precisa estar realizada.
     */
    if (
        get_post_meta( $vivencia_id, 'status_inscricao', true )
        !== 'realizado'
    ) {
        return new WP_Error(
            'vivencia_indisponivel',
            'Esta vivência ainda não está disponível para avaliação.'
        );
    }

    /*
     * Recupera as perguntas conforme o tipo.
     */
    $perguntas = get_perguntas_avaliacao_vivencia( $tipo );

    if ( empty( $perguntas ) || ! is_array( $perguntas ) ) {
        return new WP_Error(
            'perguntas_invalidas',
            'Não existem perguntas disponíveis para esta avaliação.'
        );
    }

    /*
     * Recupera as respostas enviadas.
     */
    $respostas_enviadas = (
        isset( $_POST['respostas'] ) &&
        is_array( $_POST['respostas'] )
    )
        ? wp_unslash( $_POST['respostas'] )
        : [];

    $respostas = [];

    /*
     * Valida todas as perguntas.
     */
    foreach ( $perguntas as $chave => $configuracao ) {

        /*
         * A pergunta não foi respondida.
         */
        if ( ! array_key_exists( $chave, $respostas_enviadas ) ) {
            return new WP_Error(
                'resposta_obrigatoria',
                'Responda todas as perguntas da avaliação.'
            );
        }

        /*
         * Valida a nota.
         * A escala permitida é de 0 a 5.
         * A nota 0 é válida.
         */
        $nota = filter_var(
            $respostas_enviadas[ $chave ],
            FILTER_VALIDATE_INT
        );

        if (
            $nota === false ||
            $nota < 0 ||
            $nota > 5
        ) {
            return new WP_Error(
                'nota_invalida',
                'Uma das notas informadas é inválida.'
            );
        }

        $respostas[ $chave ] = $nota;
    }

    /*
     * Identifica quem está realizando a avaliação.
     */
    if ( $tipo === 'professor' ) {

        /*
         * Identificação do professor por RF ou CPF.
         */
        $documento = isset( $_POST['documento'] )
            ? sanitize_text_field( wp_unslash( $_POST['documento'] ) )
            : '';

        $participante = identificar_participante_avaliacao(
            $vivencia_id,
            $documento
        );

        if ( is_wp_error( $participante ) ) {
            return $participante;
        }

        /*
         * Verifica se o professor já respondeu.
         */
        if (
            professor_ja_avaliou_vivencia(
                $vivencia_id,
                $participante['rf']
            )
        ) {
            return new WP_Error(
                'avaliacao_duplicada',
                'Este educador já realizou a avaliação desta vivência.'
            );
        }

        $identificador = $participante['rf'];
        $nome          = $participante['nome'];

    } else {

        /*
         * Identificação da Unidade Produtiva.
         */
        $unidade_id = isset( $_POST['unidade_id'] )
            ? absint( $_POST['unidade_id'] )
            : 0;

        if (
            ! $unidade_id ||
            ! unidade_pertence_vivencia( $vivencia_id, $unidade_id )
        ) {
            return new WP_Error(
                'unidade_invalida',
                'Esta Unidade Produtiva não pertence à vivência informada.'
            );
        }

        /*
         * Validação do convite individual.
         */
        $token = isset( $_POST['token_avaliacao'] )
            ? sanitize_text_field(
                wp_unslash( $_POST['token_avaliacao'] )
            )
            : '';

        if (
            ! validar_convite_avaliacao_unidade(
                $vivencia_id,
                $unidade_id,
                $token
            )
        ) {
            return new WP_Error(
                'convite_invalido',
                'O link de avaliação é inválido ou não está autorizado.'
            );
        }

        /*
         * Identificação da unidade.
         */
        $identificador = (string) $unidade_id;
        $nome          = get_the_title( $unidade_id );

        /*
         * Verifica se a unidade já respondeu.
         */
        if (
            avaliacao_unidade_ja_realizada(
                $vivencia_id,
                $unidade_id
            )
        ) {
            return new WP_Error(
                'avaliacao_duplicada',
                'Esta Unidade Produtiva já realizou a avaliação desta vivência.'
            );
        }
    }

    /*
     * Comentário opcional.
     */
    $comentario = isset( $_POST['comentario'] )
        ? sanitize_textarea_field(
            wp_unslash( $_POST['comentario'] )
        )
        : '';

    /*
     * Salva a avaliação.
     */
    return salvar_avaliacao_vivencia([
        'vivencia_id'   => $vivencia_id,
        'tipo'          => $tipo,
        'identificador' => $identificador,
        'nome'          => $nome,
        'respostas'     => $respostas,
        'comentario'    => $comentario,
    ]);
}

function professor_ja_avaliou_vivencia( $vivencia_id, $rf ) {
    global $wpdb;

    $vivencia_id = absint( $vivencia_id );
    $rf          = sanitize_text_field( $rf );

    if ( ! $vivencia_id || ! $rf ) {
        return false;
    }

    $table_name = $wpdb->prefix . 'avaliacoes_vivencias';

    $avaliacao_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
            FROM {$table_name}
            WHERE vivencia_id = %d
              AND tipo = %s
              AND identificador = %s
            LIMIT 1",
            $vivencia_id,
            'professor',
            $rf
        )
    );

    return ! empty( $avaliacao_id );
}

function get_avaliacoes_vivencia( $vivencia_id, $tipo = null ) {
    global $wpdb;

    $vivencia_id = absint( $vivencia_id );

    if ( ! $vivencia_id ) {
        return [];
    }

    $table_name = $wpdb->prefix . 'avaliacoes_vivencias';

    if ( $tipo ) {

        $tipo = sanitize_key( $tipo );

        $sql = $wpdb->prepare(
            "SELECT *
            FROM {$table_name}
            WHERE vivencia_id = %d
              AND tipo = %s
            ORDER BY data_avaliacao DESC",
            $vivencia_id,
            $tipo
        );

    } else {

        $sql = $wpdb->prepare(
            "SELECT *
            FROM {$table_name}
            WHERE vivencia_id = %d
            ORDER BY data_avaliacao DESC",
            $vivencia_id
        );
    }

    return $wpdb->get_results(
        $sql,
        ARRAY_A
    );
}

add_action(
    'add_meta_boxes',
    'adicionar_metabox_avaliacoes_vivencia',
    10,
    2
);

function adicionar_metabox_avaliacoes_vivencia( $post_type, $post ) {

    /*
     * Post type real da vivência.
     */
    if ( $post_type !== 'post_inscricao' ) {
        return;
    }

    $status = get_post_meta(
        $post->ID,
        'status_inscricao',
        true
    );

    /*
     * Só mostra depois que a vivência
     * foi realizada.
     */
    if ( $status !== 'realizado' ) {
        return;
    }

    add_meta_box(
        'avaliacoes_vivencia',
        'Avaliações da Vivência',
        'render_metabox_avaliacoes_vivencia',
        $post_type,
        'normal',
        'default'
    );
}

function render_metabox_avaliacoes_vivencia( $post ) {

    ?>

    <div class="avaliacoes-vivencia-admin">

        <nav class="nav-tab-wrapper">
            <a
                href="#avaliacoes-professores"
                class="nav-tab nav-tab-active"
                data-tab="professores"
            >
                Professores
            </a>

            <a
                href="#avaliacoes-alunos"
                class="nav-tab"
                data-tab="alunos"
            >
                Alunos
            </a>

            <a
                href="#avaliacoes-unidade"
                class="nav-tab"
                data-tab="unidade"
            >
                Unidade
            </a>
        </nav>

        <br>

        <!-- PROFESSORES -->
        <div
            id="avaliacoes-professores"
            class="avaliacao-tab-content"
        >

            <?php
            render_avaliacoes_professores_vivencia(
                $post->ID
            );
            ?>

        </div>


        <!-- ALUNOS -->
        <div
            id="avaliacoes-alunos"
            class="avaliacao-tab-content"
            style="display: none;"
        >
            <?php render_avaliacoes_alunos_vivencia( (int) $post->ID ); ?>
        </div>


        <!-- UNIDADES PRODUTIVAS -->
        <div
            id="avaliacoes-unidade"
            class="avaliacao-tab-content"
            style="display: none;"
        >
            <?php
                render_avaliacoes_unidades_vivencia( $post->ID );
            ?>
        </div>

    </div>


    <script>
        jQuery(function ($) {

            $('.avaliacoes-vivencia-admin .nav-tab').on(
                'click',
                function (e) {

                    e.preventDefault();

                    const $container = $(this).closest(
                        '.avaliacoes-vivencia-admin'
                    );

                    const target = $(this).attr('href');


                    /*
                     * Remove aba ativa.
                     */
                    $container
                        .find('.nav-tab')
                        .removeClass('nav-tab-active');


                    /*
                     * Ativa aba clicada.
                     */
                    $(this).addClass(
                        'nav-tab-active'
                    );


                    /*
                     * Esconde conteúdos.
                     */
                    $container
                        .find('.avaliacao-tab-content')
                        .hide();


                    /*
                     * Exibe conteúdo selecionado.
                     */
                    $container
                        .find(target)
                        .show();

                }
            );

        });
    </script>

    <?php
}

function render_avaliacoes_professores_vivencia( $vivencia_id ) {

    /*
     * Recupera as avaliações dos professores.
     */
    $avaliacoes = get_avaliacoes_vivencia(
        $vivencia_id,
        'professor'
    );

    /*
     * Recupera as perguntas.
     */
    $perguntas = get_perguntas_avaliacao_vivencia(
        'professor'
    );

    if ( empty( $avaliacoes ) ) {
        echo '<p>Nenhuma avaliação de professor foi realizada até o momento.</p>';
        return;
    }

    ?>

    <div class="avaliacoes-professores-vivencia">

        <p>
            <strong>
                <?php echo esc_html( count( $avaliacoes ) ); ?>
            </strong>

            <?php
            echo count( $avaliacoes ) === 1
                ? 'avaliação recebida.'
                : 'avaliações recebidas.';
            ?>
        </p>

        <?php foreach ( $avaliacoes as $avaliacao ) : ?>

            <?php

            /*
             * Recupera as respostas armazenadas em JSON.
             */
            $respostas = json_decode(
                $avaliacao['respostas'],
                true
            );

            if ( ! is_array( $respostas ) ) {
                $respostas = [];
            }

            /*
             * Calcula a média das notas.
             */
            $notas = [];

            foreach ( $perguntas as $chave => $configuracao ) {

                if (
                    array_key_exists( $chave, $respostas ) &&
                    is_numeric( $respostas[ $chave ] )
                ) {
                    $notas[] = (float) $respostas[ $chave ];
                }
            }

            $media = count( $notas ) > 0
                ? array_sum( $notas ) / count( $notas )
                : null;

            ?>

            <div
                style="
                    border: 1px solid #dcdcde;
                    background: #fff;
                    padding: 16px;
                    margin-bottom: 16px;
                    border-radius: 4px;
                "
            >

                <!-- CABEÇALHO -->

                <div
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        margin-bottom: 12px;
                    "
                >

                    <div>

                        <h5 style="margin: 0 0 5px;">
                            <?php
                            echo esc_html(
                                $avaliacao['nome']
                            );
                            ?>
                        </h5>

                        <?php if ( ! empty( $avaliacao['identificador'] ) ) : ?>

                            <small style="color: #646970;">
                                RF/CPF:
                                <?php
                                echo esc_html(
                                    $avaliacao['identificador']
                                );
                                ?>
                            </small>

                        <?php endif; ?>

                    </div>

                    <span
                        style="
                            color: #008a20;
                            font-weight: 600;
                        "
                    >
                        ✓ Avaliada
                    </span>

                </div>

                <!-- DATA DA AVALIAÇÃO -->

                <?php if ( ! empty( $avaliacao['data_avaliacao'] ) ) : ?>

                    <p>
                        <strong>Data da avaliação:</strong>

                        <?php
                        echo esc_html(
                            mysql2date(
                                'd/m/Y H:i',
                                $avaliacao['data_avaliacao']
                            )
                        );
                        ?>
                    </p>

                <?php endif; ?>

                <!-- PERGUNTAS E NOTAS -->

                <table class="widefat striped">

                    <thead>
                        <tr>
                            <th>Pergunta</th>
                            <th style="width: 90px; text-align: center;">
                                Nota
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ( $perguntas as $chave => $configuracao ) : ?>

                            <tr>

                                <td>
                                    <?php
                                    echo esc_html(
                                        $configuracao['pergunta']
                                    );
                                    ?>
                                </td>

                                <td style="text-align: center;">

                                    <?php if ( array_key_exists( $chave, $respostas ) ) : ?>

                                        <strong>
                                            <?php
                                            echo esc_html(
                                                $respostas[ $chave ]
                                            );
                                            ?>/5
                                        </strong>

                                    <?php else : ?>

                                        —

                                    <?php endif; ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <!-- MÉDIA DA AVALIAÇÃO -->

                <?php if ( $media !== null ) : ?>

                    <div
                        style="
                            margin-top: 12px;
                            padding: 12px;
                            background: #f0f6fc;
                            border-left: 4px solid #2271b1;
                            border-radius: 3px;
                        "
                    >

                        <strong>Média da avaliação:</strong>

                        <span
                            style="
                                font-size: 16px;
                                font-weight: 600;
                            "
                        >
                            <?php
                            echo esc_html(
                                number_format_i18n( $media, 1 )
                            );
                            ?> / 5
                        </span>

                    </div>

                <?php endif; ?>

                <!-- COMENTÁRIOS -->

                <?php if ( ! empty( $avaliacao['comentario'] ) ) : ?>

                    <div style="margin-top: 16px;">

                        <strong>Comentários adicionais:</strong>

                        <p>
                            <?php
                            echo nl2br(
                                esc_html(
                                    $avaliacao['comentario']
                                )
                            );
                            ?>
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

    <?php
}

function get_participantes_avaliacao_vivencia( $vivencia_id ) {

    $vivencia_id = absint( $vivencia_id );

    if ( ! $vivencia_id ) {
        return [];
    }

    $participantes = [];

    /*
     * Fontes de participantes da vivência.
     */
    $fontes = [
        'dados_educadores',
        'dados_acompanhantes',
    ];

    foreach ( $fontes as $meta_key ) {

        $dados = get_post_meta(
            $vivencia_id,
            $meta_key,
            true
        );

        if ( ! is_array( $dados ) ) {
            continue;
        }

        foreach ( $dados as $participante ) {

            if ( ! is_array( $participante ) ) {
                continue;
            }

            $identificador = preg_replace(
                '/\D/',
                '',
                (string) ( $participante['rf'] ?? '' )
            );

            $nome = sanitize_text_field(
                $participante['nome'] ?? ''
            );

            if ( ! $identificador || ! $nome ) {
                continue;
            }

            /*
             * Evita duplicidade caso o mesmo participante
             * esteja nos dois postmetas.
             */
            if ( isset( $participantes[ $identificador ] ) ) {
                continue;
            }

            $participantes[ $identificador ] = [
                'rf'     => $identificador,
                'nome'   => $nome,
                'tipo'   => sanitize_text_field(
                    $participante['tipo'] ?? 'Educador'
                ),
                'origem' => $meta_key,
            ];
        }
    }

    return array_values( $participantes );
}

function identificar_participante_avaliacao( $vivencia_id, $documento ) {
    $documento = preg_replace( '/\D/', '', (string) $documento );

    if ( $documento === '' ) {
        return new WP_Error( 'documento_invalido', 'Informe seu RF ou CPF.' );
    }

    $participantes = get_participantes_avaliacao_vivencia( $vivencia_id );

    foreach ( $participantes as $participante ) {
        if ( $documento === $participante['rf'] ) {
            return $participante;
        }
    }

    return new WP_Error(
        'participante_nao_encontrado',
        'Não encontramos seu cadastro entre os participantes desta vivência.'
    );
}

function get_unidades_produtivas_vivencia( $vivencia_id ) {

    $roteiro_id = absint( get_post_meta( $vivencia_id, 'id_roteiro_inscricao', true ) );

    if ( ! $roteiro_id ) {
        return [];
    }

    $unidades = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
    
    if ( ! is_array( $unidades ) ) {
        return [];
    }

    return array_values( array_unique( array_filter( array_map( 'absint', $unidades ) ) ) );
}

function unidade_pertence_vivencia( $vivencia_id, $unidade_id ) {

    $unidades = get_unidades_produtivas_vivencia( $vivencia_id );

    return in_array( absint( $unidade_id ), $unidades, true );
}

function gerar_token_avaliacao_unidade( $vivencia_id, $unidade_id ) {

    $vivencia_id = absint( $vivencia_id );
    $unidade_id  = absint( $unidade_id );

    if (
        ! $vivencia_id ||
        ! unidade_pertence_vivencia( $vivencia_id, $unidade_id )
    ) {
        return false;
    }

    $tokens = get_post_meta(
        $vivencia_id,
        'tokens_avaliacao_unidades',
        true
    );

    if ( ! is_array( $tokens ) ) {
        $tokens = [];
    }

    /*
     * Reutiliza o token existente.
     * Isso evita invalidar um link já enviado por e-mail.
     */
    if (
        ! empty( $tokens[ $unidade_id ] ) &&
        is_string( $tokens[ $unidade_id ] )
    ) {
        return $tokens[ $unidade_id ];
    }

    /*
     * Gera um token aleatório de 64 caracteres.
     */
    $token = bin2hex( random_bytes( 32 ) );

    $tokens[ $unidade_id ] = $token;

    update_post_meta(
        $vivencia_id,
        'tokens_avaliacao_unidades',
        $tokens
    );

    return $token;
}

function validar_convite_avaliacao_unidade( $vivencia_id, $unidade_id, $token ) {

    $vivencia_id = absint( $vivencia_id );
    $unidade_id  = absint( $unidade_id );

    if (
        ! $vivencia_id ||
        ! $unidade_id ||
        ! is_string( $token ) ||
        $token === ''
    ) {
        return false;
    }

    /*
     * Confirma que a unidade pertence à vivência.
     */
    if ( ! unidade_pertence_vivencia( $vivencia_id, $unidade_id ) ) {
        return false;
    }

    /*
     * Recupera os tokens armazenados na inscrição.
     */
    $tokens = get_post_meta(
        $vivencia_id,
        'tokens_avaliacao_unidades',
        true
    );

    if (
        ! is_array( $tokens ) ||
        empty( $tokens[ $unidade_id ] ) ||
        ! is_string( $tokens[ $unidade_id ] )
    ) {
        return false;
    }

    /*
     * Compara os tokens de maneira segura.
     */
    return hash_equals(
        $tokens[ $unidade_id ],
        $token
    );
}

function avaliacao_unidade_ja_realizada( $vivencia_id, $unidade_id ) {

    global $wpdb;

    $tabela = $wpdb->prefix . 'avaliacoes_vivencias';

    $avaliacao_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM {$tabela}
             WHERE vivencia_id = %d
               AND tipo = %s
               AND identificador = %s
             LIMIT 1",
            absint( $vivencia_id ),
            'unidade',
            (string) absint( $unidade_id )
        )
    );

    return ! empty( $avaliacao_id );
}

function get_link_avaliacao_unidade( $vivencia_id, $unidade_id ) {

    $vivencia_id = absint( $vivencia_id );
    $unidade_id  = absint( $unidade_id );

    $token = gerar_token_avaliacao_unidade(
        $vivencia_id,
        $unidade_id
    );

    if ( ! $token ) {
        return false;
    }

    return add_query_arg(
        [
            'vivencia' => $vivencia_id,
            'tipo'     => 'unidade',
            'unidade'  => $unidade_id,
            'token'    => $token,
        ],
        home_url( '/avaliar-vivencia/' )
    );
}

function render_avaliacoes_unidades_vivencia( $vivencia_id ) {

    /*
     * Recupera as unidades vinculadas à vivência.
     */
    $unidades = get_unidades_produtivas_vivencia( $vivencia_id );    

    if ( empty( $unidades ) ) {
        echo '<p>Nenhuma Unidade Produtiva vinculada a esta vivência.</p>';
        return;
    }

    /*
     * Recupera as avaliações das unidades.
     */
    $avaliacoes = get_avaliacoes_vivencia(
        $vivencia_id,
        'unidade'
    );   

    /*
     * Organiza as avaliações pelo ID da unidade.
     */
    $avaliacoes_por_unidade = [];

    foreach ( $avaliacoes as $avaliacao ) {
        $avaliacoes_por_unidade[ (int) $avaliacao['identificador'] ] = $avaliacao;
    }

    /*
     * Recupera as perguntas.
     */
    $perguntas = get_perguntas_avaliacao_vivencia( 'unidade' );

    ?>

    <div class="avaliacoes-unidades-vivencia">

        <?php foreach ( $unidades as $unidade_id ) : ?>

            <?php

            $nome_unidade = get_the_title( $unidade_id );

            $avaliacao = $avaliacoes_por_unidade[ $unidade_id ] ?? null;

            ?>

            <div
                style="
                    border: 1px solid #dcdcde;
                    background: #fff;
                    padding: 16px;
                    margin-bottom: 16px;
                    border-radius: 4px;
                "
            >

                <div
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 12px;
                        margin-bottom: 12px;
                    "
                >

                    <h5 style="margin: 0;">
                        <?php echo esc_html( $nome_unidade ); ?>
                    </h5>

                    <?php if ( $avaliacao ) : ?>

                        <span
                            style="
                                color: #008a20;
                                font-weight: 600;
                            "
                        >
                            ✓ Avaliada
                        </span>

                    <?php else : ?>

                        <span
                            style="
                                color: #996800;
                                font-weight: 600;
                            "
                        >
                            Aguardando avaliação
                        </span>

                    <?php endif; ?>

                </div>

                <?php if ( ! $avaliacao ) : ?>

                    <p style="margin-bottom: 0;">
                        Esta Unidade Produtiva ainda não respondeu à avaliação.
                    </p>

                <?php else : ?>

                    <?php

                    /*
                     * Recupera as respostas armazenadas em JSON.
                     */
                    $respostas = json_decode(
                        $avaliacao['respostas'],
                        true
                    );

                    if ( ! is_array( $respostas ) ) {
                        $respostas = [];
                    }

                    /*
                    * Calcula a média das notas respondidas.
                    */
                    $notas = [];

                    foreach ( $perguntas as $chave => $configuracao ) {

                        if (
                            array_key_exists( $chave, $respostas ) &&
                            is_numeric( $respostas[ $chave ] )
                        ) {
                            $notas[] = (float) $respostas[ $chave ];
                        }
                    }

                    $media = count( $notas ) > 0
                        ? array_sum( $notas ) / count( $notas )
                        : null;

                    ?>

                    <p>
                        <strong>Data da avaliação:</strong>
                        <?php
                        echo esc_html(
                            mysql2date(
                                'd/m/Y H:i',
                                $avaliacao['data_avaliacao']
                            )
                        );
                        ?>
                    </p>

                    <table class="widefat striped">

                        <thead>
                            <tr>
                                <th>Pergunta</th>
                                <th style="width: 90px; text-align: center;">
                                    Nota
                                </th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ( $perguntas as $chave => $configuracao ) : ?>

                                <tr>

                                    <td>
                                        <?php echo esc_html( $configuracao['pergunta'] ); ?>
                                    </td>

                                    <td style="text-align: center;">

                                        <?php if ( array_key_exists( $chave, $respostas ) ) : ?>

                                            <strong>
                                                <?php echo esc_html( $respostas[ $chave ] ); ?>/5
                                            </strong>

                                        <?php else : ?>

                                            —

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                    <?php if ( $media !== null ) : ?>

                        <div
                            style="
                                margin-top: 12px;
                                padding: 12px;
                                background: #f0f6fc;
                                border-left: 4px solid #2271b1;
                                border-radius: 3px;
                            "
                        >
                            <strong>Média da avaliação:</strong>

                            <span style="font-size: 16px; font-weight: 600;">
                                <?php
                                echo esc_html(
                                    number_format_i18n( $media, 1 )
                                );
                                ?> / 5
                            </span>
                        </div>

                    <?php endif; ?>

                    <?php if ( ! empty( $avaliacao['comentario'] ) ) : ?>

                        <div style="margin-top: 16px;">

                            <strong>Comentários adicionais:</strong>

                            <p>
                                <?php
                                echo nl2br(
                                    esc_html( $avaliacao['comentario'] )
                                );
                                ?>
                            </p>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

    <?php
}

/**
 * Recupera os alunos vinculados a uma vivência.
 *
 * @param int  $vivencia_id
 * @param bool $somente_presentes
 *
 * @return array
 */
function get_alunos_vivencia(
    int $vivencia_id,
    bool $somente_presentes = false
): array {

    $turmas = get_post_meta(
        $vivencia_id,
        'dados_turmas',
        true
    );

    if ( ! is_array( $turmas ) ) {
        return [];
    }

    $alunos = [];

    foreach ( $turmas as $turma ) {

        if (
            ! is_array( $turma ) ||
            empty( $turma['alunosTurma'] ) ||
            ! is_array( $turma['alunosTurma'] )
        ) {
            continue;
        }

        $id_turma = absint( $turma['idTurma'] ?? 0 );

        $nome_turma = sanitize_text_field(
            $turma['nomeTurma'] ?? ''
        );

        foreach ( $turma['alunosTurma'] as $aluno ) {

            if ( ! is_array( $aluno ) ) {
                continue;
            }

            $codigo_aluno = absint(
                $aluno['codigoAluno'] ?? 0
            );

            if ( ! $codigo_aluno ) {
                continue;
            }

            $presenca = $aluno['confirmacaoPresenca'] ?? false;

            $presenca_confirmada = in_array(
                $presenca,
                [ true, 1, '1' ],
                true
            );

            if ( $somente_presentes && ! $presenca_confirmada ) {
                continue;
            }

            $nome = sanitize_text_field(
                $aluno['nomeSocialAluno']
                    ?: ( $aluno['nomeAluno'] ?? '' )
            );

            $alunos[ $codigo_aluno ] = [
                'codigoAluno'        => $codigo_aluno,
                'nomeAluno'          => $nome,
                'numeroAlunoChamada' => sanitize_text_field(
                    $aluno['numeroAlunoChamada'] ?? ''
                ),
                'idTurma'            => $id_turma,
                'nomeTurma'          => $nome_turma,
                'presencaConfirmada' => $presenca_confirmada,
            ];
        }
    }

    return array_values( $alunos );
}

/**
 * Busca um aluno pelo código dentro da vivência.
 *
 * Retorna os dados do aluno ou null.
 */
function get_aluno_vivencia(
    int $vivencia_id,
    int $codigo_aluno
): ?array {

    if ( ! $vivencia_id || ! $codigo_aluno ) {
        return null;
    }

    $alunos = get_alunos_vivencia( $vivencia_id );

    foreach ( $alunos as $aluno ) {

        if (
            (int) $aluno['codigoAluno'] === $codigo_aluno
        ) {
            return $aluno;
        }
    }

    return null;
}

/**
 * Verifica se um aluno já respondeu à avaliação.
 */
function avaliacao_aluno_ja_realizada(
    int $vivencia_id,
    int $codigo_aluno
): bool {

    global $wpdb;

    if ( ! $vivencia_id || ! $codigo_aluno ) {
        return false;
    }

    $tabela = $wpdb->prefix . 'avaliacoes_vivencias';

    $existe = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
             FROM {$tabela}
             WHERE vivencia_id = %d
               AND tipo = %s
               AND identificador = %s
             LIMIT 1",
            $vivencia_id,
            'aluno',
            (string) $codigo_aluno
        )
    );

    return ! empty( $existe );
}

/**
 * Recupera os alunos com informação
 * sobre a avaliação realizada.
 */
function get_alunos_status_avaliacao(
    int $vivencia_id,
    bool $somente_presentes = false
): array {

    $alunos = get_alunos_vivencia(
        $vivencia_id,
        $somente_presentes
    );

    $avaliacoes = get_avaliacoes_vivencia(
        $vivencia_id,
        'aluno'
    );

    $respondidos = [];

    foreach ( $avaliacoes as $avaliacao ) {

        $codigo = (string) $avaliacao['identificador'];

        $respondidos[ $codigo ] = true;
    }

    foreach ( $alunos as &$aluno ) {

        $codigo = (string) $aluno['codigoAluno'];

        $aluno['respondeu'] = isset(
            $respondidos[ $codigo ]
        );
    }

    unset( $aluno );

    return $alunos;
}

/**
 * Retorna a URL de avaliação para um tipo específico.
 */
function get_link_avaliacao_vivencia(
    int $vivencia_id,
    string $tipo = ''
): string {

    $args = [
        'vivencia' => $vivencia_id,
    ];

    if ( in_array( $tipo, [ 'professor', 'aluno' ], true ) ) {
        $args['tipo'] = $tipo;
    }

    return add_query_arg(
        $args,
        home_url( '/avaliar-vivencia/' )
    );
}

function get_perguntas_avaliacao_aluno_vivencia(): array {
    $sim_nao = [ 'sim' => 'Sim', 'nao' => 'Não' ];
    $aprendizado = [ 'muitas'=>'Aprendi muitas coisas novas', 'algumas'=>'Aprendi algumas coisas novas', 'poucas'=>'Aprendi poucas coisas novas', 'nenhuma'=>'Não aprendi coisas novas' ];
    return [
        'conhecimento_producao' => [ 'pergunta'=>'Antes do Rolê você já sabia que existiam espaços de produção de alimentos dentro da cidade de São Paulo?', 'tipo'=>'opcoes', 'opcoes'=>$sim_nao ],
        'contato_agricultor' => [ 'pergunta'=>'Antes do Rolê você já tinha conversado com alguém que produz alimentos, como um agricultor ou uma agricultora?', 'tipo'=>'opcoes', 'opcoes'=>$sim_nao ],
        'experiencia' => [ 'pergunta'=>'De 1 (muito ruim) a 5 (muito boa), que nota você daria para sua experiência no Rolê Agroecológico hoje?', 'tipo'=>'nota' ],
        'mais_gostou' => [ 'pergunta'=>'O que você mais gostou no Rolê Agroecológico de hoje?', 'tipo'=>'texto', 'obrigatoria'=>true ],
        'transporte' => [ 'pergunta'=>'De 1 (muito ruim) a 5 (muito bom), que nota você daria para o transporte utilizado para deslocamento até o Rolê Agroecológico hoje?', 'tipo'=>'nota' ],
        'reflexao_alimentos' => [ 'pergunta'=>'O Rolê fez você pensar sobre de onde vêm os alimentos, quem planta e como eles chegam até você?', 'tipo'=>'opcoes', 'opcoes'=>$sim_nao ],
        'experimentou_alimento' => [ 'pergunta'=>'No Rolê, você experimentou algum alimento que nunca tinha experimentado ou que não costuma comer no dia a dia?', 'tipo'=>'opcoes', 'opcoes'=>$sim_nao ],
        'aprendizado_natureza' => [ 'pergunta'=>'Você acha que aprendeu coisas novas sobre a natureza e os espaços verdes da cidade?', 'tipo'=>'opcoes', 'opcoes'=>$aprendizado ],
        'aprendizado_agricultura' => [ 'pergunta'=>'Você acha que aprendeu coisas novas sobre os alimentos, a agricultura e a produção de alimentos?', 'tipo'=>'opcoes', 'opcoes'=>$aprendizado ],
        'interesse_horta' => [ 'pergunta'=>'Depois do Rolê, você ficou com vontade de ajudar a criar ou melhorar uma horta na sua escola?', 'tipo'=>'opcoes', 'opcoes'=>[ 'muita'=>'Fiquei com muita vontade', 'alguma'=>'Fiquei com alguma vontade', 'pouca'=>'Fiquei com pouca vontade', 'nenhuma'=>'Não fiquei com vontade' ] ],
        'recomendacao' => [ 'pergunta'=>'Você recomendaria o Rolê Agroecológico para outro estudante?', 'tipo'=>'opcoes', 'opcoes'=>[ 'sim'=>'Sim', 'nao'=>'Não', 'nao_sei'=>'Não sei' ] ],
        'sugestao' => [ 'pergunta'=>'Se quiser, deixe uma sugestão ou comentário sobre o seu dia:', 'tipo'=>'texto', 'obrigatoria'=>false ],
    ];
}

function processar_avaliacao_aluno_vivencia( int $vivencia_id, int $codigo_aluno ) {
    global $wpdb;

    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'sem_login', 'É necessário entrar no sistema.' );
    }
    if ( ! isset( $_POST['_wpnonce'] ) || ! is_string( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'avaliar_aluno_vivencia_' . $vivencia_id . '_' . $codigo_aluno ) ) {
        return new WP_Error( 'nonce_invalido', 'A sessão do formulário expirou. Recarregue a página.' );
    }
    if ( get_post_meta( $vivencia_id, 'status_inscricao', true ) !== 'realizado' ) {
        return new WP_Error( 'status_invalido', 'Esta vivência não está disponível para avaliação.' );
    }
    $aluno = get_aluno_vivencia( $vivencia_id, $codigo_aluno );
    if ( ! $aluno ) {
        return new WP_Error( 'aluno_invalido', 'O estudante não pertence à vivência.' );
    }
    if ( avaliacao_aluno_ja_realizada( $vivencia_id, $codigo_aluno ) ) {
        return new WP_Error( 'ja_respondeu', 'Este estudante já respondeu à avaliação.' );
    }
    $enviadas = isset( $_POST['respostas'] ) && is_array( $_POST['respostas'] ) ? wp_unslash( $_POST['respostas'] ) : [];
    $respostas = [];
    foreach ( get_perguntas_avaliacao_aluno_vivencia() as $chave => $config ) {
        $valor = $enviadas[ $chave ] ?? '';
        if ( ! is_scalar( $valor ) ) {
            return new WP_Error( 'resposta_invalida', 'Uma das respostas é inválida.' );
        }
        $valor = trim( (string) $valor );
        $obrigatoria = $config['obrigatoria'] ?? true;
        if ( $valor === '' ) {
            if ( $obrigatoria ) {
                return new WP_Error( 'resposta_obrigatoria', 'Responda todas as perguntas obrigatórias.' );
            }
            $respostas[ $chave ] = '';
            continue;
        }
        if ( $config['tipo'] === 'nota' ) {
            $nota = filter_var( $valor, FILTER_VALIDATE_INT );
            if ( $nota === false || $nota < 1 || $nota > 5 ) {
                return new WP_Error( 'nota_invalida', 'As notas devem estar entre 1 e 5.' );
            }
            $respostas[ $chave ] = $nota;
        } elseif ( $config['tipo'] === 'opcoes' ) {
            if ( ! array_key_exists( $valor, $config['opcoes'] ) ) {
                return new WP_Error( 'opcao_invalida', 'Uma das alternativas escolhidas é inválida.' );
            }
            $respostas[ $chave ] = $valor;
        } else {
            $texto = sanitize_textarea_field( $valor );
            if ( mb_strlen( $texto ) > 1000 ) {
                return new WP_Error( 'texto_longo', 'Cada resposta aberta deve ter até 1000 caracteres.' );
            }
            $respostas[ $chave ] = $texto;
        }
    }

    $agora = current_time( 'mysql' );
    $tabela = $wpdb->prefix . 'avaliacoes_vivencias';
    $salvo = $wpdb->insert( $tabela, [
        'vivencia_id' => $vivencia_id,
        'tipo' => 'aluno',
        'identificador' => (string) $codigo_aluno,
        'nome' => $aluno['nomeAluno'],
        'respostas' => wp_json_encode( $respostas, JSON_UNESCAPED_UNICODE ),
        'comentario' => '',
        'data_avaliacao' => $agora,
        'created_at' => $agora,
    ], [ '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ] );

    if ( $salvo === false ) {
        if ( avaliacao_aluno_ja_realizada( $vivencia_id, $codigo_aluno ) ) {
            return new WP_Error( 'ja_respondeu', 'Este estudante já respondeu à avaliação.' );
        }
        return new WP_Error( 'erro_banco', 'Não foi possível salvar a avaliação.' );
    }
    return (int) $wpdb->insert_id;
}


function render_avaliacoes_alunos_vivencia( int $vivencia_id ): void {
    $avaliacoes = get_avaliacoes_vivencia( $vivencia_id, 'aluno' );
    $perguntas = get_perguntas_avaliacao_aluno_vivencia();
    $alunos = get_alunos_vivencia( $vivencia_id );
    $nomes = [];
    foreach ( $alunos as $aluno ) {
        $nomes[ (string) $aluno['codigoAluno'] ] = $aluno['nomeAluno'];
    }

    $totais = [ 'experiencia' => [], 'transporte' => [], 'geral' => [] ];
    $distribuicoes = [];
    $linhas = [];

    foreach ( $avaliacoes as $avaliacao ) {
        $respostas = json_decode( (string) ( $avaliacao['respostas'] ?? '' ), true );
        if ( ! is_array( $respostas ) ) { $respostas = []; }
        $notas = [];
        foreach ( [ 'experiencia', 'transporte' ] as $chave ) {
            $valor = $respostas[ $chave ] ?? null;
            if ( ( is_int( $valor ) || ( is_string( $valor ) && ctype_digit( $valor ) ) ) && (int) $valor >= 1 && (int) $valor <= 5 ) {
                $notas[ $chave ] = (int) $valor;
                $totais[ $chave ][] = (int) $valor;
            }
        }
        if ( count( $notas ) === 2 ) {
            $totais['geral'][] = ( $notas['experiencia'] + $notas['transporte'] ) / 2;
        }
        foreach ( $perguntas as $chave => $config ) {
            if ( ( $config['tipo'] ?? '' ) !== 'opcoes' ) { continue; }
            $valor = $respostas[ $chave ] ?? null;
            if ( is_string( $valor ) && isset( $config['opcoes'][ $valor ] ) ) {
                $distribuicoes[ $chave ][ $valor ] = ( $distribuicoes[ $chave ][ $valor ] ?? 0 ) + 1;
            }
        }
        $linhas[] = [ 'avaliacao' => $avaliacao, 'respostas' => $respostas, 'notas' => $notas ];
    }

    $media = static function ( array $valores ): string {
        return $valores ? number_format_i18n( array_sum( $valores ) / count( $valores ), 2 ) : '—';
    };

    ?>

    <h3>Resumo das avaliações dos estudantes</h3>

    <p><strong><?php echo esc_html( count( $avaliacoes ) ); ?></strong> avaliações recebidas de <strong><?php echo esc_html( count( $alunos ) ); ?></strong> estudantes cadastrados.</p>
    
    <table class="widefat striped" style="margin-bottom:22px">
        <thead><tr><th>Indicador</th><th>Média (1 a 5)</th><th>Respostas consideradas</th></tr></thead>
        <tbody>
            <tr><td>Experiência no Rolê</td><td><strong><?php echo esc_html( $media( $totais['experiencia'] ) ); ?></strong></td><td><?php echo esc_html( count( $totais['experiencia'] ) ); ?></td></tr>
            <tr><td>Transporte</td><td><strong><?php echo esc_html( $media( $totais['transporte'] ) ); ?></strong></td><td><?php echo esc_html( count( $totais['transporte'] ) ); ?></td></tr>
            <tr><td><strong>Média geral (experiência + transporte)</strong></td><td><strong><?php echo esc_html( $media( $totais['geral'] ) ); ?></strong></td><td><?php echo esc_html( count( $totais['geral'] ) ); ?></td></tr>
        </tbody>
    </table>

    <?php if ( ! $avaliacoes ) : ?>
        <p>Nenhuma avaliação de estudante foi realizada até o momento.</p>
        <?php return; ?>
    <?php endif; ?>

    <h3>Distribuição das respostas de múltipla escolha</h3>

    <?php foreach ( $perguntas as $chave => $config ) : ?>
        <?php if ( ( $config['tipo'] ?? '' ) !== 'opcoes' ) { continue; } ?>
        <?php $total = array_sum( $distribuicoes[ $chave ] ?? [] ); ?>
        <p><strong><?php echo esc_html( $config['pergunta'] ); ?></strong></p>
        <table class="widefat striped" style="margin-bottom:18px">
            <thead><tr><th>Alternativa</th><th>Quantidade</th><th>Percentual</th></tr></thead><tbody>
            <?php foreach ( $config['opcoes'] as $valor => $rotulo ) : ?>
                <?php $quantidade = $distribuicoes[ $chave ][ $valor ] ?? 0; ?>
                <tr><td><?php echo esc_html( $rotulo ); ?></td><td><?php echo esc_html( $quantidade ); ?></td><td><?php echo esc_html( $total ? number_format_i18n( $quantidade * 100 / $total, 1 ) . '%' : '—' ); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <h3>Respostas individuais</h3>

    <div style="overflow-x:auto">
        <table class="widefat striped">
            <thead><tr><th>Estudante</th><th>Experiência</th><th>Transporte</th><th>Média individual</th><th>Respostas abertas</th><th>Data</th></tr></thead>
            <tbody>
            <?php foreach ( $linhas as $linha ) : ?>
                <?php
                $a = $linha['avaliacao']; $r = $linha['respostas']; $n = $linha['notas'];
                $nome = $nomes[ (string) ( $a['identificador'] ?? '' ) ] ?? ( $a['nome'] ?? 'Estudante não encontrado' );
                $media_individual = count( $n ) === 2 ? $media( array_values( $n ) ) : '—';
                ?>
                <tr>
                    <td><?php echo esc_html( $nome ); ?></td>
                    <td><?php echo esc_html( $n['experiencia'] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $n['transporte'] ?? '—' ); ?></td>
                    <td><?php echo esc_html( $media_individual ); ?></td>
                    <td>
                        <strong>O que mais gostou:</strong> <?php echo nl2br( esc_html( (string) ( $r['mais_gostou'] ?? '' ) ) ); ?><br>
                        <strong>Sugestão:</strong> <?php echo nl2br( esc_html( (string) ( $r['sugestao'] ?? '' ) ) ); ?>
                    </td>
                    <td><?php echo esc_html( $a['data_avaliacao'] ?? '' ); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}