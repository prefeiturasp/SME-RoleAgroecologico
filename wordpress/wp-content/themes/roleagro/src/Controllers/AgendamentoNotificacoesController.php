<?php

namespace App\Controllers;

use EnviaEmail\classes\Envia_Emails;
use Exception;

class AgendamentoNotificacoesController {
    const CRON_KIT_COMPLEMENTAR = 'roleagro_verificar_pendencias_kit_complementar';

    private $notificacoes = [];
    private $plugin_slug = 'envia-email-role-agro/envia-email-role-agro.php';
    private $post_type   = 'post_inscricao';

    public function __construct() {

        add_action( 'init', [$this, 'agendar_verificacao_kit_complementar'] );
        add_action( self::CRON_KIT_COMPLEMENTAR, [$this, 'executar_verificacao_kit_complementar'] );

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

    //Registra o evento de verificação na agenda de execuções do CRON.
    public function agendar_verificacao_kit_complementar() {
        if ( wp_next_scheduled( self::CRON_KIT_COMPLEMENTAR ) ) {
            return;
        }

        $agora = current_datetime();
        $primeira_execucao = $agora->setTime( 8, 0 );
        if ( $primeira_execucao <= $agora ) {
            $primeira_execucao = $primeira_execucao->modify( '+1 day' );
        }

        wp_schedule_event( $primeira_execucao->getTimestamp(), 'daily', self::CRON_KIT_COMPLEMENTAR );
    }

    //Executa a verificação, dispara os e-mails e registra no histórico
    public function executar_verificacao_kit_complementar() {

        $agora = obter_data_com_timezone( 'Y-m-d', 'America/Sao_Paulo' );
        $pendencias = self::buscar_pendencias_kit_complementar( $agora );
        $lembretes = self::preparar_lembretes_kit_complementar( $pendencias );
        $envios = self::disparar_lembretes_kit_complementar( $lembretes );

        return self::registrar_resultado_kit_complementar( $pendencias, $agora, $envios );
    }

    // Registra o envio do lembrete em um transient com duração de 2 dias.
    public static function registrar_resultado_kit_complementar( array $pendencias, string $data_referencia, array $envios = [] ) {
        $resultado = [
            'executado_em' => obter_data_com_timezone( 'Y-m-d H:i:s', 'America/Sao_Paulo' ),
            'data_referencia' => $data_referencia,
            'total' => count( $pendencias ),
            'pendencias' => $pendencias,
            'envios' => $envios,
        ];

        set_transient( 'roleagro_ultima_verificacao_kit_complementar', $resultado, 2 * DAY_IN_SECONDS );

        return $resultado;
    }

    /*
    * Busca as inscrições que necessitam do kit complementar e as notificações ainda não foram enviadas.
    * A busca considera roteiros que estão entre 7 e 5 dias da realização da vivência.
    */
    public static function buscar_pendencias_kit_complementar( $data_referencia = null ) {
        $hoje = $data_referencia === null
            ? current_datetime()->setTime( 0, 0 )
            : \DateTimeImmutable::createFromFormat( '!Y-m-d', $data_referencia, wp_timezone() );

        if ( !$hoje || ( $data_referencia !== null && $hoje->format( 'Y-m-d' ) !== $data_referencia ) ) {
            throw new \InvalidArgumentException( 'A data de referência deve ser válida e estar em Y-m-d.' );
        }

        $datas = [
            $hoje->modify( '+7 days' )->format( 'Y-m-d' ) => 7,
            $hoje->modify( '+5 days' )->format( 'Y-m-d' ) => 5,
        ];

        $datas_consulta = array_merge(
            array_keys( $datas ),
            array_map( function ( $data ) {
                return str_replace( '-', '', $data );
            }, array_keys( $datas ) )
        );

        $query = new \WP_Query( [
            'post_type' => 'post_inscricao',
            'post_status' => ['publish', 'pending', 'private', 'future'],
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_query' => [
                [
                    'key' => 'data_reservada_para_o_roteiro',
                    'value' => $datas_consulta,
                    'compare' => 'IN',
                ],
                [
                    'key' => '_fornecedor_kit_complementar',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => 'status_inscricao',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => 'status_inscricao',
                        'value' => ['cancelado', 'realizado'],
                        'compare' => 'NOT IN'
                    ],
                ],
            ],
        ] );

