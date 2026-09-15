<?php 

use App\Services\ApiEolService;
use EnviaEmail\classes\Envia_Emails;

add_action( 'phpmailer_init', 'roleagro_email_debug_mailhog_config' );
function roleagro_email_debug_mailhog_config( $phpmailer ) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'mailhog';
    $phpmailer->Port = 1025;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPAutoTLS = false;
    $phpmailer->SMTPSecure = '';
    $phpmailer->From = 'no-reply@roleagro.local';
    $phpmailer->FromName = 'RoleAgro';
}

add_action( 'rest_api_init', 'roleagro_email_debug_register_route' );
function roleagro_email_debug_register_route() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }

    if ( !is_plugin_active( 'envia-email-roleagro/envia-email-role-agro.php' ) ) {
        return;
    }

    register_rest_route( 'envia-email-roleagro', '/teste-envio/(?P<idInscricao>\d+)/(?P<tipo>[a-zA-Z0-9_-]+)', array(
        'methods'  => 'GET',
        'callback' => 'roleagro_email_debug_prereserva',
        'permission_callback' => '__return_true',
    ) );

    register_rest_route( 'envia-email-roleagro', '/lembrete-autorizacoes', array(
        'methods'  => 'GET',
        'callback' => 'roleagro_email_debug_lembrete_autorizacoes',
        'permission_callback' => '__return_true',
    ) );
}

function roleagro_email_debug_lembrete_autorizacoes( WP_REST_Request $request ) {
    $quantidade = Envia_Emails::enviar_lembretes_prazo_autorizacoes();

    return new WP_REST_Response( array(
        'success' => true,
        'total_enviados' => $quantidade,
        'mensagem' => 'Varredura concluída para lembrete de autorizações.',
    ), 200 );
}

