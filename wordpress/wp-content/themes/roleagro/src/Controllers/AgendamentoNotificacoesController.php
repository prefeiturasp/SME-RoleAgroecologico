<?php

namespace App\Controllers;

use EnviaEmail\classes\Envia_Emails;
use Exception;

class AgendamentoNotificacoesController {

    private $notificacoes = [];
    private $plugin_slug = 'envia-email-role-agro/envia-email-role-agro.php';
    private $post_type   = 'post_inscricao';

    public function __construct() {

        add_action( 'admin_notices', [$this, 'verificar_plugin_ativo'] );
        add_action( 'admin_notices', [$this, 'exibir_mensagens'] );

        $this->notificacoes = [
            'confirmacao_ue' => [
                'campo'    => 'confirmar_agendamento_da_ue',
                'meta'     => 'confirmar_agendamento',
            ],
            'autorizacoes_estudantes' => [
                'campo'    => 'enviar_autorizacoestermos_dos_estudantes',
                'meta'     => 'autorizacoes',
            ],
            'notificar_unidade_produtiva' => [
                'campo'    => 'notificar_a_unidade_produtiva_parque',
                'meta'     => 'confirmar_agendamento_up',
            ],
            'cancelamento_escola' => [
                'campo'    => 'notificar_cancelamento_do_roteiro_a_ue',
                'meta'     => 'cancelamento_ue',
            ],
            'cancelemanto_unidade_produtiva' => [
                'campo'    => 'notificar_cancelamento_a_un_prod_parque',
                'meta'     => 'cancelamento_unidade',
            ],
        ];

        foreach ( $this->notificacoes as $notificacao ) {
            add_filter( "acf/update_value/name={$notificacao['campo']}", [$this, 'verificar_notificacoes'], 20, 3 );
        }
    }

    public function verificar_notificacoes( $novo_valor, $post_id, $campo ) {

        if (get_post_type($post_id) !== 'post_inscricao') {
            return $novo_valor;
        }

        if ( !is_plugin_active( $this->plugin_slug ) ) {
            return $novo_valor;
        }

        $notificacoes_enviadas = get_post_meta( $post_id, '_notificacoes_enviadas_inscricao', true );

        foreach ( $this->notificacoes as $tipo_notificacao => $notificacao ) {

            if ($notificacao['campo'] !== $campo['name']) {
                continue;
            }

            if ( !isset( $notificacoes_enviadas[$notificacao['meta']] ) || $notificacoes_enviadas[$notificacao['meta']]['enviado'] != true ) {

                $valor_antigo = get_post_meta( $post_id, $campo['name'], true );

                if ( $novo_valor && !$valor_antigo ) {

                    try {
                        new Envia_Emails( $post_id, $tipo_notificacao, $notificacao['meta'] );

                    } catch ( Exception $e ) {
                        set_transient( 'autorizacoes_erro_' . get_current_user_id(), $e->getMessage(), 30 );
    
                        return $valor_antigo;
                    }
                }
            }
        }

        return $novo_valor;
    }

    /**
     * Verifica se o plugin está ativo e exibe um aviso se não estiver.
     */
    public function verificar_plugin_ativo() {
        global $typenow;

        if ( $typenow !== $this->post_type ) {
            return;
        }

        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        if ( ! is_plugin_active( $this->plugin_slug ) ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Atenção:</strong> O plugin de disparo de e-mails personalizados não está ativo. As ações de envio de e-mail não serão disparadas.</p>';
            echo '</div>';
        }
    }

    /**
     * Exibe as mensagens de erro geradas pelas ações realizadas na página de Inscrições
    */
    public function exibir_mensagens() {

        $mensagem = get_transient('autorizacoes_erro_' . get_current_user_id());
        if ($mensagem) {

            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>Falha:</strong> ' . esc_html($mensagem) . '</p>';
            echo '</div>';

            delete_transient('autorizacoes_erro_' . get_current_user_id());
        }
    }
}