        $pendencias = [];
        foreach ( $query->posts as $inscricao_id ) {
            $notificacoes_pendentes = [];
            if ( !get_field( 'notificar_fornecedor_kit_complementar', $inscricao_id ) ) {
                $notificacoes_pendentes[] = 'fornecedor';
            }
            if ( !get_field( 'notificar_up_receptora_kit_complementar', $inscricao_id ) ) {
                $notificacoes_pendentes[] = 'unidade_receptora';
            }

            if ( !$notificacoes_pendentes ) {
                continue;
            }

            $data_passeio = get_post_meta( $inscricao_id, 'data_reservada_para_o_roteiro', true );
            if ( preg_match( '/^\d{8}$/', $data_passeio ) ) {
                $data_passeio = substr( $data_passeio, 0, 4 ) . '-' . substr( $data_passeio, 4, 2 ) . '-' . substr( $data_passeio, 6, 2 );
            }

            $pendencias[] = [
                'inscricao_id' => (int) $inscricao_id,
                'data_passeio' => $data_passeio,
                'dias_antecedencia' => $datas[$data_passeio],
                'fornecedor_id' => (int) get_post_meta( $inscricao_id, '_fornecedor_kit_complementar', true ),
                'notificacoes_pendentes' => $notificacoes_pendentes,
            ];
        }