function roleagro_email_debug_prereserva( WP_REST_Request $request ) {
    $inscricao_id = absint( $request->get_param( 'idInscricao' ) );
    $tipo = sanitize_text_field( $request->get_param( 'tipo' ) );

    if ( $inscricao_id <= 0 ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Informe um idInscricao válido.' ), 400 );
    }

    $tipos_permitidos = array(
        'notificacao-pre-reserva',
        'notificacao-cancelamento-vivencia',
        'notificacao-unidade-selecionada-roteiro',
        'notificacao-reforco-prazo-autorizacoes',
        'notificacao-vivencia-confirmada-unidade-produtiva',
    );

    if ( ! in_array( $tipo, $tipos_permitidos, true ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Parâmetro inválido. Use notificacao-pre-reserva, notificacao-cancelamento-vivencia, notificacao-unidade-selecionada-roteiro, notificacao-reforco-prazo-autorizacoes ou notificacao-vivencia-confirmada-unidade-produtiva.',
        ), 400 );
    }

    $inscricao = get_post( $inscricao_id );
    if ( ! $inscricao || $inscricao->post_type !== 'post_inscricao' ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Inscrição não encontrada.' ), 404 );
    }

    $emef = get_field( 'nome_da_unidade_educacional', $inscricao_id );
    if ( empty( $emef ) ) {
        $emef = $inscricao->post_title;
    }

    $data_roteiro = get_field( 'data_reservada_para_o_roteiro', $inscricao_id );
    $data_formatada = roleagro_email_debug_format_data( $data_roteiro );

    $total_participantes = roleagro_email_debug_total_participantes( $inscricao_id );
    $unidades_produtivas = roleagro_email_debug_obter_unidades_produtivas( $inscricao_id );
    $nome_unidades_produtivas = roleagro_email_debug_formatar_unidades_produtivas( $unidades_produtivas );

    $template_email = '';
    $assunto = '';
    $template_path = '';

    if ( $tipo === 'notificacao-pre-reserva' ) {
        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-possivel-alocacao-unidade.html';
        $assunto = 'Possível alocação de vivência no Rolê Agroecológico';
    }

    if ( $tipo === 'notificacao-cancelamento-vivencia' ) {
        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-cancelamento-vivencia-confirmada-unidade.html';
        $assunto = 'Vivência cancelada - Rolê Agroecológico';
    }

    if ( $tipo === 'notificacao-unidade-selecionada-roteiro' ) {
        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-unidade-selecionada-roteiro.html';
        $assunto = 'Sua unidade foi selecionada para o Rolê Agroecológico';
    }

    if ( $tipo === 'notificacao-reforco-prazo-autorizacoes' ) {
        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-reforco-prazo-autorizacoes.html';
        $assunto = 'Reforço de prazo para envio de autorizações do Rolê Agroecológico';
    }

    if ( $tipo === 'notificacao-vivencia-confirmada-unidade-produtiva' ) {
        $template_path = EMAILS_PLUGIN_BASE_DIR . '/src/templates/tema-email-vivencia-confirmada-unidade-produtiva.html';
        $assunto = 'Vivência confirmada - informações para a unidade produtiva';
    }

    if ( empty( $template_path ) || ! file_exists( $template_path ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Template de e-mail não encontrado para o tipo solicitado.',
        ), 404 );
    }

    $template_email = file_get_contents( $template_path );

    if ( $tipo === 'notificacao-pre-reserva' ) {
        $template_email = str_replace( '{NOME_UNIDADE_PRODUTIVA}', $nome_unidades_produtivas, $template_email );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $emef ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_formatada, $template_email );
        $template_email = str_replace( '{TOTAL_PARTICIPANTES}', (string) $total_participantes, $template_email );
        $template_email = str_replace( '{TELEFONE_OSC}', 'a confirmar', $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', '', $template_email );
    }

    if ( $tipo === 'notificacao-cancelamento-vivencia' ) {
        $template_email = str_replace( '{NOME_UNIDADE_PRODUTIVA}', $nome_unidades_produtivas, $template_email );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $emef ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_formatada, $template_email );
        $template_email = str_replace( '{IMAGENS_RODAPE}', '', $template_email );

        $motivo_cancelamento = 'Cancelamento da vivência confirmada por falta de autorizações no prazo.';
        $motivo_html = "<p class='espaco'>Motivo do cancelamento: {$motivo_cancelamento}</p>";
        $template_email = str_replace( '{MOTIVO_CANCELAMENTO}', $motivo_html, $template_email );
    }

    if ( $tipo === 'notificacao-unidade-selecionada-roteiro' ) {
        $email_contato = get_field( 'e-mail_de_contato_da_ue', $inscricao_id ) ?: 'e-mail não informado';
        $telefone_contato = get_field( 'telefone_de_contato_da_ue', $inscricao_id ) ?: 'a confirmar';
        $link_roteiro = site_url( '/roteiros/' );

        $template_email = str_replace( '{IMAGENS_RODAPE}', '', $template_email );
        $template_email = str_replace( '{EMAIL_CONTATO}', esc_html( $email_contato ), $template_email );
        $template_email = str_replace( '{TELEFONE_CONTATO}', esc_html( $telefone_contato ), $template_email );
        $template_email = str_replace( '{LINK_ROTEIRO}', esc_url( $link_roteiro ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_formatada, $template_email );
    }

    if ( $tipo === 'notificacao-reforco-prazo-autorizacoes' ) {
        $local_roteiro = get_the_title( get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true ) ) ?: 'local do roteiro';
        $link_site = site_url( '/login/' );

        $template_email = str_replace( '{IMAGENS_RODAPE}', '', $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_formatada, $template_email );
        $template_email = str_replace( '{LOCAL_ROTEIRO}', mb_strtoupper( $local_roteiro ), $template_email );
        $template_email = str_replace( '{LINK_SITE}', esc_url( $link_site ), $template_email );
        $template_email = str_replace( '{TAMANHO_PDF}', '2M', $template_email );
    }

    if ( $tipo === 'notificacao-vivencia-confirmada-unidade-produtiva' ) {
        $link_restricoes = site_url( '/formulario-restricoes-alimentares/' );
        $telefone_osc = get_field( 'telefone_de_contato_da_osc', 'options' ) ?: 'a confirmar';
        $nome_propriedade = get_the_title( get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true ) ) ?: 'Unidade Produtiva';

        $template_email = str_replace( '{IMAGENS_RODAPE}', '', $template_email );
        $template_email = str_replace( '{NOME_UNIDADE_PRODUTIVA}', $nome_unidades_produtivas, $template_email );
        $template_email = str_replace( '{NOME_EMEF}', mb_strtoupper( $emef ), $template_email );
        $template_email = str_replace( '{DATA_ROTEIRO}', $data_formatada, $template_email );
        $template_email = str_replace( '{TOTAL_PARTICIPANTES}', (string) $total_participantes, $template_email );
        $template_email = str_replace( '{TELEFONE_OSC}', esc_html( $telefone_osc ), $template_email );
        $template_email = str_replace( '{LINK_FORMULARIO_REFEICOES}', esc_url( $link_restricoes ), $template_email );
        $template_email = str_replace( '{NOME_PROPRIEDADE}', mb_strtoupper( $nome_propriedade ), $template_email );
        $template_email = str_replace( '{KIT_BLOCK}', '', $template_email );
    }

    add_filter( 'wp_mail_content_type', 'roleagro_email_debug_set_html_content_type' );
    $email_enviado = wp_mail( 'jardeon.araujo@gmail.com', $assunto, $template_email );
    remove_filter( 'wp_mail_content_type', 'roleagro_email_debug_set_html_content_type' );

    return new WP_REST_Response( array(
        'success' => $email_enviado,
        'template' => $tipo,
        'destinatario' => 'jardeon.araujo@gmail.com',
        'inscricao_id' => $inscricao_id,
        'data_roteiro' => $data_formatada,
        'total_participantes' => $total_participantes,
    ), $email_enviado ? 200 : 500 );
}

