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
                wp_mail( $this->email_unidade, $assunto, $template_email, $headers );
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
                wp_mail( $this->email_unidade, $assunto, $template_email, $headers );
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

                $email_enviado = wp_mail( $this->email_unidade, $assunto, $template_email, $headers, [$autorizacoes] );

                if ( !$email_enviado ) {
                    throw new Exception( 'Erro ao enviar o e-mail para a unidade.' );
                }

                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

                unlink( $autorizacoes );

            break;
            case 'notificar_unidade_produtiva': //Notificar unidade produtiva/parque

                $assunto = 'Um novo agendamento de rolê foi realizado na sua propriedade!';
                $roteiro_info = $this->obter_informacoes_roteiro($this->inscricao_id);

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $hora_saida = get_field( 'horario_de_saida_da_ue', $this->inscricao_id );
                $hora_retorno = get_field( 'horario_previsto_de_retorno_a_ue', $this->inscricao_id );
                $logos_rodape = $this->renderizar_rodape_email();

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-agendamento-unidade.html' );
                $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
                $template_email = str_replace( '{HORA_SAIDA}', $hora_saida, $template_email );
                $template_email = str_replace( '{HORA_RETORNO}', $hora_retorno, $template_email );
                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                if ( isset( $roteiro_info['emails'] ) && !empty( $roteiro_info['emails'] ) ) {
                    foreach( $roteiro_info['emails'] as $email_unidade ) {
                        wp_mail( $email_unidade, $assunto, $template_email, $headers );
                    }

                    $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
                }
                
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
                wp_mail( $this->email_unidade, $assunto, $template_email, $headers );
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
                wp_mail( $this->email_unidade, $assunto, $template_email, $headers );
                $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );

            break;
            case 'cancelemanto_unidade_produtiva': //Notificar cancelamento a Un. Prod./Parque

                $assunto = 'Rolê cancelado!';

                $roteiro_info = $this->obter_informacoes_roteiro( $this->inscricao_id );

                $tipo_roteiro = get_field( 'tipo_de_roteiro', $this->inscricao_id );
                $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $this->inscricao_id );
                $data_roteiro = $this->formatar_data( $data_roteiro );
                $logos_rodape = $this->renderizar_rodape_email();

                $template_email = file_get_contents( EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-cancelamento-unidade.html');
                $template_email = str_replace( '{TIPO_ROTEIRO}', $tipo_roteiro, $template_email );
                $template_email = str_replace( '{DATA_ROTEIRO}', $data_roteiro, $template_email );
                $template_email = str_replace( '{IMAGENS_RODAPE}', $logos_rodape, $template_email );

                // Define o cabeçalho para e-mail HTML
                $headers = array('Content-Type: text/html; charset=UTF-8');
                
                // Envia o e-mail
                if ( isset( $roteiro_info['emails'] ) && !empty( $roteiro_info['emails'] ) ) {
                    foreach( $roteiro_info['emails'] as $email_unidade ) {
                        wp_mail( $email_unidade, $assunto, $template_email, $headers );
                    }

                    $this->atualiza_historico_envios( $this->inscricao_id, $this->campo_meta );
                }

            break;
        }

        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
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

    public function formatar_data( $data, $formato_saida = 'EEEE, dd/MM/yyyy' ) {

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

    private function obter_informacoes_roteiro( $inscricao_id ) {

        $roteiro_id = get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true );
        $unidades_roteiro = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
        $dados_unidades = [];

        if ( is_array( $unidades_roteiro ) ) {
            foreach ( $unidades_roteiro as $unidade ) {
                $email_unidade = get_field( 'email_contato', $unidade );
                $dados_unidades['emails'][] = $email_unidade;
            }
        }

        return $dados_unidades;
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
        $email_enviado = wp_mail( $email, $assunto, $template_email, $headers );

        return $email_enviado;
    }

}

