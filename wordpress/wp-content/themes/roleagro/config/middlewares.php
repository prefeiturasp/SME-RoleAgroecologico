<?php

function can_realizar_agendamentos() {
    return current_user_can( 'realizar_agendamentos' );
}

function can_solicitar_cancelamento( int $id_inscricao ) {

    $status_permitidos = ['novo', 'inscricao-confirmada', 'aguardando-autorizacoes'];
    $status_inscricao = get_post_meta( $id_inscricao, 'status_inscricao', true );

    return in_array( $status_inscricao, $status_permitidos );
}

function tem_disponibilidade_agendamento() {

    $usuario_id = get_current_user_id();
    $usuario_lotacao = get_user_meta( $usuario_id, 'unidade_locacao', true );

    if ( !isset( $usuario_lotacao['codUnidade'] ) || empty( $usuario_lotacao['codUnidade'] ) ) {
        return false;
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

    return $query->found_posts < $qtd_agendamentos_permitidos;
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