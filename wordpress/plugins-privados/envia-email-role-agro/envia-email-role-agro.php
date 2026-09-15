<?php 
/**
 * Plugin Name: Envio de Emails Personalizados - SME - v2
 * Description: Envia e-mails e notificações personalizadas.
 * Version: 1.1
 * Author: Jardeon J M Araujo
 */

defined('ABSPATH') || die('Ops, acesso negado!');

// Dentro do plugin
require_once plugin_dir_path(__FILE__) . 'src/classes/Envia_Emails.php';

//require_once 'vendor/autoload.php';

define('EMAILS_PLUGIN_BASE_URL', WP_PLUGIN_URL . "/" . dirname(plugin_basename(__FILE__)));
define('EMAILS_PLUGIN_BASE_DIR', WP_PLUGIN_DIR . "/" . dirname(plugin_basename(__FILE__)));

function roleagro_email_agendar_lembrete_autorizacoes() {
    if ( wp_next_scheduled( 'roleagro_email_lembrete_autorizacoes_daily' ) ) {
        return;
    }

    $timestamp = strtotime( 'today 07:00' );
    if ( $timestamp <= time() ) {
        $timestamp += DAY_IN_SECONDS;
    }

    wp_schedule_event( $timestamp, 'daily', 'roleagro_email_lembrete_autorizacoes_daily' );
}

function roleagro_email_limpar_lembrete_autorizacoes() {
    wp_clear_scheduled_hook( 'roleagro_email_lembrete_autorizacoes_daily' );
}

register_activation_hook( __FILE__, 'roleagro_email_agendar_lembrete_autorizacoes' );
register_deactivation_hook( __FILE__, 'roleagro_email_limpar_lembrete_autorizacoes' );
add_action( 'init', 'roleagro_email_agendar_lembrete_autorizacoes' );
add_action( 'init', function() {
    add_rewrite_rule( '^preview-vivencia-confirmada-up/([0-9]+)/?$', 'index.php?roleagro_preview_vivencia_confirmada_up=$matches[1]', 'top' );
} );
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'roleagro_preview_vivencia_confirmada_up';
    return $vars;
} );
add_action( 'template_redirect', function() {
    $id = absint( get_query_var( 'roleagro_preview_vivencia_confirmada_up' ) );

    if ( empty( $id ) ) {
        return;
    }

    try {
        $html = \EnviaEmail\classes\Envia_Emails::renderizar_template_vivencia_confirmada_unidade_produtiva( $id );
        header( 'Content-Type: text/html; charset=UTF-8' );
        echo $html;
        exit;
    } catch ( Exception $e ) {
        status_header( 500 );
        echo '<pre>' . esc_html( $e->getMessage() ) . '</pre>';
        exit;
    }
} );
add_action( 'roleagro_email_lembrete_autorizacoes_daily', function() {
    \EnviaEmail\classes\Envia_Emails::enviar_lembretes_prazo_autorizacoes();
} );

add_action( 'rest_api_init', function() {
    register_rest_route( 'envia-email-roleagro', '/lembrete-autorizacoes', array(
        'methods' => 'GET',
        'callback' => 'roleagro_email_plugin_lembrete_autorizacoes',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'envia-email-roleagro', '/teste-vivencia-confirmada-up/(?P<id_inscricao>\\d+)', array(
        'methods' => 'GET',
        'callback' => 'roleagro_email_plugin_teste_vivencia_confirmada_up',
        'permission_callback' => '__return_true',
    ) );
} );

function roleagro_email_plugin_lembrete_autorizacoes( WP_REST_Request $request ) {
    $quantidade = \EnviaEmail\classes\Envia_Emails::enviar_lembretes_prazo_autorizacoes();

    return new WP_REST_Response( array(
        'success' => true,
        'total_enviados' => $quantidade,
        'mensagem' => 'Varredura concluída para lembrete de autorizações.',
    ), 200 );
}

function roleagro_email_plugin_teste_vivencia_confirmada_up( WP_REST_Request $request ) {
    $id = absint( $request->get_param( 'id_inscricao' ) );

    if ( empty( $id ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'mensagem' => 'Informe o id da inscrição na rota /teste-vivencia-confirmada-up/{id_inscricao}.',
        ), 400 );
    }

    try {
        new \EnviaEmail\classes\Envia_Emails( $id, 'vivencia_confirmada_unidade_produtiva', 'vivencia_confirmada_unidade_produtiva' );

        return new WP_REST_Response( array(
            'success' => true,
            'mensagem' => 'Disparo de teste concluído para vivência confirmada da unidade produtiva.',
            'id_inscricao' => $id,
        ), 200 );
    } catch ( Exception $e ) {
        return new WP_REST_Response( array(
            'success' => false,
            'mensagem' => $e->getMessage(),
            'id_inscricao' => $id,
        ), 500 );
    }
}