function roleagro_email_debug_set_html_content_type() {
    return 'text/html';
}

function roleagro_email_debug_obter_unidades_produtivas( $inscricao_id ) {
    $roteiro_id = get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true );
    $unidades_roteiro = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
    $unidades = array();

    if ( ! is_array( $unidades_roteiro ) ) {
        return $unidades;
    }

    foreach ( $unidades_roteiro as $unidade_id ) {
        $nome_unidade = get_the_title( $unidade_id );

        if ( empty( $nome_unidade ) ) {
            $nome_unidade = get_field( 'nome_da_unidade_produtiva', $unidade_id );
        }

        if ( empty( $nome_unidade ) ) {
            $nome_unidade = 'Equipe da Unidade Produtiva';
        }

        $unidades[] = mb_strtoupper( trim( $nome_unidade ) );
    }

    return $unidades;
}

function roleagro_email_debug_formatar_unidades_produtivas( $unidades ) {
    if ( empty( $unidades ) ) {
        return 'UNIDADE PRODUTIVA DE TESTE';
    }

    return implode( ', ', array_unique( $unidades ) );
}

function roleagro_email_debug_total_participantes( $inscricao_id ) {
    $total = 0;

    $turmas = get_post_meta( $inscricao_id, 'dados_turmas', true );
    if ( is_array( $turmas ) ) {
        foreach ( $turmas as $turma ) {
            if ( !empty( $turma['alunosTurma'] ) && is_array( $turma['alunosTurma'] ) ) {
                $total += count( $turma['alunosTurma'] );
            }
        }
    }

    $educadores = get_post_meta( $inscricao_id, 'dados_educadores', true );
    if ( is_array( $educadores ) ) {
        $total += count( $educadores );
    }

    $acompanhantes = get_post_meta( $inscricao_id, 'dados_acompanhantes', true );
    if ( is_array( $acompanhantes ) ) {
        $total += count( $acompanhantes );
    }

    return $total;
}

function roleagro_email_debug_format_data( $data ) {
    try {
        $dt = DateTime::createFromFormat( 'd/m/Y', $data );
        if ( $dt && $dt->format( 'd/m/Y' ) === $data ) {
            $formatted = new IntlDateFormatter( 'pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', IntlDateFormatter::GREGORIAN, 'dd/MM/yyyy' );
            return $formatted->format( $dt->getTimestamp() );
        }

        $dt = DateTime::createFromFormat( 'Y-m-d', $data );
        if ( $dt && $dt->format( 'Y-m-d' ) === $data ) {
            $formatted = new IntlDateFormatter( 'pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', IntlDateFormatter::GREGORIAN, 'dd/MM/yyyy' );
            return $formatted->format( $dt->getTimestamp() );
        }
    } catch ( Exception $e ) {
        return $data;
    }

    return $data;
}

#### ADD ENDPOINT PARA CADASTRAR INFORMACOES TEMPORÁRIAS
add_action( 'rest_api_init', 'rota_armazena_info_temp' );
function rota_armazena_info_temp() {
    register_rest_route( 'info-temp', '/idPost/(?P<idPost>\d+)', array(
        'methods'  => 'POST',
        'callback' => 'obter_disp_locais',
        'permission_callback' => '__return_true' // Permissão para todos
    ) );
}


