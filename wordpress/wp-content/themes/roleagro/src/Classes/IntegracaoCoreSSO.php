<?php

namespace App\Classes;

use App\Classes\Integra\Integracoes;

class IntegracaoCoreSSO {

    private $api_token;

    public function __construct() {
        global $pagenow;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Se não estiver ativa, inicia a sessão
            session_start();
        }

        // Redireciona o usuário para a página de login, ao clicar em sair
        add_action('wp_logout', array($this,'logout_page'));

        $this->api_token = getenv('SMEINTEGRACAO_API_TOKEN'); //PRODUÇÃO

        // Substituir a autenticacao do WordPress
        add_filter( 'authenticate', array( $this,'loginAuternativo'), 10, 3 );

    }

    public function carregarPerfisPorLogin($rf){
        $response = $this->buscaDadosAPI('/api/Intranet/CarregarPerfisPorLogin/' . $rf, false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosUsuarioApi($rf){
        $response = $this->buscaDadosAPI('/api/AutenticacaoSgp/'. $rf .'/dados', false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosEscolasFuncionariosApi($codUe, $codCargo){
        $response = $this->buscaDadosAPI('/api/escolas/'.$codUe.'/funcionarios/cargos/'.$codCargo, false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosFuncionariosApi($codRF){
        $response = $this->buscaDadosAPI('/api/funcionarios/cargo/'.$codRF, false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosUsuarioUnidadesCargos($rf){
        $response = $this->buscaDadosAPI('/api/funcionarios/DadosSigpae/'. $rf , false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosUsuarioSGP($rf){
        $response = $this->buscaDadosAPI('/api/AutenticacaoSgp/'.$rf.'/dados', false, 'GET');
        return json_decode($response['body']);
    }

    public function buscaDadosUnidadeEducacional( $codigo_ue ){
        $response = $this->buscaDadosAPI("/api/escolas/dados/{$codigo_ue}", false, 'GET');
        return json_decode($response['body']);
    }

    public function verificaUsuarioCadWP($username){
        // Verifica se o usuario ja esta cadastrado no WordPress
        $userobj = new \WP_User();
        $user_wp = $userobj->get_data_by( 'login', $username );
       
        if(!empty($user_wp) && isset($user_wp->ID)){
            return $user_wp;
        } else {
            return false;
        }
    }

    public function criaUsuarioWP($user){

        $senhaPadrao = sanitize_text_field($_SESSION["arrAcesso"]['pwd']);
        // Caso nao queira adicionar o usuario no WordPress
        // descomente a linha abaixo
        //$user_wp = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );
        
        // Recebe o nome completo do usuario 
        $nome = $user->nome;
        // Recebe o CPF
        $rf = $user->codigoRf;
        // Recebe o E-mail
        $email = $user->email;
        // Recebe o CPF
        // $cpf = $user->cpf;
        
        if(isset($nome) && !empty($nome)){
            $nomeCompleto = $nome;
        } else {
            $nomeCompleto = $rf;
        }

        $new_user_id = wp_insert_user(
                        array( 'user_email' => $email,
                          'user_login' => $rf,
                          'user_pass' => $senhaPadrao,
                          'first_name' => $nomeCompleto,
                          'role' => 'adm-eol',                              
                        )
                    ); // Um novo usuario sera criado
       
        if (!is_wp_error($new_user_id)) {
            // Carregar as novas informações do usuário
            $user_wp = new \WP_User ($new_user_id); 
            return $user_wp;
        } else {
            error_log('Info L104: ' . $new_user_id->get_error_message());
            $_SESSION['info-msg'] = $new_user_id->get_error_message();
            (new self())->logout_page();
        }
        
    }

    public function buscaDadosAPI($endPoint, $body = false, $metodo = 'POST') {
        // URL da API + ENDPOINT
        $api_url = getenv('SMEINTEGRACAO_API_URL') . $endPoint; //PROD
        
        if (!$body){
            $response = wp_remote_get( $api_url ,
                array( 
                    'method' => $metodo,
                    'timeout'     => 30,
                    'headers' => array( 
                        'x-api-eol-key' => $this->api_token,							
                    )
                )
            ); 
        } else {
            $response = wp_remote_post($api_url,
                array(
                    'method' => $metodo,
                    'timeout'     => 30,
                    'headers' => array( 
                        'x-api-eol-key' => $this->api_token, // Chave da API
                        'Content-Type'=> 'application/json-patch+json'
                    ),
                    'body' => $body, // Body da requisicao
                )
            );
        }

        if (is_wp_error($response)) {
            // Trata o erro, por exemplo, registrando uma mensagem no log.
            error_log('Info L141: ' . $response->get_error_message());
            $_SESSION['info-msg'] = $response->get_error_message();
            $this->logout_page();
            // wp_redirect(home_url('/login') . '/?request=noresponse');
            exit;
        }
        return $response;
    }

    public function autenticaUsuarioCoreSSO($username, $password) {

        // URL da API para onde os dados serão enviados
        $url = getenv('SMEINTEGRACAO_API_URL') .'/api/AutenticacaoSgp/Autenticar';

        // Dados do usuário a serem enviados
        $data = array(
            'login' => $username,
            'senha' => $password
        );

        // Inicia a sessão cURL
        $req = curl_init();

        // Envia dados como formulário
        curl_setopt($req, CURLOPT_URL, $url);
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($data)); // Codifica os dados para o formato de formulário
        curl_setopt($req, CURLOPT_HTTPHEADER, array(
            'x-api-eol-key:' . getenv('SMEINTEGRACAO_API_TOKEN'),
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        // Executa a requisição e armazena a resposta
        $response = curl_exec($req);

        return $response;

        curl_close($req);
    }

    public function retornaCargosPermitidos(){
        $cagos = array(
            array("cod"=>"3085"),
            array("cod"=>"3360"), 
            array("cod"=>"3379"), 
            array("cod"=>"4906"),
            array("cod"=>"3255"), 
            array("cod"=>"3263"), 
            array("cod"=>"3271"), 
            array("cod"=>"3280"), 
            array("cod"=>"3298"), 
            array("cod"=>"3301"), 
            array("cod"=>"3336"), 
            array("cod"=>"3344"), 
            array("cod"=>"3425"),  
            array("cod"=>"3433"), 
            array("cod"=>"3450"), 
            array("cod"=>"3468"), 
            array("cod"=>"3808"), 
            array("cod"=>"3816"), 
            array("cod"=>"3840"),
            array("cod"=>"3859"), 
            array("cod"=>"3867"), 
            array("cod"=>"3868"), 
            array("cod"=>"3869"), 
            array("cod"=>"3870"), 
            array("cod"=>"3871"),
            array("cod"=>"3873"), 
            array("cod"=>"3874"), 
            array("cod"=>"3876"), 
            array("cod"=>"3877"), 
            array("cod"=>"3878"), 
            array("cod"=>"3880"),
            array("cod"=>"3881"), 
            array("cod"=>"3882"), 
            array("cod"=>"3883"), 
            array("cod"=>"3884"), 
            array("cod"=>"3885")
        );
        return $cagos;
    }

    public function buscaEscola($codEscola){
        $response = $this->buscaDadosAPI('/api/escolas/'. $codEscola , false, 'GET');
        return json_decode($response['body']);
    }

    public function retornaArrFiltrado($userInfo){
       
        $arrCargos = [];
        if($userInfo->cargosSobrePosto){
            array_push($arrCargos, (array) $userInfo->cargosSobrePosto[0]);
            for($i=0; count($userInfo->cargos) > $i;$i++){
                array_push($arrCargos, (array) $userInfo->cargos[$i]);
            }
        } else if($userInfo->cargos){
            array_push($arrCargos, (array) $userInfo->cargos[0]);
        }
        return array_unique($arrCargos, SORT_REGULAR);
    }

    public function filtraArrIguais($arr){

        $arrCargos = [];
        $arrFiltrado = [];

        foreach($arr as $item){
            array_push($arrCargos, $item['cod']);
        }

        $arrCargosFilter = array_unique($arrCargos);

        foreach($arrCargosFilter as $codCargo){
            foreach($arr as $item){
                if($codCargo == $item['cod']){
                    array_push($arrFiltrado, $item);
                    break;
                }
            }
        }

        return $arrFiltrado;

    }

    public function realiza_login_wp($username, $password, $redirect = false) {
       
        // Certifique-se de que as funções principais do WP estão carregadas
        if (!function_exists('wp_set_current_user')) {
            require_once(ABSPATH . WPINC . '/pluggable.php');
        }

        $usu = wp_authenticate_username_password( null, $username, $password );
    
        // Verifica se houve erro (usuário ou senha inválidos)
        if (is_wp_error($usu)) {
            // Exibe a mensagem de erro
            $_SESSION['info-msg'] = $usu->get_error_message();
            $this->logout_page();
        } else {
            // Autentica o usuário atual e define os cookies
            wp_set_current_user($usu->ID, $usu->user_login);
            wp_set_auth_cookie($usu->ID);
            do_action('wp_login', $usu->user_login);
            // Redireciona para uma página de sucesso (ex: painel)
            if ( is_user_logged_in() ) {
                $redirect ? wp_redirect($redirect) : wp_redirect(site_url());
            } else {
                wp_redirect(site_url('/login'));
            }
        }
        $this->removeSessionsLogin();
        exit;
    }

    public function loginWP($username, $password, $redirect){
        // Tenta autenticar o usuário
        $usu = wp_authenticate_username_password( null, $username, $password );
        
        // Verifica se a autenticação foi bem-sucedida
        if (is_wp_error($usu)) {
            // Houve um erro, exibe a mensagem de erro
            error_log('Info L269: ' . $usu->get_error_message());
            $_SESSION['info-msg'] = $usu->get_error_message();
            $this->logout_page();
        } else {
            $dadosUsu = $usu->data;
            // Login bem-sucedido, o usuário agora é um objeto WP_User válido
            wp_set_current_user($dadosUsu->ID); // Configura o usuário atual na sessão
            wp_set_auth_cookie($dadosUsu->ID);
            wp_redirect($redirect);
            $this->removeSessionsLogin();
            exit;
        }
        
    }

    public function loginAuternativo( $user, $username, $password ){
       
        // Salva dos dados do acesso para logar posteriormente após verificação da permissão do acesso
        $_SESSION["arrAcesso"] = array("log"=>$username, "pwd"=>$password);

        // Verifica se o usuario e senha foram preenchidos
        if($username == '' || $password == '') return;

        // Verifica se o usuário é o admin do WP, para não precisar requisitar o CoreSSO e logar diretamente
        $usuLogin = $this->verificaUsuarioCadWP($username);
        
        if(!empty($usuLogin)){
            
            if ( user_can( $usuLogin->ID, 'acessar_painel_adm' ) ) {
                $this->realiza_login_wp($username, $password, admin_url());
            }  

            if(isset($usuLogin->ID) && strlen($username) === 7){
                $user = $this->autenticaUsuarioCoreSSO($username, $password);
                if(is_object($user)){
                    $arrCargosPermitidos = $this->verificaUnidadesCargos($user);
                    $qtdAcesso = count($arrCargosPermitidos);

                    if($qtdAcesso > 1){
                        $_SESSION['arrCargosUe'] = $arrCargosPermitidos;
                        wp_redirect(site_url('/login-duplo'));
                        exit;
                    } else {
                        $this->realiza_login_wp($username, $password, site_url());
                    }
                }
            }
            
            if(isset($usuLogin->ID) && strlen($username) === 11){
                if(user_can( $usuLogin->ID, 'pg_agendamento_lista_presenca' )){
                    $this->realiza_login_wp($username, $password, site_url('/agendamento-lista-presenca/'));
                } else {
                    $this->realiza_login_wp($username, $password, admin_url());
                }
            } 
            
            if(user_can( $usuLogin->ID, 'pg_agendamento_lista_presenca' )){
                $this->realiza_login_wp($username, $password, site_url('/agendamento-lista-presenca/'));
            }
        }

        // Verifica se o usuário está válido no CoreSSO
        $user = $this->autenticaUsuarioCoreSSO($username, $password);
        $user = json_decode($user);
        
        if(!is_object($user)){
            $this->login_failed();
        } else {
            
            // Salva os dados do perfil do usuário
            if($user->codigoRf && strlen($user->codigoRf) == 7){
                $arrCargosPermitidos = $this->verificaUnidadesCargos($user);
                $qtdAcesso = count($arrCargosPermitidos);
                if($qtdAcesso > 1){
                    $_SESSION['arrCargosUe'] = $arrCargosPermitidos;
                    wp_redirect(site_url('/login-duplo'));
                    exit;
                } else {
                    $this->atualizaEAcesssaWP($arrCargosPermitidos[0]);
                }
            }
        }
    }

    public function verificaUnidadesCargos($user){

        // $userInfo = $this->carregarPerfisPorLogin($user->codigoRf);
        $dadosEscolaFuncionarios = $this->buscaDadosFuncionariosApi($user->codigoRf);
        $dados = $dadosEscolaFuncionarios[0];
        $arrCargosPermitidos = [];

        if(is_object($dados)){
            
            $cargosPermitidos = $this->retornaCargosPermitidos();

            if($dados->cdCargoBase){
                
                $codCargo = $dados->cdCargoBase;
                $i = 0;
                foreach($cargosPermitidos as $item){
                    if($item['cod'] == $codCargo){
                        array_push($arrCargosPermitidos, array(
                            "codigoRF" => $user->codigoRf,
                            "cod"   => $dados->cdCargoBase,
                            "cargo" => $dados->cargoBase,
                            "codUe" => $dados->cdUeCargoBase,
                            "nomeUe" => $dados->ueCargoBase
                        ));
                    } 
                    $i++;
                }

            }

            if($dados->cdCargoSobreposto){
                $codCargoSobre = $dados->cdCargoSobreposto;
                foreach($cargosPermitidos as $item){
                    if($item['cod'] == $codCargoSobre){
                        array_push($arrCargosPermitidos, array(
                            "codigoRF" => $user->codigoRf,
                            "cod"   => $dados->cdCargoSobreposto,
                            "cargo" => $dados->cargoSobreposto,
                            "codUe" => $dados->cdUeCargoSobreposto,
                            "nomeUe" => $dados->ueCargoSobreposto
                        ));
                    } 
                }

            }
            
        }
        
        return $arrCargosPermitidos;
        
    }

    public function verificarPermissaoUnidade($unidade){
        // Busca a escola de acordo com o código
        $escola = $this->buscaEscola($unidade['codigo']);
        // Array de unidades permitidas
        $unidadesPermitidas = array('EMEF', 'EMEFM', 'CEU EMEF', 'EMEBS');
        // verifica se a sigla da unidade está entre as unidades permitidas
        if (in_array($escola->siglaTipoEscola, $unidadesPermitidas)) {
            return array("unidade"=>$unidade, "permissao"=>'1');
        } else {
            return array("unidade"=>$unidade, "permissao"=>'0');
        }
    }

    // Se nao autenticar o usuario redireciona para o login novamente
    // icluindo o parametro GET na URL
    public function login_failed() {	
        wp_redirect( home_url() . '/login/?login=failed' );
        exit;
    }
    
    // Se usuario/senha estiver vazio redireciona para o login novamente
    // icluindo o parametro GET na URL
    public function blank_username_password( $user, $username, $password ) {
        global $page_id;
        if( $username == "" || $password == "" ) {
            wp_redirect( home_url() . "login/?login=blank");
            exit;
        }
    }

    public static function removeSessoesUsuario($user_id){
        $sessionsUsuario = \WP_Session_Tokens::get_instance($user_id);
        $sessionsUsuario->destroy_all();
    }

    // Se for acionado a funcao de Logout (sair) redireciona o usuario para a pagina de login
    public function logout_page() {
        wp_redirect( home_url('/login') . "/?login=false" );
        exit;
    }

    public function removeSessionsLogin(){
        //Remove a session de unidades, cargos e login
        unset($_SESSION["arrAcesso"]);
        unset($_SESSION['usuCad']);
        unset($_SESSION['arrCargosUe']);
        unset($_SESSION["dadosUsu"]);
    }

    public static function atualizaEAcesssaWP($arrUsuario){

        $instancia = new self();

        $codCargo = $arrUsuario['cod'];
        $nomeCargo = $arrUsuario['cargo'];
        $codUnidade = $arrUsuario['codUe'];
        $nomeUnidade = $arrUsuario['nomeUe'];
        $codRF = $arrUsuario['codigoRF'];

        $arrCargo = array("codCargo"=>$codCargo, "nomeCargo" => $nomeCargo);
        $arrLotacao = array("codUnidade"=>$codUnidade, "nomeUnidade"=>$nomeUnidade);
        $arrUnidade = $instancia->buscaDadosUnidadeEducacional( $codUnidade );

        $usuCadastrado = $instancia->verificaUsuarioCadWP($codRF);
        
        if(isset($usuCadastrado->ID)){
 
            $dadosUsu = $usuCadastrado;

            $usuUp = wp_update_user(array(
                'ID' => $usuCadastrado->ID,
                'user_pass' => $_SESSION["arrAcesso"]['pwd']
            ));

            if (is_wp_error($usuUp)) {
                // Login falhou
                error_log('Info L455: ' . $usuUp->get_error_message());
                $_SESSION['info-msg'] = $usuUp->get_error_message();
                $instancia->logout_page();
            }

        } else {
            $infoUser = $instancia->buscaDadosUsuarioApi($codRF);
            $dadosUsu = $instancia->criaUsuarioWP($infoUser);
        }
    
        // Atualiza o cargo e a função do usuário
        update_user_meta($dadosUsu->ID , "cargo", $arrCargo); 
        update_user_meta($dadosUsu->ID , "unidade_locacao", $arrLotacao); 
        update_user_meta($dadosUsu->ID, 'dados_ue', $arrUnidade);
        
        $instancia->realiza_login_wp($codRF, $_SESSION["arrAcesso"]['pwd']);
        
    }

    public static function redefineSenhaCoreSSO($rf, $senha){

        // URL da API para onde os dados serão enviados
        $url = getenv('SMEINTEGRACAO_API_URL') .'/api/AutenticacaoSgp/AlterarSenha';
        $token = getenv('SMEINTEGRACAO_API_TOKEN');

        // Dados do usuário a serem enviados
        $data = array(
            'Usuario' => $rf,
            'Senha' => $senha
        );

        // Inicia a sessão cURL
        $req = curl_init();

        // Envia dados como formulário
        curl_setopt($req, CURLOPT_URL, $url);
        curl_setopt($req, CURLOPT_POST, true);
        curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($data)); // Codifica os dados para o formato de formulário
        curl_setopt($req, CURLOPT_HTTPHEADER, array(
            'x-api-eol-key:' . $token,
            'Content-Type: application/x-www-form-urlencoded'
        ));
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
        // Executa a requisição e armazena a resposta
        $response = curl_exec($req);
     
        // Verifica se houve erro na requisição
        if(curl_errno($req)) {
            return array('resp' => curl_error($req), 'rf' => $rf);
        } else {
            return array('resp' => $response, 'rf' => $rf);
        }

        curl_close($req);

    }
    
}