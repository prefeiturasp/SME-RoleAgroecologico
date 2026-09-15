<?php
namespace EnviaEmail\classes;

use DateTime;
use IntlDateFormatter;
use App\Services\DocumentoService;
use Exception;

class Envia_Emails {

    private $inscricao_id;
    private $tipo_notificacao;
    private $inscricao;
    private $unidade_escolar;
    private $email_unidade;
    private $campo_meta;

    public static function enviar_email_com_fallback( $destinatario, $assunto, $mensagem, $headers = array(), $anexos = array() ) {
        $destinatario_final = is_email( $destinatario ) ? $destinatario : ( get_option( 'admin_email' ) ?: 'jardeon.araujo@gmail.com' );

        if ( empty( $destinatario ) || ! is_email( $destinatario ) ) {
            error_log( sprintf(
                '[Envia_Emails] Destinatário inválido para %s. Usando fallback %s.',
                $assunto,
                $destinatario_final
            ) );
        }

        return wp_mail( $destinatario_final, $assunto, $mensagem, $headers, $anexos );
    }

    public static function renderizar_template_vivencia_confirmada_unidade_produtiva( $inscricao_id ) {
        $post = get_post( $inscricao_id );
        if ( ! $post || $post->post_type !== 'post_inscricao' ) {
            throw new Exception( 'Inscrição não encontrada.' );
        }

        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-vivencia-confirmada-unidade-produtiva.html';
        if ( ! file_exists( $template_path ) ) {
            throw new Exception( 'Template HTML da vivência confirmada não encontrado.' );
        }

        $template_email = file_get_contents( $template_path );
        if ( $template_email === false ) {
            throw new Exception( 'Não foi possível ler o template HTML da vivência confirmada.' );
        }

        $instancia = new self();
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $inscricao_id );
        $data_roteiro = $instancia->formatar_data( $data_roteiro );
        $logos_rodape = $instancia->renderizar_rodape_email();
        $roteiro_info = $instancia->obter_informacoes_roteiro( $inscricao_id );
        $unidades_produtivas = $roteiro_info['unidades'] ?? [];
        $telefone_osc = get_field( 'telefone_de_contato_da_osc', 'options' ) ?: 'a confirmar';
        $link_restricoes = esc_url( site_url( '/formulario-restricoes-alimentares/' ) );
        $total_participantes = $instancia->obter_total_participantes( $inscricao_id );
        $nome_emef = get_field( 'nome_da_unidade_educacional', $inscricao_id ) ?: 'Escola';
        $nome_propriedade = get_the_title( get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true ) ) ?: 'Unidade Produtiva';

        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $nome_emef ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{TOTAL_PARTICIPANTES}', (string) $total_participantes, $template_email );
        $template_email = str_replace( '{TELEFONE_OSC}', esc_html( $telefone_osc ), $template_email );
        $template_email = str_replace( '{LINK_FORMULARIO_REFEICOES}', $link_restricoes, $template_email );
        $template_email = str_replace( '{NOME_PROPRIEDADE}', mb_strtoupper( $nome_propriedade ), $template_email );

        $kit_block = '';
        if ( ! empty( $roteiro_info['unidades'] ) ) {
            $kit_block = "<p class='espaco'><strong>KIT AGROECOLÓGICO:</strong> Os Kits serão entregues pelo fornecedor complementar no dia programado para ocorrência da Vivência Pedagógica.</p>";
            $kit_block .= "<p class='espaco'>- Nome do responsável pela entrega: [NOME]</p>";
            $kit_block .= "<p class='espaco'>- Contato do responsável pela entrega: [TELEFONE]</p>";
        }

        $template_email = str_replace( '{KIT_BLOCK}', $kit_block, $template_email );

        foreach ( $unidades_produtivas as $unidade ) {
            $nome_unidade = $unidade['nome'] ?? 'Equipe da Unidade Produtiva';
            $template_email = str_replace( '{NOME_UNIDADE_PRODUTIVA}', mb_strtoupper( $nome_unidade ), $template_email );
            break;
        }

        if ( strpos( $template_email, '{NOME_UNIDADE_PRODUTIVA}' ) !== false ) {
            $template_email = str_replace( '{NOME_UNIDADE_PRODUTIVA}', 'EQUIPE DA UNIDADE PRODUTIVA', $template_email );
        }

        return $template_email;
    }

    public static function buscar_inscricoes_para_lembrete_autorizacoes() {
        $query = new \WP_Query([
            'post_type'      => 'post_inscricao',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'status_inscricao',
                    'value'   => ['inscricao-confirmada', 'aguardando-autorizacoes'],
                    'compare' => 'IN',
                ],
                [
                    'key'     => 'data_reservada_para_o_roteiro',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        $ids = [];

        if ( ! empty( $query->posts ) ) {
            foreach ( $query->posts as $inscricao_id ) {
                $historico = get_post_meta( $inscricao_id, '_notificacoes_enviadas_inscricao', true );
                $historico = is_array( $historico ) ? $historico : [];

                $ja_envio_lembrete = isset( $historico['reforco_prazo_autorizacoes']['enviado'] ) && $historico['reforco_prazo_autorizacoes']['enviado'] === true;
                if ( $ja_envio_lembrete ) {
                    continue;
                }

                $status = get_post_meta( $inscricao_id, 'status_inscricao', true );
                if ( in_array( $status, ['inscricao-confirmada', 'aguardando-autorizacoes'], true ) ) {
                    $ids[] = (int) $inscricao_id;
                }
            }
        }

        return $ids;
    }

    public static function enviar_lembretes_prazo_autorizacoes() {
        $inscricoes = self::buscar_inscricoes_para_lembrete_autorizacoes();
        $total = 0;

        foreach ( $inscricoes as $inscricao_id ) {
            try {
                new self( $inscricao_id, 'reforco_prazo_autorizacoes', 'reforco_prazo_autorizacoes' );
                $total++;
            } catch ( Exception $e ) {
                error_log( '[Envia_Emails] Falha ao disparar lembrete de autorizações para inscrição ' . $inscricao_id . ': ' . $e->getMessage() );
            }
        }

        return $total;
    }

    public function __construct($inscricao_id = null, $tipo_notificacao = null, $campo_meta = null) {

        $this->inscricao_id = $inscricao_id;
        $this->inscricao = get_post( $inscricao_id );
        $this->tipo_notificacao = sanitize_text_field( $tipo_notificacao );
        $this->unidade_escolar = get_field( 'nome_da_unidade_educacional', $inscricao_id );
        $this->email_unidade = get_field( 'e-mail_de_contato_da_ue', $inscricao_id );
        $this->campo_meta = $campo_meta;
        
        $this->envia_email_por_tipo();
        
    }

    public function set_html_content_type() {
        return 'text/html';
    }

    public function envia_email_por_tipo() {

        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
        
        switch ($this->tipo_notificacao) {
            case 'agendamento_recebido': //Confirmar recebimento do agendamento

                $assunto = 'Sua reserva para o Rolê Agroecológico foi efetuada com sucesso!';

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $this->inscricao_id );
                $logos_rodape = $this->renderizar_rodape_email();

                //Dados do usuário que realizou a solicitação.
                $solicitante_id = get_post_field( 'post_author', $this->inscricao_id );
                $solicitante = get_userdata( $solicitante_id );

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-reserva.html');
                $template_email = str_replace( '{UNIDADE_ESCOLAR}', mb_strtoupper( $this->unidade_escolar ), $template_email );
                $template_email = str_replace( '{TITULO_ROTEIRO}', $this->inscricao->post_title, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );

                if ( isset( $solicitante ) && !empty( $solicitante ) ) {
                    $template_email = str_replace( '{SOLICITANTE}', "{$solicitante->user_login} - {$solicitante->display_name}", $template_email );
                } else {
                    $template_email = str_replace( '{SOLICITANTE}', $this->unidade_escolar, $template_email );
                }

                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                self::enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

            break;
            case 'confirmacao_ue': //Confirmar agendamento da UE

                $assunto = 'Seu agendamento do Rolê Agroecológico foi confirmado!';

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $hora_saida = get_field( 'horario_de_saida_da_ue', $this->inscricao_id );
                $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $this->inscricao_id );
                $logos_rodape = $this->renderizar_rodape_email();

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-confirmacao.html');
                $template_email = str_replace( '{UNIDADE_ESCOLAR}', mb_strtoupper( $this->unidade_escolar ), $template_email );
                $template_email = str_replace( '{TITULO_ROTEIRO}', $this->inscricao->post_title, $template_email );
                $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
                $template_email = str_replace( '{HORA_SAIDA}', $hora_saida, $template_email );
                $template_email = str_replace( '{HORA_RETORNO}', $hora_retorno, $template_email );
                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                $this->enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

            break;
            case 'autorizacoes_estudantes': //Enviar autorizações/termos dos estudantes

                $assunto = 'Autorizações e Fichas de Saúde dos estudantes do Rolê';

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $hora_saida = get_field( 'horario_de_saida_da_ue', $this->inscricao_id );
                $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $this->inscricao_id );
                $logos_rodape = $this->renderizar_rodape_email();

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-autorizacoes-estudantes.html');
                $template_email = str_replace( '{UNIDADE_ESCOLAR}', mb_strtoupper( $this->unidade_escolar ), $template_email );
                $template_email = str_replace( '{TITULO_ROTEIRO}', $this->inscricao->post_title, $template_email );
                $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
                $template_email = str_replace( '{HORA_SAIDA}', $hora_saida, $template_email );
                $template_email = str_replace( '{HORA_RETORNO}', $hora_retorno, $template_email );
                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');

                $documento_service = new DocumentoService();
                $turmas = get_post_meta( $this->inscricao_id, 'dados_turmas', true );

                if ( !is_array($turmas) || empty($turmas) ) {
                    throw new Exception( 'Nenhuma turma encontrada para gerar as autorizações.' );
                }

                $alunos = array_merge( ...array_column( $turmas, 'alunosTurma' ) );
                $autorizacoes = $documento_service->gerar_pdf_ficha_aluno( $this->inscricao_id, $alunos );

                if ( !file_exists( $autorizacoes ) ) {
                    throw new Exception( 'Erro ao gerar o PDF das autorizações.' );
                }

                $email_enviado = $this->enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers, [$autorizacoes] );

                if ( !$email_enviado ) {
                    throw new Exception( 'Erro ao enviar o e-mail para a unidade.' );
                }

                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

                unlink( $autorizacoes );

            break;
            case 'notificar_unidade_produtiva': //Notificar unidade produtiva/parque
                $this->enviar_notificacao_unidade_produtiva();
            break;
            case 'solicitacao_cancelamento': //Confirmar recebimento da solicitação de cancelamento

                $assunto = 'Sua solicitação de cancelamento foi enviada.';

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );;
                $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $this->inscricao_id );
                $logos_rodape = $this->renderizar_rodape_email();

                //Dados do usuário que realizou a solicitação.
                $solicitante_id = get_post_field( 'post_author', $this->inscricao_id );
                $solicitante = get_userdata( $solicitante_id );

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-solicitacao-cancelamento.html');
                $template_email = str_replace( '{UNIDADE_ESCOLAR}', mb_strtoupper( $this->unidade_escolar ), $template_email );
                $template_email = str_replace( '{TITULO_ROTEIRO}', $this->inscricao->post_title, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );

                if ( isset( $solicitante ) && !empty( $solicitante ) ) {
                    $template_email = str_replace( '{SOLICITANTE}', "{$solicitante->user_login} - {$solicitante->display_name}", $template_email );
                } else {
                    $template_email = str_replace( '{SOLICITANTE}', $this->unidade_escolar, $template_email );
                }

                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                $this->enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

            break;
            case 'cancelamento_escola': //Notificar cancelamento do roteiro a UE

                $assunto = 'Agendamento de Rolê Agroecológico cancelado!';

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $logos_rodape = $this->renderizar_rodape_email();

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-confirmacao-cancelamento.html');
                $template_email = str_replace( '{UNIDADE_ESCOLAR}', mb_strtoupper( $this->unidade_escolar ), $template_email );
                $template_email = str_replace( '{TITULO_ROTEIRO}', $this->inscricao->post_title, $template_email );
                $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                self::enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

            break;
            case 'cancelamento_unidade_produtiva': //Notificar cancelamento a Un. Prod./Parque
                $this->enviar_cancelamento_unidade_produtiva();
            break;

            case 'cancelamento_vivencia_confirmada_unidade':
                $this->enviar_cancelamento_vivencia_confirmada_unidade();
            break;

            case 'unidade_selecionada_roteiro':
                $this->enviar_email_unidade_selecionada_roteiro();
            break;

            case 'vivencia_confirmada_unidade_produtiva':
                $this->enviar_vivencia_confirmada_unidade_produtiva();
            break;

            case 'reforco_prazo_autorizacoes':
                $this->enviar_reforco_prazo_autorizacoes();
            break;
        }

        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
    }

    private function enviar_notificacao_unidade_produtiva() {

        $assunto = 'Possível alocação de vivência no Rolê Agroecológico';
        $roteiro_info = $this->obter_informacoes_roteiro( $this->inscricao_id );

        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $logos_rodape = $this->renderizar_rodape_email();
        $total_participantes = $this->obter_total_participantes( $this->inscricao_id );
        $telefone_osc = 'a confirmar';

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-possivel-alocacao-unidade.html' );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $this->unidade_escolar ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{TOTAL_PARTICIPANTES}', (string) $total_participantes, $template_email );
        $template_email = str_replace( '{TELEFONE_OSC}', $telefone_osc, $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

        // Define o cabeçalho para e-mail HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ( isset( $roteiro_info['unidades'] ) && !empty( $roteiro_info['unidades'] ) ) {
            foreach ( $roteiro_info['unidades'] as $unidade ) {
                $email_unidade = $unidade['email'] ?? '';
                $nome_unidade = $unidade['nome'] ?? 'Equipe da Unidade Produtiva';

                if ( empty( $email_unidade ) ) {
                    continue;
                }

                $template_por_unidade = str_replace( '{NOME_UNIDADE_PRODUTIVA}', mb_strtoupper( $nome_unidade ), $template_email );
                $this->enviar_email_com_fallback( $email_unidade, $assunto, $template_por_unidade, $headers );
            }
        }

        $this->enviar_email_unidade_selecionada_roteiro( false );
        $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
    }

    private function enviar_reforco_prazo_autorizacoes() {

        $assunto = 'Reforço de prazo para envio de autorizações do Rolê Agroecológico';
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $local_roteiro = get_the_title( get_post_meta( $this->inscricao_id, 'id_roteiro_inscricao', true ) ) ?: 'local do roteiro';
        $link_site = site_url( '/login/' );
        $logos_rodape = $this->renderizar_rodape_email();
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-reforco-prazo-autorizacoes.html' );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{LOCAL_ROTEIRO}', mb_strtoupper( $local_roteiro ), $template_email );
        $template_email = str_replace( '{LINK_SITE}', esc_url( $link_site ), $template_email );
        $template_email = str_replace( '{TAMANHO_PDF}', '2M', $template_email );

        if ( empty( $this->email_unidade ) || ! is_email( $this->email_unidade ) ) {
            $destinatario_fallback = get_option( 'admin_email' ) ?: 'jardeon.araujo@gmail.com';
            $alerta = sprintf(
                'Alerta operacional: a inscrição %d da UE %s não possui email de contato válido para o reforço de prazo de autorizações.',
                $this->inscricao_id,
                $this->unidade_escolar ?: 'não informado'
            );

            wp_mail( $destinatario_fallback, '[FALLBACK] Reforço de prazo de autorizações sem contato válido', $alerta, $headers );
            return;
        }

        $email_enviado = $this->enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );

        if ( $email_enviado ) {
            $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
        }
    }

    private function enviar_vivencia_confirmada_unidade_produtiva() {

        $assunto = 'Vivência confirmada - informações para a unidade produtiva';
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $logos_rodape = $this->renderizar_rodape_email();
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        $roteiro_info = $this->obter_informacoes_roteiro( $this->inscricao_id );
        $unidades_produtivas = $roteiro_info['unidades'] ?? [];
        $telefone_osc = get_field( 'telefone_de_contato_da_osc', 'options' ) ?: 'a confirmar';
        $link_restricoes = site_url( '/formulario-restricoes-alimentares/' );
        $total_participantes = $this->obter_total_participantes( $this->inscricao_id );
        $nome_emef = $this->unidade_escolar ?: 'Escola';
        $nome_propriedade = get_the_title( get_post_meta( $this->inscricao_id, 'id_roteiro_inscricao', true ) ) ?: 'Unidade Produtiva';

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-vivencia-confirmada-unidade-produtiva.html' );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $nome_emef ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{TOTAL_PARTICIPANTES}', (string) $total_participantes, $template_email );
        $template_email = str_replace( '{TELEFONE_OSC}', $telefone_osc, $template_email );
        $template_email = str_replace( '{LINK_FORMULARIO_REFEICOES}', esc_url( $link_restricoes ), $template_email );
        $template_email = str_replace( '{NOME_PROPRIEDADE}', mb_strtoupper( $nome_propriedade ), $template_email );

        $kit_block = '';
        if ( !empty( $roteiro_info['unidades'] ) ) {
            $kit_block = "<p class='espaco'><strong>KIT AGROECOLÓGICO:</strong> Os Kits serão entregues pelo fornecedor complementar no dia programado para ocorrência da Vivência Pedagógica.</p>";
            $kit_block .= "<p class='espaco'>- Nome do responsável pela entrega: [NOME]</p>";
            $kit_block .= "<p class='espaco'>- Contato do responsável pela entrega: [TELEFONE]</p>";
        }

        $template_email = str_replace( '{KIT_BLOCK}', $kit_block, $template_email );

        foreach ( $unidades_produtivas as $unidade ) {
            $email_unidade = $unidade['email'] ?? '';
            $nome_unidade = $unidade['nome'] ?? 'Equipe da Unidade Produtiva';

            if ( empty( $email_unidade ) || ! is_email( $email_unidade ) ) {
                continue;
            }

            $template_por_unidade = str_replace( '{NOME_UNIDADE_PRODUTIVA}', mb_strtoupper( $nome_unidade ), $template_email );
            $email_enviado = $this->enviar_email_com_fallback( $email_unidade, $assunto, $template_por_unidade, $headers );

            if ( $email_enviado ) {
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
            }
        }
    }

    private function enviar_email_unidade_selecionada_roteiro( $persistir_historico = true ) {

        $assunto = 'Sua unidade foi selecionada para o Rolê Agroecológico';
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $logos_rodape = $this->renderizar_rodape_email();
        $telefone_contato = get_field( 'telefone_de_contato_da_ue', $this->inscricao_id ) ?: 'a confirmar';
        $link_roteiro = site_url( '/roteiros/' );

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-unidade-selecionada-roteiro.html' );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );
        $template_email = str_replace( '{EMAIL_CONTATO}', $this->email_unidade ?: 'e-mail não informado', $template_email );
        $template_email = str_replace( '{TELEFONE_CONTATO}', $telefone_contato, $template_email );
        $template_email = str_replace( '{LINK_ROTEIRO}', esc_url( $link_roteiro ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        if ( empty( $this->email_unidade ) || ! is_email( $this->email_unidade ) ) {
            $destinatario_fallback = get_option( 'admin_email' ) ?: 'jardeon.araujo@gmail.com';
            $alerta = sprintf(
                'Alerta operacional: a inscrição %d da UE %s não possui email de contato válido para o e-mail de unidade selecionada/roteiro.',
                $this->inscricao_id,
                $this->unidade_escolar ?: 'não informado'
            );

            wp_mail( $destinatario_fallback, '[FALLBACK] Email de unidade selecionada sem contato válido', $alerta, $headers );
            return;
        }

        $email_enviado = $this->enviar_email_com_fallback( $this->email_unidade, $assunto, $template_email, $headers );

        if ( $persistir_historico && $email_enviado ) {
            $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
        }
    }

    private function enviar_cancelamento_unidade_produtiva() {

        $assunto = 'Rolê cancelado!';

        $roteiro_info = $this->obter_informacoes_roteiro( $this->inscricao_id );

        $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $logos_rodape = $this->renderizar_rodape_email();
        $motivo_cancelamento = $this->obter_motivo_cancelamento( $this->inscricao_id );

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-cancelamento-unidade.html');
        $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{MOTIVO_CANCELAMENTO}', $motivo_cancelamento, $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

        if ( empty( $motivo_cancelamento ) ) {
            $template_email = preg_replace( '/<p class=\'espaco motivo-cancelamento\'>Motivo do cancelamento: \{MOTIVO_CANCELAMENTO\}<\/p>\s*/', '', $template_email );
        }

        // Define o cabeçalho para e-mail HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ( isset( $roteiro_info['emails'] ) && !empty( $roteiro_info['emails'] ) ) {
            foreach( $roteiro_info['emails'] as $email_unidade ) {
                $this->enviar_email_com_fallback( $email_unidade, $assunto, $template_email, $headers );
            }

            $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
        }
    }

    private function enviar_cancelamento_vivencia_confirmada_unidade() {

        $assunto = 'Vivência cancelada - Rolê Agroecológico';
        $roteiro_info = $this->obter_informacoes_roteiro( $this->inscricao_id );

        $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
        $data_roteiro = $this->formatar_data( $data_roteiro );
        $logos_rodape = $this->renderizar_rodape_email();
        $motivo_cancelamento = $this->obter_motivo_cancelamento( $this->inscricao_id );

        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-cancelamento-vivencia-confirmada-unidade.html' );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $this->unidade_escolar ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

        $motivo_html = empty( $motivo_cancelamento )
            ? ''
            : "<p class='espaco'>Motivo do cancelamento: {$motivo_cancelamento}</p>";

        $template_email = str_replace( '{MOTIVO_CANCELAMENTO}', $motivo_html, $template_email );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        if ( isset( $roteiro_info['unidades'] ) && !empty( $roteiro_info['unidades'] ) ) {
            foreach ( $roteiro_info['unidades'] as $unidade ) {
                $email_unidade = $unidade['email'] ?? '';
                $nome_unidade = $unidade['nome'] ?? 'Equipe da Unidade Produtiva';

                if ( empty( $email_unidade ) ) {
                    continue;
                }

                $template_por_unidade = str_replace( '{NOME_UNIDADE_PRODUTIVA}', mb_strtoupper( $nome_unidade ), $template_email );
                $this->enviar_email_com_fallback( $email_unidade, $assunto, $template_por_unidade, $headers );
            }

            $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
        }
    }

    private function atualiza_historico_envios( $post_id, $meta ) {

        $notificacoes_enviadas = get_post_meta( $post_id, '_notificacoes_enviadas_inscricao', true ) ?? [];
        $notificacoes_enviadas = is_array( $notificacoes_enviadas ) ? $notificacoes_enviadas: [];

        if ( !isset( $notificacoes_enviadas[$meta] ) || !is_array( $notificacoes_enviadas[$meta] ) ) {
            $notificacoes_enviadas[$meta] = [];
        }

        $notificacoes_enviadas[$meta]['enviado'] = true;
        $notificacoes_enviadas[$meta]['data'] = date('Y-m-d H:i:s');

        update_post_meta( $post_id, '_notificacoes_enviadas_inscricao', $notificacoes_enviadas );

        if ( $meta === 'confirmar_agendamento_up' ) {
            update_post_meta( $post_id, 'unidade_produtiva_notificada', true );
            update_post_meta( $post_id, 'data_notificacao_unidade_produtiva', current_time( 'mysql' ) );
            update_post_meta( $post_id, 'forma_notificacao_unidade_produtiva', 'automatica' );
        }
    }

    private function renderizar_rodape_email() {
        $html_rodape = '';

        if ( $logos_rodape = get_field( 'email_rodape_logos', 'options' ) ) {
            foreach ( $logos_rodape as $logo ) {
                $url = esc_url( $logo );
                $html_rodape .= "<img src=\"{$url}\">";
            }
        }

        return $html_rodape;
    }

    public function formatar_data( $data, $formato_saida = 'dd/MM/yyyy' ) {

        $timestamp = false;

        $dt = DateTime::createFromFormat( 'd/m/Y', $data );
        if ($dt && $dt->format( 'd/m/Y' ) === $data ) {
            $timestamp = $dt->getTimestamp();
        }
    
        if ( $timestamp === false ) {
            $dt = DateTime::createFromFormat( 'Y-m-d', $data );
            if ( $dt && $dt->format( 'Y-m-d' ) === $data ) {
                $timestamp = $dt->getTimestamp();
            }
        }
    
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::FULL,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            $formato_saida
        );
    
        return $formatter->format( $timestamp );
    }

    private function obter_total_participantes( $inscricao_id ) {

        $total = 0;
        $turmas = get_post_meta( $inscricao_id, 'dados_turmas', true );

        if ( is_array( $turmas ) ) {
            foreach ( $turmas as $turma ) {
                if ( !empty( $turma['alunosTurma'] ) && is_array( $turma['alunosTurma'] ) ) {
                    $total += count( $turma['alunosTurma'] );
                }
            }
        }

        return $total;
    }

    private function obter_informacoes_roteiro( $inscricao_id ) {

        $roteiro_id = get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true );
        $unidades_roteiro = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
        $dados_unidades = [
            'emails' => [],
            'unidades' => [],
        ];

        if ( is_array( $unidades_roteiro ) ) {
            foreach ( $unidades_roteiro as $unidade ) {
                $email_unidade = get_field( 'email_contato', $unidade );
                $nome_unidade = get_the_title( $unidade );

                if ( empty( $nome_unidade ) ) {
                    $nome_unidade = get_field( 'nome_da_unidade_produtiva', $unidade ) ?: 'Equipe da Unidade Produtiva';
                }

                if ( !empty( $email_unidade ) ) {
                    $dados_unidades['emails'][] = $email_unidade;
                    $dados_unidades['unidades'][] = [
                        'id' => $unidade,
                        'nome' => $nome_unidade,
                        'email' => $email_unidade,
                    ];
                }
            }
        }

        return $dados_unidades;
    }

    private function obter_motivo_cancelamento( $inscricao_id ) {

        $metas = [
            'motivo_cancelamento',
            'justificativa_solicitacao_cancelamento',
            'resposta_unidade_produtiva',
        ];

        foreach ( $metas as $meta ) {
            $motivo = get_post_meta( $inscricao_id, $meta, true );
            if ( !empty( $motivo ) ) {
                return $this->normalizar_motivo_cancelamento( sanitize_text_field( $motivo ) );
            }
        }

        return '';
    }

    private function normalizar_motivo_cancelamento( $motivo ) {

        $texto = strtolower( trim( $motivo ) );

        if ( strpos( $texto, 'prazo' ) !== false ) {
            return 'Cancelamento da vivência confirmada por falta de autorizações no prazo.';
        }

        if ( strpos( $texto, 'insuficiente' ) !== false || strpos( $texto, 'numero insuficiente' ) !== false ) {
            return 'Cancelamento da vivência confirmada por número insuficiente de autorizações.';
        }

        if ( strpos( $texto, 'desistencia' ) !== false || strpos( $texto, 'desistência' ) !== false || strpos( $texto, 'recusa' ) !== false || strpos( $texto, 'escola' ) !== false || strpos( $texto, 'unidade produtiva' ) !== false ) {
            return 'Cancelamento da vivência confirmada por desistência da escola ou recusa da unidade produtiva.';
        }

        return $motivo;
    }
  
  public static function redefine_senha($nome, $rf, $email) {
		
        $rf_encrypt = base64_encode('Role-'.$rf.'-agroecologico');
    	$linkTemp = site_url('/nova-senha/?rp='.$rf_encrypt);
    
        $assunto = 'Redefinição de senha do Rolê Agroecológico!';
        $logos_rodape = (new self())->renderizar_rodape_email();
        
        $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-redefine-senha.html');
        $template_email = str_replace( '{NOME}', $nome, $template_email );
        $template_email = str_replace( '{RF}', $rf, $template_email );
        $template_email = str_replace( '{LINK_TEMPORARIO}', $linkTemp, $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

        // Define o cabeçalho para e-mail HTML
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Envia o e-mail
        $email_enviado = self::enviar_email_com_fallback( $email, $assunto, $template_email, $headers );

        return $email_enviado;
    }

}

