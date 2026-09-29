<?php

namespace App\Classes;

class UsuarioSistemaCore {

    // Definição do slug do menu pai para reutilização nos submenus
    private $menu_slug = 'usuario-sistema-core';

    public function __construct() {
        // Insere o CSS da página
        add_action( 'admin_enqueue_scripts', array( $this, 'style_usuario' ));
        add_action( 'admin_enqueue_scripts', array( $this, 'script_usuario' ));
        // Dispara o hook correto para registrar menus de administração
        add_action( 'admin_menu', array( $this, 'criar_menus_admin' ) );
        //Gera link de remoção
        add_action( 'admin_post_preparar_exclusao_nativa',  array( $this, 'processar_ponte_exclusao_nativa') );

        // Intercepta especificamente o carregamento interno da tela de usuários do WordPress
        add_action( 'load-users.php', array( $this, 'forcar_retorno_usuario_sistema_core') );
        add_action('wp_ajax_verificar_usuario', array( $this, 'verificar_usuario_existente') );
        
        add_action('wp_ajax_processa_cadastro_usuario_ext', array( $this,  'processa_cadastro_usuario_ext'));
    }

    /**
     * Registra o menu principal e seus respectivos submenus
     */
    public function criar_menus_admin() {
        // 1. Cria o Menu Principal (Pai)
        add_menu_page(
            __( 'Gestão de Usuários', 'texto-dominio' ),  // Título da página
            __( 'Gestão de Usuários', 'texto-dominio' ),  // Título no Menu Lateral
            'manage_options',                              // Capacidade necessária (Ex: Administrador)
            $this->menu_slug,                              // Slug único do menu
            array( $this, 'renderizar_pagina_principal' ), // Método que renderiza o HTML
            'dashicons-buddicons-buddypress-logo',                       // Ícone do menu (Dashicon)
            20                                             // Posição no menu lateral
        );
    }

