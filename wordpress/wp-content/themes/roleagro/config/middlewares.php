<?php

function can_realizar_agendamentos() {
    return current_user_can( 'realizar_agendamentos' );
}

function can_solicitar_cancelamento( int $id_inscricao ) {

    $status_permitidos = ['novo', 'inscricao-confirmada', 'aguardando-autorizacoes'];
    $status_inscricao = get_post_meta( $id_inscricao, 'status_inscricao', true );

    return in_array( $status_inscricao, $status_permitidos );
}

/**
 * Valida a horta da UE autenticada com base no último sorteio salvo.
 * Retorna true ou WP_Error para que a mesma regra seja usada na página e no endpoint.
 */
function valida_situacao_horta_ue( $roteiro_id ) {
    global $wpdb;

    $permitidas = get_field( 'situacao_horta', $roteiro_id );
    if ( empty( $permitidas ) ) {
        return true;
    }

    $lotacao = get_user_meta( get_current_user_id(), 'unidade_locacao', true );
    $cie = trim( (string) ( $lotacao['codUnidade'] ?? '' ) );
    if ( $cie === '' ) {
        return new WP_Error( 'sem_ue', 'Seu usuário não está vinculado a uma unidade educacional.', ['status' => 403] );
    }

    $horta = $wpdb->get_var( $wpdb->prepare(
        "SELECT unidade.horta
         FROM {$wpdb->prefix}sorteio_unidades AS unidade
         WHERE unidade.sorteio_id = (
             SELECT id FROM {$wpdb->prefix}sorteios
             ORDER BY data_criacao DESC, id DESC
             LIMIT 1
         )
             AND TRIM(LEADING '0' FROM unidade.cie) = %s
         LIMIT 1",
        ltrim( $cie, '0' )
    ) );

    if ( $wpdb->last_error ) {
        return new WP_Error( 'consulta_horta_indisponivel', 'Não foi possível consultar a situação da horta da sua UE. Tente novamente.', ['status' => 503] );
    }

    $situacao = str_replace( '-', '_', sanitize_title( (string) $horta ) );
    if ( !in_array( $situacao, ['sim', 'nao', 'nao_informado'], true ) ) {
        $situacao = 'nao_informado';
    }

    if ( !in_array( $situacao, (array) $permitidas, true ) ) {

        return new WP_Error(
            'situacao_horta',
            'A situação da horta da sua unidade educacional não atende aos critérios de inscrição deste roteiro.',
            ['status' => 403]
        );
    }

    return true;
}

/** Retorna status, motivo e mensagem para a disponibilidade de inscrição. */
function tem_disponibilidade_agendamento( $roteiro_id = 0 ) {

    $usuario_id = get_current_user_id();
    $usuario_lotacao = get_user_meta( $usuario_id, 'unidade_locacao', true );

    if ( !isset( $usuario_lotacao['codUnidade'] ) || empty( $usuario_lotacao['codUnidade'] ) ) {
        return [
            'status' => false,
            'motivo' => 'sem_ue',
            'mensagem' => 'Seu usuário não está vinculado a uma unidade educacional.'
        ];
    }

    $horta = valida_situacao_horta_ue( $roteiro_id ?: get_the_ID() );
    if ( is_wp_error( $horta ) ) {
        return ['status' => false, 'motivo' => $horta->get_error_code(), 'mensagem' => $horta->get_error_message()];
    }

    if ( !tem_disponibilidade_agendamento_dre() ) {
        return [
            'status' => false,
            'motivo' => 'restricao_dre',
            'mensagem' => 'Não há disponibilidade de agendamento para sua DRE.'
        ];
    }

    $ano = date( 'Y' );
    $qtd_agendamentos_permitidos = intval( get_field( 'quantidade_agendamentos_permitidos', 'options' ) );
    $inicio_periodo = $ano . get_field( 'inicio_ciclo_agendamentos', 'options' );
    $fim_periodo    = $ano . get_field( 'fim_ciclo_agendamentos', 'options' );

    $query = new WP_Query([
        'post_type'      => 'post_inscricao',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            'relation' => 'AND',
            [
                'key'   => 'codigo_eol_ue',
                'value' => $usuario_lotacao['codUnidade'],
            ],
            [
                'key'   => 'status_inscricao',
                'compare' => '!=',
                'value' => 'cancelado',
            ],
            [
                'key'     => 'data_reservada_para_o_roteiro',
                'value'   => [$inicio_periodo, $fim_periodo],
                'compare' => 'BETWEEN',
                'type'    => 'NUMERIC',
            ],
        ],
    ]);

    if ( $query->found_posts >= $qtd_agendamentos_permitidos ) {
        return [
            'status' => false,
            'motivo' => 'limite_ue',
            'mensagem' => sprintf(
                'Sua unidade escolar já atingiu o limite de %d agendamento(s) permitido(s) para este ciclo.',
                $qtd_agendamentos_permitidos
            )
        ];
    }

    return ['status' => true, 'motivo' => null, 'mensagem' => ''];
}

