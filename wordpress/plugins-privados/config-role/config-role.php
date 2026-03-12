<?php 
/*
Plugin Name: Configurações do Portal Roleagroecológico
Plugin URI: https://amcom.com.br/
Description: Configurações do Portal Role Agroecológico
Version: 1.0
Author: AMcom
Author URI: https://amcom.com.br/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Altera o esquema de cores do painel admin
function registrar_esquema_admin_role() {
    wp_admin_css_color(
        'role',
        'Rolê Agroecologico',
        plugin_dir_url(__FILE__) . 'assets/css/colors.css',
        ['#2e7d32', '#f9a825', '#ffe082', '#f57f17']
    );
}

add_action('admin_init', 'registrar_esquema_admin_role');