<?php
/*
Plugin Name: Grupos Editores
Description:  Plugin Criado para controlar os editores dos portais
Author: Felipe Viana
Version: 1.0.0
Author URI: https://www.amcom.com.br/
*/


// Post Type Grupos
function wporg_custom_post_type() {
    register_post_type('editores_portal',
        array(
            'labels'      => array(
                'name'          => __( 'Grupos', 'textdomain' ),
                'singular_name' => __( 'Grupo', 'textdomain' ),
            ),
            'public'      => true,
            'has_archive' => true,
			'rewrite'     => array( 'slug' => 'editores_portal' ), // my custom slug
			'capabilities' => array(
				'edit_post'          => 'add_grupo',
				'read_post'          => 'add_grupo',
				'delete_post'        => 'add_grupo',
				'edit_posts'         => 'add_grupo',
				'edit_others_posts'  => 'add_grupo',
				'delete_posts'       => 'add_grupo',
				'publish_posts'      => 'add_grupo',
				'read_private_posts' => 'add_grupo'
			),
        )
    );
}

add_action('init', 'wporg_custom_post_type');

// Controle de paginas permitidas
function wpse_user_can_edit( $user_id, $page_id ) {

    // ID da pagina corrente da lista
    $page = get_post( $page_id );
 	
    // pega as informacoes do usuario logado
    $user = wp_get_current_user($user_id);

    $post_author_id = get_post_field( 'post_author', $page_id );

    if($post_author_id == $user_id){
        return true;
    }

    // usuarios que ficam foram da regra
	$allowed_roles = array( 'editor', 'administrator' );
	if ( array_intersect( $allowed_roles, $user->roles ) ) {
        // se estiverem na lista todas as paginas sao permitidas para edicao
        return true;
    }

    // pega o grupo que o usuario pertence
    $variable = get_field('grupo', 'user_' . $user_id);
    $todasPaginas = array();

    // verifica se esta liberado para editar todas paginas
    if($variable && $variable != ''){
        foreach($variable as $permitido){
            $todasPaginas[] = get_field('todas_as_paginas', $permitido);            
        }
    }

    $liberar = 1;

	if(in_array($liberar, $todasPaginas)){
		return true;
	} else {	
        
        $permitidas = array();

        if($variable && $variable != ''){
            foreach($variable as $permitido){
                $permitidas[] = get_field('selecionar_paginas', $permitido);
                $permitidas[] = get_field('contatos_sme', $permitido);
            }
        }
        
        // pega as paginas permitidas para edicao pelo grupo
        $pages = array_flatten($permitidas);
        $pages = array_unique($pages);   

        // se a pagina corrente esta na lista de paginas do grupo libera para edicao
        if( in_array($page_id, $pages) ){
			return true;
		} else {
			return false;
		}
	} 
	
 }

 //
 add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) {

    global $post;
    
    if (is_object($post) && $post->post_type == 'attachment'){
        return $caps;
    }

    // capability atribuida
    $to_filter = [ 'edit_post', 'delete_post', 'edit_page', 'delete_page', 'edit_contato', 'delete_contato' ];

    // If the capability being filtered isn't of our interest, just return current value
    if ( ! in_array( $cap, $to_filter, true ) ) {
        return $caps;
    }

    // First item in $args array should be page ID
    if ( ! $args || empty( $args[0] ) || ! wpse_user_can_edit( $user_id, $args[0] ) ) {
        // User is not allowed, let's tell that to WP
        return [ 'do_not_allow' ];
    }
    // Otherwise just return current value
    return $caps;

}, 10, 4 );