        return $pendencias;
    }

    public static function preparar_lembretes_kit_complementar( array $pendencias ) {
        if ( !$pendencias ) {
            return [];
        }

        $usuarios = get_users( [
            'role__in' => ['gestor_empresa', 'administrador_empresa'],
            'fields' => ['ID', 'user_email'],
        ] );
        
        $destinatarios = [];
        foreach ( $usuarios as $usuario ) {
            $email = strtolower( trim( $usuario->user_email ) );
            if ( is_email( $email ) ) {
                $destinatarios[$email] = $email;
            }
        }

        $lembretes = [];
        foreach ( $pendencias as $pendencia ) {
            $inscricao_id = $pendencia['inscricao_id'];
            $dias = $pendencia['dias_antecedencia'];
            $data = \DateTimeImmutable::createFromFormat( '!Y-m-d', $pendencia['data_passeio'] );
            $nome_ue = get_post_meta( $inscricao_id, 'nome_da_unidade_educacional', true );
            $nome_fornecedor = get_the_title( $pendencia['fornecedor_id'] );
            $link_inscricao = admin_url( 'post.php?post=' . $inscricao_id . '&action=edit' );
            $rotulos = [
                'fornecedor' => 'Notificação ao fornecedor do kit complementar',
                'unidade_receptora' => 'Notificação à unidade produtiva receptora do kit complementar',
            ];

            $lista_pendencias = '<ul class="lista">';
            foreach ( $pendencia['notificacoes_pendentes'] as $tipo ) {
                $lista_pendencias .= '<li>' . esc_html( $rotulos[$tipo] ) . '</li>';
            }
            $lista_pendencias .= '</ul>';

            $logos = '';
            foreach ( (array) get_field( 'email_rodape_logos', 'options' ) as $logo ) {
                if ( is_string( $logo ) && $logo !== '' ) {
                    $logos .= '<img src="' . esc_url( $logo ) . '" alt="">';
                }
            }

            $mensagem = self::renderizar_email_kit_complementar( [
                '{INSCRICAO_ID}' => (string) $inscricao_id,
                '{DIAS_ANTECEDENCIA}' => (string) $dias,
                '{TITULO_ROTEIRO}' => esc_html( get_the_title( $inscricao_id ) ),
                '{DATA_VIVENCIA}' => esc_html( $data->format( 'd/m/Y' ) ),
                '{NOME_UNIDADE_EDUCACIONAL}' => esc_html( $nome_ue ),
                '{NOME_FORNECEDOR_COMPLEMENTAR}' => esc_html( $nome_fornecedor ),
                '{NOTIFICACOES_PENDENTES}' => $lista_pendencias,
                '{LINK_INSCRICAO}' => esc_url( $link_inscricao ),
                '{IMAGENS_RODAPE}' => $logos,
            ] );

            $lembretes[] = array_merge( $pendencia, [
                'destinatarios' => array_values( $destinatarios ),
                'assunto' => sprintf( '[Lembrete] Notificação de kit complementar pendente - inscrição #%d - Vivência em %d dias', $inscricao_id, $dias ),
                'mensagem_html' => $mensagem,
            ] );
        }

        return $lembretes;
    }

    public static function renderizar_email_kit_complementar( array $marcadores, $conteudo_html = null ) {
        if ( !defined( 'EMAILS_PLUGIN_BASE_DIR' ) ) {
            throw new Exception( 'Plugin de templates de e-mail indisponível.' );
        }
        $base = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-lembrete-kit-complementar.html';
        if ( !is_readable( $base ) ) {
            throw new Exception( 'Template do lembrete de kit complementar indisponível.' );
        }

        $template = file_get_contents( $base );
        
        if ( $template === false ) {
            throw new Exception( 'Não foi possível ler o template do lembrete de kit complementar.' );
        }

        if ( $conteudo_html === null ) {
            $conteudo_html = <<<'HTML'
                <p class="espaco">Prezada equipe da OSC,</p>
                <p class="espaco">A vivência da inscrição <strong>#{INSCRICAO_ID}</strong> ocorrerá em <strong>{DIAS_ANTECEDENCIA} dias</strong> e possui notificações pendentes sobre o kit complementar.</p>
                <p class="espaco lista">
                    <strong>Roteiro:</strong> {TITULO_ROTEIRO}<br>
                    <strong>Data da vivência:</strong> {DATA_VIVENCIA}<br>
                    <strong>Unidade educacional:</strong> {NOME_UNIDADE_EDUCACIONAL}<br>
                    <strong>Fornecedor complementar:</strong> {NOME_FORNECEDOR_COMPLEMENTAR}
                </p>
                <p class="espaco">Notificações pendentes:</p>
                {NOTIFICACOES_PENDENTES}
                <p class="espaco">Acesse a inscrição para realizar o envio das notificações pendentes.</p>
                <p class="destaque"><a href="{LINK_INSCRICAO}">Acessar inscrição</a></p>
            HTML;
        }

        $template = str_replace( '{CONTEUDO}', wp_kses_post( $conteudo_html ), $template );

        return strtr( $template, $marcadores );
    }

    public static function disparar_lembretes_kit_complementar( array $lembretes ) {

        $resultados = [];
        foreach ( $lembretes as $lembrete ) {
            $inscricao_id = $lembrete['inscricao_id'];
            if ( !$lembrete['destinatarios'] ) {
                $resultados[] = ['inscricao_id' => $inscricao_id, 'status' => 'falha', 'mensagem' => 'Nenhum destinatário OSC com e-mail válido.'];
                continue;
            }

            $pendentes = [];
            $meta = '_lembretes_kit_complementar';
            $historico = get_post_meta( $inscricao_id, $meta, true );
            $historico = is_array( $historico ) ? $historico : [];
            $dia = $lembrete['data_passeio'];
            $prazo = $lembrete['dias_antecedencia'];
            $destinatarios_enviados = $historico[$dia][$prazo]['destinatarios'] ?? [];

            foreach ( $lembrete['destinatarios'] as $email ) {
                $email = strtolower( trim( $email ) );
                $resultado = ['inscricao_id' => $inscricao_id, 'destinatario' => $email];
                if ( !is_email( $email ) ) {
                    $resultados[] = array_merge( $resultado, ['status' => 'falha', 'mensagem' => 'E-mail inválido.'] );
                    continue;
                }

                if ( in_array( $email, $destinatarios_enviados, true ) ) {
                    $resultados[] = array_merge( $resultado, ['status' => 'ja_enviado'] );
                } else {
                    $pendentes[$email] = $email;
                }
            }

            if ( !$pendentes ) {
                continue;
            }

            $erro_envio = null;
            try {
                $enviado = wp_mail(
                    array_keys( $pendentes ),
                    $lembrete['assunto'],
                    $lembrete['mensagem_html'],
                    ['Content-Type: text/html; charset=UTF-8']
                );
                if ( !$enviado ) {
                    throw new Exception( 'O serviço de e-mail não aceitou o envio.' );
                }

                $historico[$dia][$prazo] = [
                    'enviado_em' => current_datetime()->format( 'Y-m-d H:i:sP' ),
                    'destinatarios' => array_values( array_unique( array_merge( $destinatarios_enviados, array_keys( $pendentes ) ) ) ),
                ];
                if ( !update_post_meta( $inscricao_id, $meta, $historico ) ) {
                    throw new Exception( 'E-mail aceito, mas houve falha ao registrar o envio; uma nova tentativa pode repetir o lembrete.' );
                }
            } catch ( \Throwable $erro ) {
                $erro_envio = $erro->getMessage();
            }

            foreach ( $pendentes as $email ) {
                $resultado = ['inscricao_id' => $inscricao_id, 'destinatario' => $email];

                if ( $erro_envio === null ) {
                    $resultado['status'] = 'enviado';
                } else {
                    $resultado['status'] = 'falha';
                    $resultado['mensagem'] = $erro_envio;
                }

                $resultados[] = $resultado;
            }
        }

        return $resultados;
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