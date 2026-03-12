<?php
#### DEFINES DO TEMA
define('URL_PLUGIN', plugin_dir_url(__FILE__) );
define('PATH_PLUGIN', plugin_dir_path(__FILE__) );
define('URL_IMG_THEME', get_template_directory_uri() . '/src/Views/assets/img');
define('CONFIG_DIR', get_template_directory() . '/config');
define('VIEWS_DIR', get_template_directory() . '/src/Views');
define('CLASSES_PATH', get_template_directory_uri() . '/src/Classes');
define('MODELS_PATH', get_template_directory_uri() . '/src/Models');
define('VIEWS_PATH', get_template_directory_uri() . '/src/Views');

#### INCLUDES DE CONFIGURAÇÃO
require_once get_template_directory(). '/config/admin.php';
require_once get_template_directory(). '/config/helper.php';
require_once get_template_directory(). '/config/request-endpoints.php';
require_once get_template_directory(). '/config/middlewares.php';
require_once get_template_directory(). '/config/register-usuario.php';

#### AUTOLOAD
require plugin_dir_path(__FILE__). 'vendor/autoload.php';

#### CARREGAMENTO DE CSS E JS
new App\Classes\AutoCarregamento();
#### CARREGAMENTO DOS POSTSTYPES
new App\Classes\Core();
#### CARREGAMENTO INTEGRAÇÃO CORESSO
new App\Classes\IntegracaoCoreSSO();
#### CARREGAMENTO DA CLASSE DE NOTIFICAÇÕES E ENVIO DE EMAILS DA INSCRIÇÃO
new App\Controllers\AgendamentoNotificacoesController();
#### CARREGAMENTO DA CLASSE DE CONTROLE DAS INSCRIÇÕES
new App\Controllers\AgendamentoController();
#### CARREGAMENTO DA CLASSE DE DOS TRANSPORTADORES
new App\Controllers\TransporteController();

#### CARREGAMENTO SHORTCODE ACF
add_action('init', function() {
    require_once(CONFIG_DIR . '/acf-field-shortcode-display.php');
    acf_register_field_type('ACF_Field_Shortcode_Display');
});

function obter_disp_locais( $request ) {
    $idPost = absint((int) $request->get_param('idPost'));
    $ids = $_POST['ids'];
    
    set_transient('ids_temp_selecao_roteiros_'.$idPost, $ids);
    wp_send_json(array("res"=>$ids));
    wp_die();
}

#### Função de configuração padrão do tema
function configuracoes_tema (){
    // Registrando MENUS
    register_nav_menus(
        array(
            'main_menu_role' => 'Main Menu',
            'main_menu_role_top_right' => 'Sub Menu Principal',
            'main_menu_top_left' => 'Top Left Menu',
            'footer_menu' => 'Footer Menu'
        )
    );
    add_theme_support('post-thumbnails');
    add_theme_support('post-formats', array('video', 'image'));
}
add_action('after_setup_theme','configuracoes_tema', 0 );

#### HABILITA O MENU DE CATEGORIAS E TAGS PARA O POSTTYPE ROTEIRO
function add_taxonomies_to_custom_post_roteiro() {

    register_taxonomy_for_object_type( 'category', 'post_roteiro' );
    register_taxonomy_for_object_type( 'post_tag', 'post_roteiro' );
    register_taxonomy_for_object_type( 'tax_up_distritos', 'post_roteiro' );
    register_taxonomy_for_object_type( 'tax_up_regioes', 'post_roteiro' );
    register_taxonomy_for_object_type( 'tax_up_distritos', 'post_roteiro' );
    register_taxonomy_for_object_type( 'tax_up_periodos-de-oferta', 'post_roteiro' );
    register_taxonomy_for_object_type( 'tax_up_disponibilidade-semana', 'post_roteiro' );

}
add_action( 'init', 'add_taxonomies_to_custom_post_roteiro' );

#### INCLUDE TEMPLATE POSTTYPE ROTEIROS
require_once VIEWS_DIR .'/shortcodes/admin-roteiros.php';

