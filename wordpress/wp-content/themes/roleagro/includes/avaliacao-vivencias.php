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
            sanitize_text_field(
                wp_unslash( $_POST['_wpnonce'] )
            ),
            'avaliar_vivencia'
        )
    ) {
        return new WP_Error(
            'nonce_invalido',
            'Não foi possível validar o envio da avaliação.'
        );
    }


    /*
     * Vivência.
     */
    $vivencia_id = isset( $_POST['vivencia_id'] )
        ? absint( $_POST['vivencia_id'] )
        : 0;

    if ( ! $vivencia_id ) {
        return new WP_Error(
            'vivencia_invalida',
            'Vivência inválida.'
        );
    }


    /*
     * Perguntas válidas para professores.
     */
    $perguntas = get_perguntas_avaliacao_vivencia(
        'professor'
    );


    /*
     * Respostas enviadas.
     */
    $respostas_enviadas = isset( $_POST['respostas'] )
        && is_array( $_POST['respostas'] )
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
        if (
            ! array_key_exists(
                $chave,
                $respostas_enviadas
            )
        ) {
            return new WP_Error(
                'resposta_obrigatoria',
                'Responda todas as perguntas da avaliação.'
            );
        }


        /*
         * Valida a nota.
         */
        $nota = filter_var(
            $respostas_enviadas[ $chave ],
            FILTER_VALIDATE_INT
        );


        /*
         * A escala permitida é de 0 a 5.
         *
         * Importante:
         * 0 é uma nota válida.
         */
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
    * Identifica o participante pelo RF ou CPF informado.
    */
    $documento = isset( $_POST['documento'] )
        ? sanitize_text_field( wp_unslash( $_POST['documento'] ) )
        : '';

    $participante = identificar_participante_avaliacao( $vivencia_id, $documento );

    if ( is_wp_error( $participante ) ) {
        return $participante;
    }

    /*
     * Verifica se esse professor já realizou
     * uma avaliação para esta vivência.
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
        'tipo'          => 'professor',
        'identificador' => $participante['rf'],
        'nome'          => $participante['nome'],
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

            <p>
                As avaliações dos alunos serão exibidas aqui.
            </p>

        </div>


        <!-- UNIDADE -->
        <div
            id="avaliacoes-unidade"
            class="avaliacao-tab-content"
            style="display: none;"
        >

            <p>
                A avaliação da unidade será exibida aqui.
            </p>

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

    $avaliacoes = get_avaliacoes_vivencia(
        $vivencia_id,
        'professor'
    );

    $perguntas = get_perguntas_avaliacao_vivencia(
        'professor'
    );

    if ( empty( $avaliacoes ) ) {
        ?>

        <p>
            Nenhuma avaliação de professor foi realizada até o momento.
        </p>

        <?php
        return;
    }

    ?>

    <p>
        <strong>
            <?php echo count( $avaliacoes ); ?>
        </strong>

        <?php
        echo count( $avaliacoes ) === 1
            ? 'avaliação recebida.'
            : 'avaliações recebidas.';
        ?>
    </p>


    <table class="widefat striped">

        <thead>

            <tr>

                <th>
                    Professor
                </th>

                <?php foreach ( $perguntas as $pergunta ) : ?>

                    <th>
                        <?php
                        echo esc_html(
                            $pergunta['pergunta']
                        );
                        ?>
                    </th>

                <?php endforeach; ?>

                <th>
                    Média
                </th>

                <th>
                    Data
                </th>

            </tr>

        </thead>


        <tbody>

            <?php foreach ( $avaliacoes as $avaliacao ) : ?>

                <?php

                $respostas = json_decode(
                    $avaliacao['respostas'],
                    true
                );

                if ( ! is_array( $respostas ) ) {
                    $respostas = [];
                }


                /*
                 * Calcula média da avaliação.
                 */
                $notas = [];

                foreach ( $respostas as $nota ) {

                    if ( is_numeric( $nota ) ) {
                        $notas[] = (int) $nota;
                    }
                }

                $media = ! empty( $notas )
                    ? array_sum( $notas ) / count( $notas )
                    : null;

                ?>


                <tr>

                    <!-- PROFESSOR -->

                    <td>

                        <strong>
                            <?php
                            echo esc_html(
                                $avaliacao['nome']
                            );
                            ?>
                        </strong>


                        <?php if ( ! empty( $avaliacao['identificador'] ) ) : ?>

                            <br>

                            <small>

                                RF:

                                <?php
                                echo esc_html(
                                    $avaliacao['identificador']
                                );
                                ?>

                            </small>

                        <?php endif; ?>

                    </td>


                    <!-- RESPOSTAS -->

                    <?php foreach ( $perguntas as $chave => $pergunta ) : ?>

                        <td>

                            <?php

                            if (
                                array_key_exists(
                                    $chave,
                                    $respostas
                                )
                            ) {

                                echo esc_html(
                                    $respostas[ $chave ]
                                );

                            } else {

                                echo '—';

                            }

                            ?>

                        </td>

                    <?php endforeach; ?>


                    <!-- MÉDIA -->

                    <td>

                        <?php

                        if ( $media !== null ) {

                            echo esc_html(
                                number_format(
                                    $media,
                                    1,
                                    ',',
                                    '.'
                                )
                            );

                        } else {

                            echo '—';

                        }

                        ?>

                    </td>


                    <!-- DATA -->

                    <td>

                        <?php

                        if ( ! empty( $avaliacao['data_avaliacao'] ) ) {

                            echo esc_html(
                                date_i18n(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $avaliacao['data_avaliacao']
                                    )
                                )
                            );

                        } else {

                            echo '—';

                        }

                        ?>

                    </td>

                </tr>


                <!-- COMENTÁRIO -->

                <?php if ( ! empty( $avaliacao['comentario'] ) ) : ?>

                    <tr>

                        <td
                            colspan="<?php
                                echo esc_attr(
                                    count( $perguntas ) + 3
                                );
                            ?>"
                        >

                            <strong>
                                Comentário:
                            </strong>

                            <?php
                            echo nl2br(
                                esc_html(
                                    $avaliacao['comentario']
                                )
                            );
                            ?>

                        </td>

                    </tr>

                <?php endif; ?>


            <?php endforeach; ?>

        </tbody>

    </table>

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