/**
 * Verifica se o usuário atual está vinculado a uma UE. 
*/
function verifica_usuario_logado_tem_ue() {

    $usuario_id = get_current_user_id();
    $dados_usuario = get_user_meta( $usuario_id, 'unidade_locacao', true );
    
    if ( !$usuario_id || !isset( $dados_usuario['codUnidade'] ) ) {
        return false;
    }

    return true;
}

/**
 * Verifica se o usuário pode se inscrever em uma visita quando o sorteio estiver ativo.
 */

function usuario_possui_unidade_em_sorteio(
    array $sorteios_ids,
    array $status_permitidos
) {
    global $wpdb;

    if (!is_user_logged_in()) {
        return false;
    }

    $usuario_id = get_current_user_id();

    $unidade_lotacao = get_user_meta(
        $usuario_id,
        'unidade_locacao',
        true
    );

    if (
        !is_array($unidade_lotacao) ||
        empty($unidade_lotacao['codUnidade'])
    ) {
        return false;
    }

    $cie = trim(
        (string) $unidade_lotacao['codUnidade']
    );

    if ($cie === '') {
        return false;
    }

    /*
    * Normaliza o CIE para permitir comparação
    * independentemente dos zeros à esquerda.
    */
    $cie_normalizado = ltrim(
        $cie,
        '0'
    );

    if ($cie_normalizado === '') {
        $cie_normalizado = '0';
    }

    $sorteios_ids = array_values(
        array_filter(
            array_map(
                'absint',
                $sorteios_ids
            )
        )
    );

    $status_permitidos = array_values(
        array_filter(
            array_map(
                'sanitize_key',
                $status_permitidos
            )
        )
    );

    if (
        empty($sorteios_ids) ||
        empty($status_permitidos)
    ) {
        return false;
    }

    $table_unidades =
        $wpdb->prefix . 'sorteio_unidades';

    $placeholders_sorteios = implode(
        ', ',
        array_fill(
            0,
            count($sorteios_ids),
            '%d'
        )
    );

    $placeholders_status = implode(
        ', ',
        array_fill(
            0,
            count($status_permitidos),
            '%s'
        )
    );

    $sql = "
        SELECT id
        FROM {$table_unidades}
        WHERE TRIM(LEADING '0' FROM cie) = %s
        AND sorteio_id IN ({$placeholders_sorteios})
        AND status IN ({$placeholders_status})
        LIMIT 1
    ";

    $parametros = array_merge(
        [$cie_normalizado],
        $sorteios_ids,
        $status_permitidos
    );

    $unidade_id = $wpdb->get_var(
        $wpdb->prepare(
            $sql,
            ...$parametros
        )
    );

    return $unidade_id !== null;
}

function usuario_pode_inscrever_por_sorteio()
{
    $restringir = get_field(
        'restringir_inscricao_sorteio',
        'option'
    );

    /*
     * Se a restrição estiver desativada,
     * não existe bloqueio por sorteio.
     */
    if (!$restringir) {
        return true;
    }

    $sorteios_ids = get_field(
        'sorteios_permitidos',
        'option'
    );

    $status_permitidos = get_field(
        'status_sorteio_permitidos',
        'option'
    );

    $sorteios_ids = (array) $sorteios_ids;

    $status_permitidos = (array) $status_permitidos;

    /*
     * Restrição habilitada, mas configuração
     * incompleta: não libera.
     */
    if (
        empty($sorteios_ids) ||
        empty($status_permitidos)
    ) {
        return false;
    }

    return usuario_possui_unidade_em_sorteio(
        $sorteios_ids,
        $status_permitidos
    );
}

function tem_perfil_administrador() {
    return current_user_can( 'acessar_painel_adm' );
}

