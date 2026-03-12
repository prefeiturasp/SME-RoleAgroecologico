<?php 

use App\Services\ApiEolService;
use EnviaEmail\classes\Envia_Emails;

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