<?php
/**
* Plugin Name: CoreSSO Integração API
* Plugin URI: https://amcom.com.br/
* Description: Integração do Login do WordPress com o CoreSSO.
* Version: 1.0
* Author: AMcom
* Author URI: https://amcom.com.br/
**/

function validate_dre($dre){
    switch ($dre) {
        case 'DIRETORIA REGIONAL DE EDUCACAO BUTANTA':
            return array(
                'dre' => 'dre-bt',
                'grupo' => 1693
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO CAMPO LIMPO':
            return array(
                'dre' => 'dre-cl',
                'grupo' => 1703
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO CAPELA DO SOCORRO':
            return array(
                'dre' => 'dre-cs',
                'grupo' => 1728
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO FREGUESIA/BRASILANDIA':
            return array(
                'dre' => 'dre-fb',
                'grupo' => 1729
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO GUAIANASES':
            return array(
                'dre' => 'dre-gn',
                'grupo' => 1730
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO IPIRANGA':
            return array(
                'dre' => 'dre-ip',
                'grupo' => 1731
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO ITAQUERA':
            return array(
                'dre' => 'dre-it',
                'grupo' => 1732
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO JACANA/TREMEMBE':
            return array(
                'dre' => 'dre-jt',
                'grupo' => 1733
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO PENHA':
            return array(
                'dre' => 'dre-pe',
                'grupo' => 1734
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO PIRITUBA/JARAGUA':
            return array(
                'dre' => 'dre-pi',
                'grupo' => 1735
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO SANTO AMARO':
            return array(
                'dre' => 'dre-sa',
                'grupo' => 1736
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO SAO MATEUS':
            return array(
                'dre' => 'dre-sma',
                'grupo' => 1737
            );
            break;

        case 'DIRETORIA REGIONAL DE EDUCACAO SAO MIGUEL':
            return array(
                'dre' => 'dre-smi',
                'grupo' => 1738
            );
            break;
        
        default:
            return $dre;
            break;
    }
}

// Substituir a autenticacao do WordPress
add_filter( 'authenticate', 'demo_auth', 10, 3 );

function busca_escola(){
    $api_url = getenv('SMEINTEGRACAO2_API_URL') . '/api/AutenticacaoVisitas/login';
    $api_token = getenv('SMEINTEGRACAO2_API_TOKEN');

    $curl = curl_init();
    curl_setopt_array($curl, array(
                    CURLOPT_URL => $api_url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => array('login' => $user->codigoRf),
                    CURLOPT_HTTPHEADER => array('x-api-eol-key: ' . $api_token)
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response); 
}

//     // Comente esta linha se você deseja recorrer a autenticacao do WordPress
//     // Util para momentos em que o servico externo esta offline
//     remove_action( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
//     remove_action( 'authenticate', 'wp_authenticate_email_password', 20, 3 );

//     return $user_wp;
// }
function demo_auth( $user, $username, $password ){
    // Verifica se o usuario e senha foram preenchidos
    if($username == '' || $password == '') return;

    // URL da API
    $api_url = getenv('SMEINTEGRACAO_API_URL') . '/api/v1/autenticacao';
    $api_token = getenv('SMEINTEGRACAO_API_TOKEN');

    // Conversao do body para JSON
    $body = wp_json_encode( array(
        "login" => $username,
        "senha" => $password,
    ) );

    $response = wp_remote_post( $api_url ,
            array(
                'headers' => array( 
                    'x-api-eol-key' => $api_token, // Chave da API
                    'Content-Type'=> 'application/json-patch+json'
                ),
                'body' => $body, // Body da requisicao
            ));

    if (is_wp_error($response)) {
        // Trata o erro, por exemplo, registrando uma mensagem no log.
        error_log('Erro na requisição: ' . $response->get_error_message());
        
        $login_page = home_url();	
        wp_redirect($login_page . '?request=noresponse');
        exit;
    
    }

    $user = json_decode($response['body']);

    if( $response['response']['code']  != 200 ) {
        // Caso nao encontre o usuario retorna o erro na pagina
        $user = new WP_Error( 'denied', __("ERRO: Usuário/senha incorretos") );

    } else if( $response['response']['code'] == 200 ) {
        //echo $user->codigoRf;
        
        // Verifica se tem o codigo RF e busca os dados do usuario
        if($user->codigoRf){
            
            $rf = $user->codigoRf;
            
            $countRf = strlen($rf);

            if($countRf == 20){
                $usuario = $rf;
                $api_url =  getenv('SMEINTEGRACAO_API_URL') . '/api/escolas/unidades-parceiras';
                $api_token = getenv('SMEINTEGRACAO_API_TOKEN');
                $response = wp_remote_post( $api_url, array(
                    'method'      => 'POST',                    
                    'headers' => array( 
                        'x-api-eol-key' => $api_token,
                        'Content-Type' => 'application/json-patch+json'
                    ),
                    'body' => '['.$rf.']',
                    )
                );
                
                if ( is_wp_error( $response ) ) {
                    //$error_message = $response->get_error_message();
                    //echo "Something went wrong: $error_message";

                    // Trata o erro, por exemplo, registrando uma mensagem no log.
                    error_log('Erro na requisição: ' . $response->get_error_message());
                            
                    $login_page = home_url();	
                    wp_redirect($login_page . '?request=noresponse');
                    exit;

                } else {
                    $user = json_decode($response['body']);                     
                    if(!$user){
                        echo $rf;
                        $api_url =  getenv('SMEINTEGRACAO_API_URL') . '/api/AutenticacaoSgp/' . $rf . '/dados';
                        $api_token = getenv('SMEINTEGRACAO_API_TOKEN');
                        $response = wp_remote_get( $api_url ,
                            array( 
                                'headers' => array( 
                                    'x-api-eol-key' => $api_token,							
                                )
                            )
                        );

                        if (is_wp_error($response)) {
                            // Trata o erro, por exemplo, registrando uma mensagem no log.
                            error_log('Erro na requisição: ' . $response->get_error_message());
                            
                            $login_page = home_url();	
                            wp_redirect($login_page . '?request=noresponse');
                            exit;
                        
                        }
    
                        $user = json_decode($response['body']);
                    }
                }
            } else {
                $api_url = getenv('SMEINTEGRACAO_API_URL') . '/api/AutenticacaoSgp/' . $user->codigoRf . '/dados';
                $api_token = getenv('SMEINTEGRACAO_API_TOKEN');
                $response = wp_remote_get( $api_url ,
                    array( 
                        'headers' => array( 
                            'x-api-eol-key' => $api_token,							
                        )
                    )
                );

                if (is_wp_error($response)) {
                    // Trata o erro, por exemplo, registrando uma mensagem no log.
                    error_log('Erro na requisição: ' . $response->get_error_message());
                    
                    $login_page = home_url();	
                    wp_redirect($login_page . '?request=noresponse');
                    exit;
                
                }

                $user = json_decode($response['body']); 
            }
                       
        }
              

        if($user->email){
            $email = $user->email;
        } elseif(is_array($user)) {
            $email = $user[0]->email;
        } else {
            $email = $rf . "@sme.prefeitura.sp.gov.br";
        }

        // Buscar todos os usuários com 'rf' do usuario que passou no login
        $args = array(
            'meta_key'     => 'rf',
            'meta_value'   => $username,
            'meta_compare' => '='
        );

        $user_query = new WP_User_Query($args);
        $all_users = $user_query->get_results();

        $api_id_map = [];

        // Agrupar usuários por valor de api_user_id
        foreach ($all_users as $user) {
            $api_id = get_user_meta($user->ID, 'rf', true);
            if (!$api_id) continue;

            if (!isset($api_id_map[$api_id])) {
                $api_id_map[$api_id] = [];
            }

            $api_id_map[$api_id][] = $user->ID;
        }

        // Filtrar apenas os grupos duplicados
        foreach ($api_id_map as $api_id => $user_ids) {
            if (empty($user_ids)) {
                continue;
            }

            $users = [];

            foreach ($user_ids as $user_id) {
                $user = get_userdata($user_id);
                if (!$user) continue;

                $last_login = get_user_meta($user_id, 'wp_last_login', true);
                $registered = strtotime($user->user_registered);
                $score = $last_login ? intval($last_login) : $registered;

                $users[] = [
                    'ID' => $user_id,
                    'email' => $user->user_email,
                    'last_login' => $last_login,
                    'registered' => $registered,
                    'score' => $score,
                ];
            }

            // Ordenar por score (mais recente primeiro)
            usort($users, fn($a, $b) => $b['score'] <=> $a['score']);

            $user_to_keep = array_shift($users); // mesmo se houver apenas 1 usuário

            // Sempre atualiza o email/nickname se estiver diferente do CoreSSO
            $novo_email = sanitize_email($email);
            if (is_email($novo_email) && $user_to_keep['email'] !== $novo_email) {
                wp_update_user([
                    'ID'         => $user_to_keep['ID'],
                    'user_email' => $novo_email,
                    'nickname'   => $novo_email,
                ]);
            }

            // Se houver duplicados, salva os que devem ser excluídos
            if (count($users) > 0) {
                update_option('duplicados_para_excluir_' . $user_to_keep['ID'], $users);
            }
        }        
        
        //exit;
        
                
        // Verifica se o usuario ja esta cadastrado no WordPress
        $userobj = new WP_User();
        $user_wp = $userobj->get_data_by( 'email', $email ); // Does not return a WP_User object :(
        if($user_wp->ID != 0){
            $user_wp = new WP_User($user_wp->ID); // Attempt to load up the user with that ID
        }
        // Verifica se o usuario esta com um email temporario cadastrado
        // email temporario corresponde a 'rf + @sme.prefeitura.sp.gov.br'
        // Se nao estiver cadastrado faz a criacao do usuario
        if( $user_wp->ID == 0 ) {
            
            // Caso nao queira adicionar o usuario no WordPress
            // descomente a linha abaixo
            //$user_wp = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );

            // Recebe o nome completo do usuario            
            $name = $user->nome;

            // Recebe o CPF
            $cpf = $user->cpf;

            // Divide o nome em Nome e Sobrenome
            $parts = explode(" ", $name);
            if(count($parts) > 1) {
                $firstname = array_shift($parts);
                $lastname = implode(" ", $parts);
            } else {
                $firstname = $name;
                $lastname = " ";
            }

            $escola = busca_escola();

            if($escola->grupos[0] == 'c8b2ebd2-924d-494d-8767-498b2a4ddf66'){
                if(str_contains($escola->dre, 'COCEU')){
                    $role = 'administrator';
                } else {
                    $role = 'editor';
                }
            } elseif($escola->grupos[0] == 'A57D7239-8CFC-48C1-80AE-BAC162E43B36') {
                $role = 'subscriber';
            } else {
                $role = 'editor';
            }

            $userdata = array( 'user_email' => $user->email,
                                'user_login' => $user->email,
                                'first_name' => $firstname,
                                'last_name' => $lastname,
                                'role' => $role,                              
                            );
            $new_user_id = wp_insert_user( $userdata ); // Um novo usuario sera criado


            
            $dre = validate_dre($escola->dre);
            if($role == 'editor'){
                // Inserir Grupo
                update_user_meta($new_user_id, "grupo", array($dre['grupo']) );
                update_user_meta($new_user_id, "_grupo", 'field_5f9843469209b');
            }
            

            $endereço = explode(', ', $escola->enderecoUe); // Separar o endereco
            $diretor = explode(' - ', $escola->nomeDiretor); // Separar o endereco

            //Informacoes Unidade Escolar (UE)
            update_user_meta($new_user_id, "dre", $dre['dre']); // Inserir DRE
            update_user_meta($new_user_id, "endereco_nome_da_ue", $escola->nomeUe); // Inserir nome da UE
            update_user_meta($new_user_id, "endereco_logradouro", $endereço[0]); // Inserir endereco da UE
            update_user_meta($new_user_id, "endereco_numero", $endereço[1]); // Inserir numero da UE
            update_user_meta($new_user_id, "endereco_bairro", $endereço[2]); // Inserir bairro da UE
            update_user_meta($new_user_id, "endereco_telefone", $escola->telefoneUe); // Inserir telefone UE
            update_user_meta($new_user_id, "endereco_nome_diretor", $diretor[1]); // Inserir diretor da UE
            update_user_meta($new_user_id, "endereco_email_diretor", $escola->emailDiretor); // Inserir email diretor da UE
            
            // Carregar as novas informações do usuário
            $user_wp = new WP_User ($new_user_id);
            
        }        

        if($rf && $countRf == 7){

            $api_url = getenv('SMEINTEGRACAO_API_URL') . '/api/Intranet/CarregarPerfisPorLogin/' . $user->codigoRf;
            $api_token = getenv('SMEINTEGRACAO_API_TOKEN');
            $response = wp_remote_get( $api_url ,
                array( 
                    'headers' => array( 
                        'x-api-eol-key' => $api_token,							
                    )
                )
            );

            if (is_wp_error($response)) {
                // Trata o erro, por exemplo, registrando uma mensagem no log.
                error_log('Erro na requisição: ' . $response->get_error_message());
                
                $login_page = home_url();	
                wp_redirect($login_page . '?request=noresponse');
                exit;
            
            }

            $userInfo = json_decode($response['body']);

            $cargo = $userInfo->cargos[0]->nome;
            $cargoSobre = $userInfo->cargosSobrePosto[0]->nome;
            $areaAtuacao = implode(", ", $userInfo->areasAtuacao);
            $local = $userInfo->unidadeLotacao->nomeUnidade;
            $localSobre = $userInfo->unidadeExercicio->nomeUnidade;
            
            if($user_wp->ID != 0){
                update_user_meta($user_wp->ID, "cargo_principal", $cargo);
                update_user_meta($user_wp->ID, "cargo_sobre", $cargoSobre);
                update_user_meta($user_wp->ID, "area_atuacao", $areaAtuacao);
                update_user_meta($user_wp->ID, "local", $local);
                update_user_meta($user_wp->ID, "local_sobre", $localSobre);
            }

        }

        // Se nao estiver cadastrado faz a criacao do usuario
        if( $user_wp->ID == 0 ) {
             
            // Caso nao queira adicionar o usuario no WordPress
            // descomente a linha abaixo
            //$user_wp = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );

            // Recebe o nome completo do usuario            
            $name = $user->nome;

            if($user->codigo){
                $codigo = $user->codigo;
            } elseif(is_array($user)) {
                $codigo = $user[0]->codigo;
            }

            if($user->email){
                $email = $user->email;
            } elseif(is_array($user)) {
                $email = $user[0]->email;
            } else {
                $email = $rf . "@sme.prefeitura.sp.gov.br";
            }

            if($user->nome){
                $nome = $user->nome;
            } elseif(is_array($user)) {
                $nome = $user[0]->nome;
            }

            if($codigo){

                $userdata = array( 'user_email' => $email,
                                    'user_login' => $email,
                                    'first_name' => $nome,                            
                                );
                $new_user_id = wp_insert_user( $userdata ); // Um novo usuario sera criado
                update_user_meta($new_user_id, "rf", $codigo);
                update_user_meta($new_user_id, "parceira", 1);

            } else {
                // Recebe o CPF
                $cpf = $user->cpf;

                // Divide o nome em Nome e Sobrenome
                $parts = explode(" ", $name);
                if(count($parts) > 1) {
                    $firstname = array_shift($parts);
                    $lastname = implode(" ", $parts);
                } else {
                    $firstname = $name;
                    $lastname = " ";
                }

                $userdata = array( 'user_email' =>$email,
                                    'user_login' =>$email,
                                    'first_name' => $firstname,
                                    'last_name' => $lastname,                                
                                );
                $new_user_id = wp_insert_user( $userdata ); // Um novo usuario sera criado
                update_user_meta($new_user_id, "rf", $username);
                if(strlen($username) != 6){
                    update_user_meta($new_user_id, "cpf", $cpf);
                    if($cargo)
                        update_user_meta($new_user_id, "cargo_principal", $cargo);
    
                    if($cargoSobre)
                        update_user_meta($new_user_id, "cargo_sobre", $cargoSobre);
    
                    if($areaAtuacao)
                        update_user_meta($new_user_id, "area_atuacao", $areaAtuacao);
    
                    if($local)
                        update_user_meta($new_user_id, "local", $local);
    
                    if($localSobre)
                        update_user_meta($new_user_id, "local_sobre", $localSobre);
                }

                if(strlen($username) == 11 || strlen($username) == 6){
                    update_user_meta($new_user_id, "parceira", 1);
                }
            }

            
            
            // Carregar as novas informações do usuário
            $user_wp = new WP_User ($new_user_id);
            
        } 

    }

    if(!$user_wp){

        // Verifique se o campo personalizado 'cpf_user' está definido como o nome de usuário
        $args = array(
            'meta_key'     => 'cpf_user',
            'meta_value'   => $username,
            'meta_compare' => '='
        );

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();

        // Se houver um usuário com o campo personalizado correspondente, tente autenticá-lo
        if (!empty($users)) {
            $user = $users[0];
            // Tenta autenticar o usuário
            $user_wp = wp_authenticate_username_password(null, $user->user_login, $password);
        } else {
            $user_wp = wp_authenticate_username_password(null, $username, $password);
        }
        
    }

    // Comente esta linha se você deseja recorrer a autenticacao do WordPress
    // Util para momentos em que o servico externo esta offline
    remove_action( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
    remove_action( 'authenticate', 'wp_authenticate_email_password', 20, 3 );

    return $user_wp;
}

#########################################################################################
// Criacao do shortcode de login
//function intranet_add_login_shortcode() {
	//add_shortcode( 'intranet-login-form', 'intranet_login_form_shortcode' );
//}

// funcao callbacl do shortcode
function intranet_login_form_shortcode() {
	
	// Se ja estiver conectado
    if (is_user_logged_in() && !is_admin()):
        echo "<h2>Você já está conectado!</h2>";
    else:
    // Inclui o formulario de login
    ?>
    	<div class='wp_login_form'>
			<?php
                // Mensagem de erro exibida na tela
				$page_showing = basename($_SERVER['REQUEST_URI']);

				if (strpos($page_showing, 'failed') !== false) {
					echo '<p class="error-msg"><strong>ERRO:</strong> Usuário e/ou senha inválidos.</p>';
				} elseif (strpos($page_showing, 'blank') !== false ) {
					echo '<p class="error-msg"><strong>ERRO:</strong> Usuário e/ou senha estão vazios.</p>';
				}
			
                $args = array(
                'redirect' => home_url(), // Apos login redireciona para a home
                'id_username' => 'user', // ID no input de usuario
                'id_password' => 'pass', // ID no input da senha
                );
				
                wp_login_form( $args ); // Inclui o formulario de login
                
            ?>

		</div>
<?php
    endif;
}

// Carrega a funcao do shortcode
//add_action( 'init', 'intranet_add_login_shortcode' );


#####################################################################################

// Direcionar o usuario da pagina de login do WordPress para uma pagina de login customizada
function goto_login_page() {
	global $page_id;
	$login_page = home_url();
	$page = basename($_SERVER['REQUEST_URI']);

	if( $page == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
		wp_redirect($login_page);
		exit;
	}
}
// Funcao desabilitada no momento, para habilitar descomente a linha abaixo
//add_action('init','goto_login_page');

// Se nao autenticar o usuario redireciona para o login novamente
// icluindo o parametro GET na URL
function login_failed() {	
    global $page_id;
	wp_redirect( home_url() . '/login/?login=failed' );
	exit;
}
// Verifica se nao esta na pagina de login do WordPress
if( $pagenow == 'wp-login.php' && isset($_POST['login_page']) ){
	add_action( 'wp_login_failed', 'login_failed' );
}

// Se usuario/senha estiver vazio redireciona para o login novamente
// icluindo o parametro GET na URL
function blank_username_password( $user, $username, $password ) {
	global $page_id;
	if( $username == "" || $password == "" ) {
		wp_redirect( home_url() . "/login/?login=blank");
		exit;
	}
}
// Verifica se nao esta na pagina de login do WordPress
if( $pagenow == 'wp-login.php' && isset($_POST['login_page']) ){
	add_filter( 'authenticate', 'blank_username_password', 1, 3);
}

// Se for acionado a funcao de Logout (sair) redireciona o usuario para a pagina de login
function logout_page() {
	global $page_id;
	wp_redirect( home_url() . "/login/?login=false" );
	exit;
}
add_action('wp_logout', 'logout_page');

// Inclui um input oculto no formulario de login personalizado
// Para que seja validado o usuario via API e nao pelo WordPress
add_filter('login_form_middle','my_added_login_field');
function my_added_login_field(){
     //Output your HTML
     $additional_field = '<div class="login-custom-field-wrapper"">
        <input type="hidden" value="1" name="login_page"></label>
     </div>';

     return $additional_field;
}

// Verifica se esta na pagina de Login do WordPress
// para validar o usuario pelo WordPress e NAO pela API
add_action( 'login_init', 'wpse8170_login_init' );
function wpse8170_login_init() {
	global $pagenow;
	if( $pagenow == 'wp-login.php' && !isset($_POST['login_page']) ){
		remove_filter( 'authenticate', 'demo_auth' );
		remove_filter( 'authenticate', 'blank_username_password');
	}    
}