/**
 * Impede que um agendamento seja visualizado por usuários que não pertencem a mesma UE. 
*/
function valida_usuario_edicao_agendamento() {

    if ( !is_page( 'administrar-role' ) && !is_page( 'edita-agendamento' ) ) {
        return;
    }

    $usuario_id = get_current_user_id();
    $dados_usuario = get_user_meta( $usuario_id, 'unidade_locacao', true );
    
    if ( !$usuario_id || !isset( $dados_usuario['codUnidade'] ) ) {
        redirect_to_404();
    }

    $agendamento_id = intval( $_GET['iid'] );
    $codigo_unidade = get_post_meta( $agendamento_id, 'codigo_eol_ue', true );

    if ( $dados_usuario['codUnidade'] !== $codigo_unidade ) {
        redirect_to_404();
    }

    return;
}
add_action( 'template_redirect', 'valida_usuario_edicao_agendamento' );

/**
 * Força o redirecionamento do usuário para a página 404.
*/
function redirect_to_404() {

    global $wp_query;
    $wp_query->set_404();
    status_header( 404 );
    nocache_headers();
    include( get_query_template( '404' ) );

    exit;
}

function normaliza_data_ciclo_dre( $valor ) {
    if ( preg_match( '/^\d{4}$/', (string) $valor ) ) {
        $valor = obter_data_com_timezone( 'Y', 'America/Sao_Paulo' ) . $valor;
    }
    foreach ( ['Ymd', 'Y-m-d', 'd/m/Y'] as $formato ) {
        $data = DateTimeImmutable::createFromFormat( '!' . $formato, (string) $valor, wp_timezone() );
        if ( $data && $data->format( $formato ) === (string) $valor ) {
            return $data->format( 'Y-m-d' );
        }
    }

    return false;
}

/**
 * Valida a cota da DRE contando inscrições criadas no intervalo configurado.
 * As configurações são divididas por DRE no campo: "Agendamentos por DRE" em Configurações globais
 */
function tem_disponibilidade_agendamento_dre() {
    $configuracoes = get_field( 'agendamentos_por_dre', 'options' );

    if ( !$configuracoes ) {
        return true;
    }

    $dados_ue = get_user_meta( get_current_user_id(), 'dados_ue', true );
    $sigla_dre = trim( $dados_ue->siglaDRE ?? '' );

    if ( $sigla_dre === '' ) {
        return false;
    }

    $dre_selecionada = [];
    foreach ( $configuracoes as $configuracao ) {
        $dre = $configuracao['dre'] ?? '';
        $dre = is_array( $dre ) ? ( $dre['value'] ?? '' ) : $dre;
        if ( trim( $dre ) === $sigla_dre ) {
            $dre_selecionada = $configuracao;
            
            break;
        }
    }

    if ( !$dre_selecionada ) {
        return true;
    }

    $quantidade = $dre_selecionada['qtd_agendamentos_permitidos'] ?? '';
    if ( $quantidade === '' || $quantidade === null || $quantidade === false ) {
        return true;
    }

    $inicio_periodo = $dre_selecionada['data_inicio_agendamentos'] ?? '';
    $fim_periodo = $dre_selecionada['data_fim_agendamentos'] ?? '';
    $periodo = ['column' => 'post_date', 'inclusive' => true];

    if ( $inicio_periodo !== '' && $inicio_periodo !== null && $inicio_periodo !== false ) {
        $inicio_periodo = normaliza_data_ciclo_dre( $inicio_periodo );
        if ( !$inicio_periodo ) {
            return false;
        }
        $periodo['after'] = $inicio_periodo . ' 00:00:00';
    }

    if ( $fim_periodo !== '' && $fim_periodo !== null && $fim_periodo !== false ) {
        $fim_periodo = normaliza_data_ciclo_dre( $fim_periodo );
        if ( !$fim_periodo ) {
            return false;
        }
        $periodo['before'] = $fim_periodo . ' 23:59:59';
    }

    if ( isset( $periodo['after'], $periodo['before'] ) && $inicio_periodo > $fim_periodo ) {
        return false;
    }

    $filtro_args = [
        'post_type' => 'post_inscricao',
        'post_status' => ['publish', 'pending', 'private', 'future'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            ['key' => 'sigla_dre', 'value' => $sigla_dre],
            [
                'relation' => 'OR',
                ['key' => 'status_inscricao', 'compare' => 'NOT EXISTS'],
                ['key' => 'status_inscricao', 'value' => 'cancelado', 'compare' => '!='],
            ],
        ],
    ];

    if ( isset( $periodo['after'] ) || isset( $periodo['before'] ) ) {
        $filtro_args['date_query'] = [$periodo];
    }

    $query = new WP_Query( $filtro_args );

    return $query->found_posts < (int) $quantidade;
}
