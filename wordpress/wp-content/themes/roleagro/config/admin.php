<?php

// Salva o ID do usuário que realizou a última modificação no Post
function salva_autor_modificacao( $post_ID, $post_after, $post_before ) {
    if ( $post_after->post_modified !== $post_before->post_modified ) {
        update_post_meta( $post_ID, '_last_modified_author', get_current_user_id() );
    }
}
add_action( 'post_updated', 'salva_autor_modificacao', 10, 3 );


// Cria a coluna de última modificação na página de listagem do post
function adicionar_coluna_ultima_modificacao( $colunas ) {
    $colunas['last_modified'] = 'Última Modificação';
    return $colunas;
}
add_filter( 'manage_edit-post_up_columns', 'adicionar_coluna_ultima_modificacao' );

// Preenche a coluna com as informações da última modificação no post
function exibir_dados_coluna_ultima_modificacao( $coluna, $post_id ) {
    if ( $coluna === 'last_modified' ) {
        $ultima_modificacao = get_post_modified_time( 'd/m/Y à\s H:i', false, $post_id );
        $user_id = get_post_meta( $post_id, '_last_modified_author', true );

        if  ($user_id ) {

            $user_info = get_userdata( $user_id );
            echo esc_html( "{$ultima_modificacao} por {$user_info->display_name}" );

        } else {
            echo esc_html( $ultima_modificacao );
        }
    }
}
add_action( 'manage_post_up_posts_custom_column', 'exibir_dados_coluna_ultima_modificacao', 10, 2 );

// Adiciona tamanhos personalizados para as imagens
add_action('after_setup_theme', function() {
    add_image_size( 'slider-size', 635, 395, true ); 
});

// Oculta os campos não utilizados no cadastro de taxonomias
add_action('admin_head', function () {
    $screen = get_current_screen();
    $taxonomias_exluidas = ['category', 'post_tag'];

    if ( !in_array( $screen->taxonomy, $taxonomias_exluidas ) ) {
        echo '<style>
            .term-slug-wrap,
            .term-parent-wrap { display: none !important; }
        </style>';
    }
});