#### ADD ENDPOINT PARA BUSCAR ALUNOS PELO ID DA TURMA
add_action( 'rest_api_init', 'get_alunos_turma' );
function get_alunos_turma() {
    register_rest_route( 'agendamento', '/alunos-turma', array(
        'methods'  => 'POST',
        'callback' => 'obter_alunos_turma',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function obter_alunos_turma( $request ) {
    $data = $request->get_json_params(); 
 
    $arrAlunoTurmas = [];
    $i=0;
    foreach($data['arrIdsTurma'] as $idTurma){
        $alunos = (new ApiEolService())->get_alunos( $idTurma );

        usort( $alunos, function( $a, $b ) {
            return strcmp( $a['nomeAluno'], $b['nomeAluno'] );
        });
 
        foreach ( $alunos as $key =>$aluno ) {
            $aluno['possuiDieta'] = 0;
            if ( $aluno['possuiDeficiencia'] == 1 ) {
                $necessidades_especiais = (new ApiEolService())->get_necessidades_especiais_aluno( $aluno['codigoAluno'] );
                $alunos[$key]['necessidades_especiais'] = $necessidades_especiais;
            }
        }
        $arrRetorno = array("turma"=>$data['arrTurmas'][$i],"alunosTurma"=>$alunos);
        array_push($arrAlunoTurmas, $arrRetorno);
        $i++;
    }
    wp_send_json_success($arrAlunoTurmas);
}

#### ADD ENDPOINT PARA BUSCAR ACOMPANHANTES PELO RF
add_action( 'rest_api_init', 'get_acompanhantes_agendamento' );

function get_acompanhantes_agendamento() {
    register_rest_route( 'agendamento', '/acompanhante', array(
        'methods'  => 'POST',
        'callback' => 'get_informacoes_acompanhante',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function get_informacoes_acompanhante( $request ) {
    $data = $request->get_json_params();
    $rf = isset( $data['rf'] ) ? sanitize_text_field( $data['rf'] ) : null;
    wp_send_json_success((new ApiEolService())->get_servidor( $rf ));
}


#### ADD ENDPOINT PARA SALVAR AGENDAMENTO
add_action( 'rest_api_init', 'set_agendamento' );

function set_agendamento() {
    register_rest_route( 'agendamento', '/salvar', array(
        'methods'  => 'POST',
        'callback' => 'get_informacoes_agendamento',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function get_informacoes_agendamento( $request ) {

    $data = $request->get_json_params();

    $idRoteiro = absint($data['idRoteiro']);
    $nomeRoteiro = sanitize_text_field($data['nomeRoteiro']);
    $idUser = absint($data['idUser']);

    $dadosEducadores = $data['dadosEducadores'];
    $arrEducadores = [];

    foreach($dadosEducadores as $educador){
        $rfEdu = strval(absint($educador['rf']));
        $nomeEdu = sanitize_text_field($educador['nome']);
        $dietaEdu = sanitize_text_field($educador['dieta']);
        $necessidadeEdu = sanitize_text_field($educador['necessidades']);
        $tipoEdu = $educador['tipo'];
        $dataEdu = $educador['data_nascimento'];
        $telEdu = $educador['celular'];

        $arrSanitizado = array(
            "rf"=>$rfEdu, 
            "nome"=>$nomeEdu, 
            "tipo"=>$tipoEdu, 
            "celular"=>$telEdu, 
            "data_nascimento"=>$dataEdu, 
            "dieta"=>$dietaEdu, 
            "necessidades"=>$necessidadeEdu
        );
        
        array_push($arrEducadores, $arrSanitizado);
    }

    $dadosAcompanhantes = $data['dadosAcompanhantes'];
    $arrAcompanhantes = [];

    foreach($dadosAcompanhantes as $acompanhante){

        $rfAcomp = strval(absint($acompanhante['rf']));
        $nomeAcomp = sanitize_text_field($acompanhante['nome']);
        $justAcomp = sanitize_text_field($acompanhante['justificativa']);
        $dietaAcomp = sanitize_text_field($acompanhante['dieta']);
        $necessidadeAcomp = sanitize_text_field($acompanhante['necessidades']);
        $dataAcomp = $acompanhante['data_nascimento'];
        $telAcomp = $acompanhante['celular'];
        $tipoAcomp = $acompanhante['tipo'];

        $arrSanitizado = array(
            "rf"=>$rfAcomp, 
            "nome"=>$nomeAcomp, 
            "tipo"=>$tipoAcomp, 
            "celular"=>$telAcomp, 
            "data_nascimento"=>$dataAcomp, 
            "dieta"=>$dietaAcomp, 
            "justificativa"=>$justAcomp, 
            "necessidades"=>$necessidadeAcomp
        );

        array_push($arrAcompanhantes, $arrSanitizado);

    }
    
    $dadosTurmas = $data['dadosTurmas'];
    $dadosAgendamento = $data['dadosAgendamento'];

    // Dados do novo post
    $post_data = array(
        'post_title'    => $nomeRoteiro,
        'post_status'   => 'pending',
        'post_type'     => 'post_inscricao', // Tipo de post (pode ser alterado para 'page' ou um Custom Post Type)
        'post_author'   => $idUser
    );

    // Inserir o novo post
    $post_id = wp_insert_post($post_data);

    // Adicionar o ID do roteiro ao post
    add_post_meta($post_id, 'id_roteiro_inscricao', $idRoteiro);
    // Adicionar os dados do agendamento ao post
    add_post_meta($post_id, 'dados_agendamento', $dadosAgendamento);

    add_post_meta($post_id, 'nome_da_unidade_educacional', $dadosAgendamento['nomeUe']);
    add_post_meta($post_id, 'e-mail_de_contato_da_ue', $dadosAgendamento['emailUe']);

    add_post_meta($post_id, 'data_da_solicitacao', get_the_time('d/m/Y h:i:s', $post_id));
    add_post_meta($post_id, 'data_reservada_para_o_roteiro', $dadosAgendamento['dataAgendamento']);
    add_post_meta($post_id, 'nome_do_responsavel_da_ue_pelo_agendamento', $dadosAgendamento['nomeResponsavel']);
    add_post_meta($post_id, 'telefone_de_contato_da_ue', $dadosAgendamento['telefoneUe']);
	
	//Adiciona id do EOL
    $ulUser = get_user_meta($idUser, 'unidade_locacao', true);
    $codEolUe = $ulUser['codUnidade'];
    add_post_meta($post_id, 'codigo_eol_ue', $codEolUe);

    $tags = wp_get_post_tags( $idRoteiro );
    $txtTags = '';
    foreach ($tags as $tag) {
        $txtTags .= $tag->name . ' - ';
    }
    $txtTags = substr($txtTags, 0, -3);

    add_post_meta($post_id, 'tipo_de_roteiro', $txtTags);

    add_post_meta($post_id, 'dre', $dadosAgendamento['dre']);

    // Adicionar os dados dos educadores ao post
    add_post_meta($post_id, 'dados_educadores', $arrEducadores);
    // Adicionar os dados dos acompanhantes ao post
    add_post_meta($post_id, 'dados_acompanhantes', $arrAcompanhantes);
    // Adicionar os dados das turmas ao post
    add_post_meta($post_id, 'dados_turmas', $data['dadosTurmas']);
    // Adicionar o status da inscrição
    add_post_meta($post_id, 'status_inscricao', 'novo');

    salvaResumoInscricoes($post_id, $data['dadosTurmas'], $arrEducadores, $arrAcompanhantes);

    //Envia o e-mail de confirmação do recebimento do agendamento
    new Envia_Emails( $post_id, 'agendamento_recebido', 'confirmar_recebimento' );

    // Dispara automaticamente a notificação da unidade produtiva ao criar a inscrição,
    // para que o histórico de notificação seja persistido com o mesmo fluxo de envio.
    new Envia_Emails( $post_id, 'notificar_unidade_produtiva', 'confirmar_agendamento_up' );

    // Retornar o ID do novo post
    wp_send_json_success($data['dadosTurmas']);
}

#### ADD ENDPOINT PARA SALVAR AGENDAMENTO
add_action( 'rest_api_init', 'update_agendamento' );

function update_agendamento() {
    register_rest_route( 'agendamento', '/atualizar', array(
        'methods'  => 'POST',
        'callback' => 'up_informacoes_agendamento',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function up_informacoes_agendamento( $request ) {

    $data = $request->get_json_params();

    $idPost = absint($data['idInscricao']);
    // $idUser = absint($data['idUser']);
    $dadosEducadores = $data['dadosEducadores'];
    $arrEducadores = [];

    foreach($dadosEducadores as $educador){
        $rfEdu = strval(absint($educador['rf']));
        $nomeEdu = sanitize_text_field($educador['nome']);
        $dietaEdu = sanitize_text_field($educador['dieta']);
        $necessidadeEdu = sanitize_text_field($educador['necessidades']);
        $tipoEdu = $educador['tipo'];
        $dataEdu = $educador['data_nascimento'];
        $telEdu = $educador['celular'];

        $arrSanitizado = array(
            "rf"=>$rfEdu, 
            "nome"=>$nomeEdu, 
            "tipo"=>$tipoEdu, 
            "celular"=>$telEdu, 
            "data_nascimento"=>$dataEdu, 
            "dieta"=>$dietaEdu, 
            "necessidades"=>$necessidadeEdu
        );
        
        array_push($arrEducadores, $arrSanitizado);
    }

    $dadosAcompanhantes = $data['dadosAcompanhantes'];
    $arrAcompanhantes = [];

    foreach($dadosAcompanhantes as $acompanhante){

        $rfAcomp = strval(absint($acompanhante['rf']));
        $nomeAcomp = sanitize_text_field($acompanhante['nome']);
        $justAcomp = sanitize_text_field($acompanhante['justificativa']);
        $dietaAcomp = sanitize_text_field($acompanhante['dieta']);
        $necessidadeAcomp = sanitize_text_field($acompanhante['necessidades']);
        $dataAcomp = $acompanhante['data_nascimento'];
        $telAcomp = $acompanhante['celular'];
        $tipoAcomp = $acompanhante['tipo'];

        $arrSanitizado = array(
            "rf"=>$rfAcomp, 
            "nome"=>$nomeAcomp, 
            "tipo"=>$tipoAcomp, 
            "celular"=>$telAcomp, 
            "data_nascimento"=>$dataAcomp, 
            "dieta"=>$dietaAcomp, 
            "justificativa"=>$justAcomp, 
            "necessidades"=>$necessidadeAcomp
        );

        array_push($arrAcompanhantes, $arrSanitizado);

    }

    $dadosTurmas = $data['dadosTurmas'];

    atualizaResumoInscricoes($idPost, $dadosTurmas, $arrEducadores, $arrAcompanhantes);

    update_post_meta($idPost, 'dados_educadores', $arrEducadores);
    update_post_meta($idPost, 'dados_acompanhantes', $arrAcompanhantes);
    update_post_meta($idPost, 'dados_turmas', $dadosTurmas);

    wp_send_json_success($idPost);   
}


#### ADD ENDPOINT PARA RETORNAR EDUCADORES DO AGENDAMENTO
add_action( 'rest_api_init', 'get_educadores_by_post' );

function get_educadores_by_post() {
    register_rest_route( 'get-educadores', '/idPost', array(
        'methods'  => 'POST',
        'callback' => 'getEducadoresByPost',
        'permission_callback' => '__return_true' // Permissão para todos
    ) );
}

function getEducadoresByPost( $request ) {
    $data = $request->get_json_params();
    $post_id = absint($data['id_inscricao']);
    $educadores = get_post_meta($post_id, 'dados_educadores', true);
        
    wp_send_json_success($educadores);
}

#### ADD ENDPOINT PARA RETORNAR ACOMPANHANTES DO AGENDAMENTO
add_action( 'rest_api_init', 'get_acompanhantes_by_post' );

function get_acompanhantes_by_post() {
    register_rest_route( 'get-acompanhantes', '/idPost', array(
        'methods'  => 'POST',
        'callback' => 'getAcompanhantesByPost',
        'permission_callback' => '__return_true' // Permissão para todos
    ) );
}

function getAcompanhantesByPost( $request ) {
    $data = $request->get_json_params();
    $post_id = absint($data['id_inscricao']);
    $acompanhantes = get_post_meta($post_id, 'dados_acompanhantes', true);
        
    wp_send_json_success($acompanhantes);
}



#### ADD ENDPOINT PARA BUSCAR UNIDADE EDUCACIONAL PELO COD EOL
add_action( 'rest_api_init', 'get_unidade_educacional' );

function get_unidade_educacional() {
    register_rest_route( 'busca', '/eol-ue', array(
        'methods'  => 'POST',
        'callback' => 'get_informacoes_unidade_edu',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function get_informacoes_unidade_edu( $request ) {
    $data = $request->get_json_params();
    $cod_eol = isset( $data['cod_eol'] ) ? sanitize_text_field( $data['cod_eol'] ) : null;
    wp_send_json_success((new ApiEolService())->get_ue( $cod_eol ));
}


#### ADD ENDPOINT PARA BUSCAR ALUNOS COM DIETAS PELO NUMERO EOL DA UNIDADE
add_action( 'rest_api_init', 'get_alunos_dieta_ue' );

function get_alunos_dieta_ue() {
    register_rest_route( 'alunos-ue', '/dieta', array(
        'methods'  => 'POST',
        'callback' => 'get_informacoes_alunos_dieta_ue',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}
function get_informacoes_alunos_dieta_ue( $request ) {
    $data = $request->get_json_params();
    $idUser = isset( $data['idUser'] ) ? sanitize_text_field( $data['idUser'] ) : null;
    // wp_send_json_success("Meu id é: ".$idUser);

    $ueUser = get_user_meta($idUser, 'unidade_locacao', true);
    $coUnidade = $ueUser['codUnidade'];

    get_api_dietas($coUnidade);
}

function get_api_dietas($coUnidade){
    $curl = curl_init();
    curl_setopt_array($curl, 
        array(
            CURLOPT_URL => getenv('API_URL_SIGPAE_DIETAS').$coUnidade.'&serie=6&format=json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.getenv('API_TOKEN_SIGPAE_DIETAS')
            ),
        )
    );

    $response = curl_exec($curl);
    curl_close($curl);
    wp_send_json_success($response);
}

// Registra a busca de dietas
add_action('wp_ajax_dietas_por_ue', 'ajax_busca_dietas_por_ue');
function ajax_busca_dietas_por_ue() {
   
    // Verifica o nonce para segurança (importante!)
    if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nc_dietas_ue') ) {
        wp_send_json_error('Nonce inválido!');
        wp_die(); // Termina a execução
    }

    $codUe = sanitize_text_field($_POST['id_ue']); // Pega o id da UE enviada
    $post_id = sanitize_text_field($_POST['post_id']); // Pega o id do post enviado

    $post = get_post($post_id);
    $tipo = $post->post_type;

    if( $tipo == 'post_inscricao'){
        $resposta = [];
        $arrTurmas = get_post_meta( $post->ID, 'dados_turmas', true );

        $url_api = explode('/api/', getenv('API_URL_SIGPAE_DIETAS'));
        $url_base =  $url_api[0];
        $url_request = $url_base.'/api/solicitacoes-dieta-especial/relatorio-dieta-especial-terceirizada/?status_selecionado=AUTORIZADAS&serie=6&codigo_eol='.$codUe;    
    
        $curl = curl_init();
        curl_setopt_array($curl, 
            array(
                CURLOPT_URL => $url_request,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.getenv('API_TOKEN_SIGPAE_DIETAS')
                ),
            )
        );

        $response = curl_exec($curl);
        wp_send_json_success($response);
        curl_close($curl);
    } else {
        wp_send_json(array("success"=>false));
    }
    wp_send_json_success($response);
    wp_die(); // Termina a execução
}

// Registra a busca de dietas
add_action('wp_ajax_dietas_por_iduser', 'dietas_por_iduser');
function dietas_por_iduser() {

    // Verifica o nonce para segurança (importante!)
    if ( !isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nc_dietas_ue') ) {
        wp_send_json_error('Nonce inválido!');
        wp_die(); // Termina a execução
    }

    $idUser = sanitize_text_field($_POST['idUser']); // Pega o id da UE enviada
    $infoUE = get_user_meta( $idUser, 'unidade_locacao', true );
    $idUe = $infoUE['codUnidade'];

    $url_api = explode('/api/', getenv('API_URL_SIGPAE_DIETAS'));
    $url_base =  $url_api[0];
    $url_request = $url_base.'/api/solicitacoes-dieta-especial/relatorio-dieta-especial-terceirizada/?status_selecionado=AUTORIZADAS&serie=6&codigo_eol='.$idUe;    

    $curl = curl_init();
    curl_setopt_array($curl, 
        array(
            CURLOPT_URL => $url_request,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.getenv('API_TOKEN_SIGPAE_DIETAS')
            ),
        )
    );

    $response = curl_exec($curl);
    wp_send_json_success($response);
    curl_close($curl);

}

#### ADD ENDPOINT PARA BUSCAR ALUNOS PELO ID DA TURMA
add_action( 'rest_api_init', 'lista_presenca' );

function lista_presenca() {
    register_rest_route( 'agendamento', '/lista-presenca', array(
        'methods'  => 'POST',
        'callback' => 'salva_lista_presenca',
        'permission_callback' => '__return_true' // Permissão para todos
    ));
}

function salva_lista_presenca( $request ) {
    $data = $request->get_json_params(); 

    $opcao = isset( $data['opcao'] ) ? sanitize_text_field( $data['opcao'] ) : null;
    $idPost = isset( $data['idPost'] ) ? sanitize_text_field( $data['idPost'] ) : null;
    $check = isset( $data['check'] ) ? map_deep( $data['check'], 'sanitize_text_field' ): null;

    $retorno = [];

    switch ($opcao) {

        case 'aluno-lista':
            
            $idTurma = isset( $data['idTurma'] ) ? sanitize_text_field( $data['idTurma'] ) : null;
            $idAluno = isset( $data['idAluno'] ) ? sanitize_text_field( $data['idAluno'] ) : null;
            
            $arrTurmas = get_post_meta($idPost, 'dados_turmas', true);
            $arrTurmasMod = [];
            foreach ($arrTurmas as $key => $turma) {
                if($turma['idTurma'] == $idTurma){
                    $arrAlunos = [];
                    foreach ($turma['alunosTurma'] as $aluno) {
                        if($aluno['codigoAluno'] == $idAluno){
                            $aluno['confirmacaoPresenca'] = $check;
                        }
                        $arrAlunos[] = $aluno;
                    }
                    $arrTurmasMod[] = array("idTurma"=>$turma['idTurma'],"nomeTurma"=>$turma['nomeTurma'], "alunosTurma" => $arrAlunos);
                } else {
                    $arrTurmasMod[] = $turma;
                }
            }
            $retorno = update_post_meta($idPost,'dados_turmas', $arrTurmasMod);
        break;
        
        case 'acompanhante-lista':
           
            $idEduc = isset( $data['idEduc'] ) ? map_deep( $data['idEduc'], 'sanitize_text_field' ): null; 
            $tipo = isset( $data['tipo'] ) ? map_deep( $data['tipo'], 'sanitize_text_field' ): null; 

            if($tipo == 'Educador'){
                $arrEducadores = get_post_meta($idPost, 'dados_educadores', true);
                $arrEdu = [];
                foreach ($arrEducadores as $edu) {
                    if($edu['rf'] == $idEduc){
                        $edu['confirmacaoPresenca'] = $check;
                    }
                    $arrEdu[] = $edu;
                }
                $retorno = update_post_meta($idPost, 'dados_educadores', $arrEdu);
            } else {
                $arrAcompanhantes = get_post_meta($idPost, 'dados_acompanhantes', true);
                $arrAcomp = [];
                foreach ($arrAcompanhantes as $aco) {
                    if($aco['rf'] == $idEduc){
                        $aco['confirmacaoPresenca'] = $check;
                    }
                    $arrAcomp[] = $aco;
                }
                $retorno = update_post_meta($idPost, 'dados_acompanhantes', $arrAcomp);
            }

        break;
    }

    wp_send_json_success($retorno);
}

function retornaAlunoArr($arrAlunos, $idAluno){
    foreach ($arrAlunos as $item) {
       if($item['codigoAluno'] == $idAluno){
           return $item;
       }
    }
}

// Salva lista de presença
add_action('wp_ajax_set_lista_presenca', 'ajax_busca_set_lista_presenca');
function ajax_busca_set_lista_presenca() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo'])) {
        $post_id = sanitize_text_field($_POST['post_id']); // Pega o id do post enviado
        $arquivo = $_FILES['arquivo'];

        $obsRole = isset( $_POST['obsRole'] ) ? sanitize_text_field( $_POST['obsRole'], 'sanitize_text_field' ): null;
        update_post_meta($post_id, 'observacoes_do_role', $obsRole);
        
        enviaDocListaPresenca($post_id,$arquivo);

    } else if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $post_id = sanitize_text_field($_POST['post_id']); // Pega o id do post enviado
        $obsRole = isset( $_POST['obsRole'] ) ? sanitize_text_field( $_POST['obsRole'], 'sanitize_text_field' ): null;
        update_post_meta($post_id, 'observacoes_do_role', $obsRole);
        wp_send_json_success([
            'msg' => 'Informações salvas com sucesso!'
        ]);
        wp_die(); // Termina a execução
    }
}

add_action('wp_ajax_salva_arquivo_lista_presenca', 'salva_arquivo_lista_presenca');
function salva_arquivo_lista_presenca() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['arquivo'])) {
        $post_id = sanitize_text_field($_POST['post_id']); // Pega o id do post enviado
        $arquivo = $_FILES['arquivo'];
        enviaDocListaPresenca($post_id, $arquivo);
    }
}

function enviaDocListaPresenca($post_id, $arquivo){
    //verifica se não houve erro no envio
    if ($arquivo['error'] === UPLOAD_ERR_OK) {
        $upload_dir = get_theme_file_path( 'storage' );
        $path = "lista-presenca/recebida/{$post_id}";
        $file_path  = $upload_dir . '/' . $path;
        $tipo_arquivo = pathinfo( $arquivo['name'], PATHINFO_EXTENSION );

        if ( !file_exists( $file_path ) ) {
            wp_mkdir_p ( $file_path );
        }

        $nome_arquivo = "lista_presenca_{$post_id}.{$tipo_arquivo}";
        $caminho_final = $file_path . '/' . $nome_arquivo;

        if ( !move_uploaded_file( $arquivo['tmp_name'], $caminho_final ) ) {
            wp_send_json_error([
                'msg' => 'Houve um erro ao salvar a lista de presença.'
            ]);
        } else {
            wp_send_json_success([
                'msg' => 'Planilha importada com sucesso!'
            ]);
        }
    }
    wp_die(); // Termina a execução
}

?>