    /**
     * Renderiza o HTML da Página Principal
     */
    public function renderizar_pagina_principal() {
        
        global $wp_roles;

        $perfis_disponiveis = isset( $wp_roles->roles ) ? $wp_roles->roles : array();

        // 1. Captura o termo de busca enviado pelo formulário (se houver)
        $termo_busca = isset( $_POST['busca_usuario'] ) ? sanitize_text_field( $_POST['busca_usuario'] ) : '';
        $perfil_filtro = isset( $_POST['filtro_perfil'] ) ? sanitize_text_field( $_POST['filtro_perfil'] ) : '';
        
        // 2. Configura os argumentos da consulta
        $args = array(
            'role'         => $perfil_filtro, // Filtra pelo perfil de Assinante
            'search'       => '*'.$termo_busca.'*',     // Busca pelo nome (os asteriscos servem como curinga)
            'search_columns' => array( 'user_login', 'user_nicename', 'display_name' ), // Restringe a busca apenas aos campos de nome
            'orderby'      => 'display_name',
            'order'        => 'ASC'
        );

        $usuarios = get_users( $args );

        $template = file_get_contents( VIEWS_DIR . '/layouts/usuarios/form-usuarios.html');
        $template = str_replace( '{TITULO-PAGINA}',    'Gestão de Acessos', $template );
        $template = str_replace( '{SUBTITULO-PAGINA}', 'Pesquise, adicione, edite ou remova o acesso de servidores e não servidores à platafomra Rolê Agroecológico', $template );       
        $template = str_replace( '{LINK-FORM}',        $_SERVER["REQUEST_URI"], $template );
        $template = str_replace( '{LINK-LIMPAR}',      $_SERVER["REQUEST_URI"], $template );
        $template = str_replace( '{TERMO-BUSCA}',      esc_attr( $termo_busca ), $template );

        $template_select = '';

        foreach ( $perfis_disponiveis as $key_role => $dados_role ) {
            $template_opt = file_get_contents( VIEWS_DIR . '/layouts/usuarios/options-select.html');
            $template_opt = str_replace( '{VALOR}', esc_attr( $key_role ), $template_opt);
            $template_opt = str_replace( '{SELECAO}', selected( $perfil_filtro, $key_role ), $template_opt);
            $template_opt = str_replace( '{OPCAO}', esc_html(translate_user_role( $dados_role['name'] )), $template_opt);
            $template_select .= $template_opt;
        }

        $template = str_replace( '{OPTIONS-SELECT}', $template_select, $template );
        $template_tab = file_get_contents( VIEWS_DIR . '/layouts/usuarios/tab-usuario.html');

        if ( !empty( $usuarios ) ) {
           
            $template_tab_corpo = '';
            $i = 0;
            foreach ( $usuarios as $usuario ) {
                // Obtém as funções do usuário (roles)
                $urlRemocao = '';
                $geraUrlRemocao = $this->gerar_url_remocao_usuario_custom( $usuario->ID );
            
                $funcoes = implode( ', ', $usuario->roles );
                $apenasNumeros = preg_replace('/\D/', '', esc_html($usuario->user_login));
                if(is_numeric($apenasNumeros)){
                    $usu = $apenasNumeros;
                } else {
                    $usu = esc_html($usuario->user_login);
                }

                $template_tab_body = file_get_contents( VIEWS_DIR . '/layouts/usuarios/tab-body.html');
                $template_tab_body = str_replace( '{NOME-USUARIO}', esc_html( $usuario->display_name ), $template_tab_body);
                $template_tab_body = str_replace( '{TIPO-USUARIO}', $this->exibe_badges($usu), $template_tab_body);
                $template_tab_body = str_replace( '{LOGIN-USUARIO}', $this->verifica_tipo_login( esc_html( $usuario->user_login ) ), $template_tab_body);
                $template_tab_body = str_replace( '{EMAIL-USUARIO}', esc_html( $usuario->user_email ), $template_tab_body);
                $template_tab_body = str_replace( '{FUNCOES-USUARIO}', esc_html( translate_user_role( $funcoes ) ), $template_tab_body);
                $template_tab_body = str_replace( '{LINK-EDICAO}', site_url().'/wp-admin/user-edit.php?user_id='.esc_html( $usuario->ID ), $template_tab_body);
                
                // Evita exibir o link de exclusão para o próprio administrador logado
                if ( get_current_user_id() !== $usuario->ID ) {
                    $urlRemocao = esc_url( $geraUrlRemocao );
                } 

                $template_tab_body = str_replace( '{LINK-REMOCAO}', $urlRemocao, $template_tab_body);
                $template_tab_body = str_replace( '{FECHA-MODAL}', 'fechar-modal'.$i, $template_tab_body);
                $template_tab_corpo .= $template_tab_body;
                $i++;
            }
            
            $template = str_replace( '{QTD-USUARIO}', $i, $template ); 
            
            // wp_nonce_field( 'cadastrar_direto_nonce', 'usuario_nonce_field' );
            $template_detalhe = file_get_contents( VIEWS_DIR . '/layouts/usuarios/form-detalhes-usuario.html');
            $template_externo = file_get_contents( VIEWS_DIR . '/layouts/usuarios/form-usuario-externo.html');
            $template_externo = str_replace( '{OPTIONS-SELECT}', $template_select, $template_externo );

            // $template_form = str_replace( '{OPTIONS-SELECT}', $template_select, $template_form );
            $template_tab = str_replace( '{DETALHES-SERVIDOR}', $template_detalhe, $template_tab );
            $template_tab = str_replace( '{FORMULARIO-CAD}', $template_externo, $template_tab );
            

            $template_tab = str_replace( '{TAB-CORPO}', $template_tab_corpo, $template_tab );

        } else {
            $template_tab = str_replace( '{TAB-CORPO}','
            <tr style="border-bottom: 1px solid #dcdcde;">
                <td style="padding: 10px;" colspan="5">Nenhum usuário encontrado.</td>
            </tr>', $template_tab );
        }

        $template .= $template_tab;
        echo $template;
    }

    public function style_usuario() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'usuario-sistema-core' ) {
            wp_enqueue_style('style-usuario-core', get_template_directory_uri(). '/src/Views/assets/css/style_usuario_core.css', array(), '1.0.0');
        }
    }

    public function script_usuario() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'usuario-sistema-core' ) {
            wp_enqueue_script('script-usuario-core', get_template_directory_uri(). '/src/Views/assets/js/script_usuario_core.js', array(), '1.0.2');
            wp_localize_script('script-usuario-core', 'adminAjax', array(
                'ajaxurl'   => admin_url('admin-ajax.php'),
                'seguranca' => wp_create_nonce('nonce_seguranca_cadusu') // Só terá o token real se for Admin
            ));
        }
    }

    public function exibe_badges($login){
        if(is_numeric($login) && strlen($login) === 7){
          return '<span class="badge badge-primary">Servidor</span>';   
        } else {
          return '<span class="badge badge-success">Não Servidor</span>';
        }
    }

    public function verifica_tipo_login($login){
        $retornaNum = preg_replace('/\D/', '', $login);
        if(is_numeric($retornaNum) && strlen($retornaNum) === 7){
          return 'RF '. $login;
        } else if(is_numeric($retornaNum) && strlen($retornaNum) === 11){
            $segundoBloco = substr($retornaNum, 3, 3);
            $digitos = substr($retornaNum, 9, 2);

            return 'CPF ***.'.$segundoBloco.'.***-'.$digitos;
        } else {
            return $login;
        }
    }

    // 1. O gerador de URL para a sua tabela personalizada
    public function gerar_url_remocao_usuario_custom( $user_id ) {
        // 1. O WordPress exige o Nonce para a ação em lote 'bulk-users' se viermos de telas customizadas
        $nonce_nativo = wp_create_nonce( 'bulk-users' );
        // 2. Montamos o link apontando para o próprio ecossistema admin.php passando as variáveis que o core espera
        $url_final = add_query_arg(
            array(
                'action'   => 'delete',          // Ação de deletar
                'users'    => array( $user_id ), // O ID precisa ir como array para simular seleção
                '_wpnonce' => $nonce_nativo,     // Token de segurança aceito pelo users.php
            ),
            admin_url( 'users.php' )             // O destino final de processamento
        );
        // 3. Adiciona o retorno para a sua página customizada após a conclusão
        $url_de_retorno = menu_page_url( 'usuario-sistema-core', false );
        $url_final      = add_query_arg( '_wp_http_referer', urlencode( $url_de_retorno ), $url_final );
        return $url_final;
    }

     
    public function forcar_retorno_usuario_sistema_core() {
        
        // Se a URL contiver 'delete_count', significa que uma exclusão em lote acabou de acontecer
        if ( isset( $_GET['delete_count'] ) ) {
            // Capturamos o referer injetado pelo formulário ou link original
            $referer = isset( $_REQUEST['_wp_http_referer'] ) ? $_REQUEST['_wp_http_referer'] : '';
            // Caso o referer tenha sumido devido à limpeza do Core, usamos uma verificação de sessão (opcional) 
            // ou aplicamos o desvio global se você usa exclusivamente a sua tela customizada para gerenciar
            if ( empty( $referer ) || false !== strpos( $referer, 'usuario-sistema-core' ) ) {
                $quantidade = intval( $_GET['delete_count'] );
                // Monta a URL de volta para a sua página com os dados de sucesso
                $url_retorno = admin_url( 'admin.php?page=usuario-sistema-core&update=del&count=' . $quantidade );
                // Executa o desvio imediato
                wp_redirect( $url_retorno );
                exit;
            }
        }
    }

    public function verificar_usuario_existente() {
        // Pega e limpa o valor enviado pelo JavaScript
        $usuario = sanitize_text_field($_POST['usuario']);

        // Tenta encontrar o ID pelo username primeiro
        $user_id = username_exists($usuario);

        // Se não encontrou pelo username, tenta pelo e-mail
        if (!$user_id) {
            $user_id = email_exists($usuario);
        }

        // Se $user_id não for falso, significa que o usuário existe
        if ($user_id) {
            wp_send_json_success(array(
                'existe'  => true,
                'id'      => $user_id, // Retorna o ID encontrado
                'url'     => site_url().'/wp-admin/user-edit.php?user_id='.$user_id,
                'mensagem' => 'Usuário encontrado.'
            ));
        } else {
            wp_send_json_error(array(
                'existe'  => false,
                'id'      => null,
                'mensagem' => 'Usuário não existe.'
            ));
        }
    }


    
    public function processa_cadastro_usuario_ext() {

        // Verificar o nonce de segurança
        check_ajax_referer('nonce_seguranca_cadusu', 'seguranca');


        $nome = sanitize_user($_POST['nome']);
        $email    = sanitize_email($_POST['email']);
        $cpf     = sanitize_text_field($_POST['cpf']);
        $password = $_POST['password'];
        $role     = sanitize_text_field($_POST['role']);

        if (empty($nome) || empty($email) || empty($password) || empty($cpf)) {
            wp_send_json_error(['message' => 'Preencha todos os campos.']);
        }

        if (username_exists($cpf) || email_exists($email)) {
            wp_send_json_error(['message' => 'Usuário ou e-mail já cadastrado.']);
        }

        // Array com os dados do usuário para o wp_insert_user
        $user_data = array(
            'user_login' => $cpf,
            'display_name' => $nome,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => $role // Define o perfil aqui
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Usuário cadastrado com sucesso!']);
    }
    
}