#### INCLUDE TEMPLATE POSTTYPE INSCRIÇÕES
require_once VIEWS_DIR .'/shortcodes/admin-inscricoes.php';


// Registering custom post status
add_action('init','custom_status');
function custom_status(){
    if (is_admin() && get_post_type( get_the_ID() ) == 'post_inscricao') {
        $makepublic = true;
    } else {
        $makepublic = false;
    }
    register_post_status( 'suspended', array(
        'label'                     => _x( 'suspended', 'Status General Name', 'myadvert' ),
        'public'                    => $makepublic,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Suspended <span class="count">(%s)</span>', 'Suspended <span class="count">(%s)</span>' )
    ));
}


// Adiciona um item externo ao final de um menu específico
function adicionar_item_externo_ao_menu( $items, $args ) {
    $novo_item  = '';
    // Verifica se é o menu desejado (use o 'theme_location' configurado no seu tema)
    if ( $args->theme_location === 'main_menu_role' && is_user_logged_in() ) {

        $user = wp_get_current_user();
        $unidadeLotacao = get_user_meta($user->ID, 'unidade_locacao');
        if(isset($unidadeLotacao) && isset($unidadeLotacao[0]['nomeUnidade']) != ''){
            $nomeUsu = retornaTextoReduzido($unidadeLotacao[0]['nomeUnidade'], 15);
        } else {
            $nomeUsu = $user->display_name;
        }

        //Adiciona no menu o link para Agendamentos e Lista de Presença
        if(user_can( get_current_user_id(), 'pg_agendamento_lista_presenca' )){
            $novo_item .= '<li class="menu-item menu-item-agendamento">';
            $novo_item .= '<a href="' . esc_url( site_url('/') ) . '">Início</a>';
            $novo_item .= '</li>';

            $novo_item .= '<li class="menu-item menu-item-agendamento">';
            $novo_item .= '<a href="' . esc_url( site_url('/agendamento-lista-presenca/') ) . '">Lista de Presença</a>';
            $novo_item .= '</li>';
        }

        $novo_item .= '&nbsp;&nbsp;<li class="menu-item menu-item-has-children menu-item-usuario">';
        $novo_item .= '<a href="#" class="menu-usuario-link"><i class="fa fa-user-circle" aria-hidden="true"></i> ';
        $novo_item .= '<span class="nome-usuario">' . $nomeUsu . '</span>';
        $novo_item .= '<span class="seta-dropdown">▼</span>';
        $novo_item .= '</a>';

        $novo_item .= '<ul class="sub-menu">';
        $novo_item .= '<li class="menu-item"><a href="' . esc_url( site_url('/meu-perfil') ) . '">Meu Perfil</a></li>';
        $novo_item .= '<li class="menu-item"><a href="' . esc_url( wp_logout_url( home_url() ) ) . '">Sair</a></li>';
        $novo_item .= '</ul>';
        $novo_item .= '</li>';

    } else if($args->theme_location === 'main_menu_role' && !is_user_logged_in()){
        $novo_item .= '<li class="menu-item menu-item-login">';
        $novo_item .= '<i class="fa fa-user-circle" aria-hidden="true"></i> <a href="' . esc_url( site_url('/login') ) . '">Fazer Login</a>';
        $novo_item .= '</li>';
    } 
    // Adiciona ao final do menu
    $items .= $novo_item;

    if($args->theme_location === 'main_menu_role' && user_can( get_current_user_id(), 'pg_agendamento_lista_presenca' )){
        $items = $novo_item;
    }

    return $items;
}
add_filter( 'wp_nav_menu_items', 'adicionar_item_externo_ao_menu', 10, 2 );

function permissao_funcoes_personalizadas() {
    add_role(
        'lista_presenca', // 1. Nome interno (slug)
        'Lista de presença', // 2. Nome de exibição
        array( // 3. Array de capacidades (opcional)
            'read' => true, // Permite ao usuário ler conteúdo
            'pg_agendamento_lista_presenca' => true, // Permite ver a lista dos agendamentos e a lista de presença
            'acessar_lista_de_presenca' => true
        )
    );
}
add_action( 'init', 'permissao_funcoes_personalizadas' );




