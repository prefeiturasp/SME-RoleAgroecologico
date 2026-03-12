<?php
/**
 * This file aims to centralize all the helper functionalities
 *
 * @file helper.php
 * @package setup
 */

/**
 * Retorna uma categoria aleatória de um determinado post
 *
 * @param int|null $postId ID do post current.
 * @param string   $taxonomy slug da categoria.
 *
 * @return WP_Term|null
 */
function _theme_get_random_category( ?int $postId, string $taxonomy = 'category' ) {
	if ( null === $postId ) {
		global $post;
		$postId = $post->ID;
	}

	$categories = get_the_terms( $postId, $taxonomy );
	if ( !empty( $categories ) ) {

		$totalCategories = count( $categories ) > 0 ? count( $categories ) - 1 : count( $categories );
		$randomCategory  = $categories[ wp_rand( 0, $totalCategories ) ];

		return isset( $randomCategory ) ? $randomCategory : null;
	}

	return null;
}

/**
 * Retorna os post_type relacionados pela taxonomy
 *
 * @param int    $postId ID do post current.
 * @param int    $perPage Quantidade de itens por página.
 * @param string $taxonomy Categoria (Opcional).
 * @return int[]|WP_Post[]
 */
function _theme_get_posts_relatives( int $postId, int $perPage = 5, string $taxonomy = '' ) {

	$categories = get_the_terms( $postId, $taxonomy );
	$category_ids = wp_list_pluck( $categories, 'term_id' );
	$query_args = [
		'post_type'      => get_post_type( $postId ),
		'posts_per_page' => $perPage,
		'post__not_in'   => [ $postId ],
	];

	if ( $taxonomy ) {
		$query_args['tax_query'] = [
			'taxonomy' => $taxonomy,
			'field' => 'term_id',
			'terms' => $category_ids
		];
	}

	$query = new WP_Query( $query_args );

	return $query->get_posts();
}

/**
 * Ativa alguns features do WordPress
 */
function _theme_setup() {
	
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );

	register_nav_menus(
		[
			'main'   => esc_html__( 'Principal' ),
			'footer' => esc_html__( 'Rodapé' ),
		]
	);

}
add_action( 'after_setup_theme', '_theme_setup' );

/**
 * Gera URL de compartilhamento nas redes sociais
 *
 * @param string   $socialNetwork Nome da rede social.
 * @param int|null $postId ID do post current.
 *
 * @return string
 */
function _theme_social_share( string $socialNetwork, ?int $postId ): string {
	if ( null === $postId ) {
		global $post;
		$postId = $post->ID;
	}

	$baseUrlFacebook = 'https://www.facebook.com/sharer.php?u=';
	$socialNetworks  = [
		'facebook' => $baseUrlFacebook . get_permalink( $postId ) . '&t=' . rawurlencode( get_the_title( $postId ) ),
		'twitter'  => 'https://twitter.com/intent/tweet?text=' . rawurlencode( get_permalink( $postId ) ),
		'whatsapp' => 'https://wa.me/?text=' . get_permalink( $postId ),
		'telegram' => 'https://t.me/share/url?url=' . get_permalink( $postId ),
		'linkedin' => 'https://www.linkedin.com/cws/share?url=' . get_permalink( $postId ),
	];

	if ( ! isset( $socialNetworks[ $socialNetwork ] ) ) {
		return '';
	}

	return $socialNetworks[ $socialNetwork ];
}


/**
 * Gera URL para Chat do WhatsApp
 *
 * @param string $phoneNumber Número do telefone com DDD.
 * @param string $text Mensagem que será enviada no chat.
 * @return string
 */
function _theme_get_whatsapp_chat( string $phoneNumber, string $text ): string {
	$phoneNumber = preg_replace( '/[^0-9]/', '', $phoneNumber );
	return "https://api.whatsapp.com/send?phone={$phoneNumber}&text={$text}";
}

/**
 * Verifica a validade de um nonce enviado via formulário (POST).
 *
 * Essa função é uma camada de abstração para `wp_verify_nonce()`, que permite controlar
 * o comportamento em caso de falha (matar a execução com `wp_die()` ou apenas retornar false).
 *
 * @param string $field        O nome do campo (input hidden) que contém o nonce.
 * @param string $action       A ação associada ao nonce (deve ser igual ao usado na geração com wp_nonce_field()).
 * @param bool   $die_on_fail  Se verdadeiro, finaliza com wp_die() caso o nonce seja inválido ou ausente.
 *
 * @return bool Retorna true se o nonce for válido, ou false se inválido (caso $die_on_fail seja false).
 *
 */
function _theme_verify_nonce( string $field, string $action, bool $die_on_fail = true ): bool {

	if ( empty( $_POST[ $field ] ) ) {
		if ( $die_on_fail ) {
			wp_die( 'Nonce ausente.', 'Erro de segurança', 403 );
		}
		return false;
	}

	if ( !wp_verify_nonce( $_POST[ $field ], $action ) ) {
		if ( $die_on_fail ) {
			wp_die( 'Nonce inválido ou expirado.', 'Erro de segurança', 403 );
		}
		return false;
	}

	return true;
}

function _theme_formatar_conteudo_texto ( $value ) {
    // Remove parágrafos vazios do valor do campo
    $value = preg_replace( '/&nbsp;/', '<span class="nbsp"></span>', $value );
    return $value;
}

function _theme_show_pagination( array $args ) {
	$query = new WP_Query( $args );

	$maxPage = 99999;
	$pages   = paginate_links(
		[
			'base'      => str_replace( $maxPage, '%#%', esc_url( get_pagenum_link( $maxPage ) ) ),
			'format'    => '?paged=%#%',
			'current'   => max( 1, get_query_var( 'paged' ) ),
			'total'     => $query->max_num_pages,
			'type'      => 'array',
			'prev_next' => true,
			'prev_text' => __( '<i aria-hidden="true" class="fas fa-fw fa-chevron-left"></i>' ),
			'next_text' => __( '<i aria-hidden="true" class="fas fa-fw fa-chevron-right"></i>' ),
		]
	);

	$output = '';
	if ( is_array( $pages ) ) {
		$output .= '<ul class="pagination">';
		foreach ( $pages as $page ) {
			$output .= "<li class=\"pagination__number\">{$page}</li>";
		}
		$output .= '</ul>';
	}
	wp_reset_postdata();

	return $output;
}

/**
 * Adiciona um script no footer que vai inserir uma variável js com uma URL
 * que será utilizada para requisições AJAX
 *
 * phpcs:disabled WordPress.Security.EscapeOutput.OutputNotEscaped
 */
function _theme_load_ajax() {
	$script  = '<script>';
	$script .= 'var ajaxUrl = "' . admin_url( 'admin-ajax.php' ) . '";';
	$script .= '</script>';

	echo $script;
}
add_action( 'wp_footer', '_theme_load_ajax' );
// phpcs:enabled WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Gera um iframe do youtube a partir de uma URL do youtube em qualquer formato
 *
 * @param string $url Url do vídeo.
 * @param string $classes classes do CSS.
 */
function _theme_generate_youtube_iframe( string $url, string $classes = '' ) {
	return preg_replace(
		'/\s*[a-zA-Z\/\/:\.]*youtu(be.com\/watch\?v=|.be\/)([a-zA-Z0-9\-_]+)([a-zA-Z0-9\/\*\-\_\?\&\;\%\=\.]*)/i',
		"<iframe src=\"//www.youtube.com/embed/$2\" class=\"$classes\" allowfullscreen></iframe>",
		$url
	);
}

/**
 * Retorna imagem destacada, caso não tenha ele retorna o placeholder
 *
 * @param int|null $postId ID do post current.
 * @return false|mixed|string|null
 */
function _theme_get_thumbnail( ?int $post_id ) {
	$thumb = get_field( 'imagem_placeholder', 'options' );
	
	if ( null !== $post_id ) {
		if ( has_post_thumbnail( $post_id ) ) {
			$thumb = get_the_post_thumbnail_url( $post_id );
		}
	}

	return $thumb;
}

/**
 * Retorna a logo principal
 *
 * @return mixed|null
 */
function _theme_get_logo() {
	return get_field( 'logo', 'options' );
}

/**
 * Retorna a logo do rodapé
 *
 * @return mixed|null
 */
function _theme_get_footer_logo() {
	return get_field( 'footer_logo', 'options' );
}

/**
 * Retorna as redes sociais
 *
 * @return mixed|null
 */
function _theme_get_social_networks() {
	return get_field( 'social_networks', 'options' );
}

/**
 * Retorna o endereço
 *
 * @return mixed|null
 */
function _theme_get_address() {
	return get_field( 'address', 'options' );
}

if ( ! function_exists( 'dd' ) ) {
	/**
	 * Var_dump and die method
	 *
	 * @param mixed $data Qualquer tipo de dado para ser debugado.
	 *
	 * @return void
	 */
	function dd( $data ) {

		ini_set( 'highlight.comment', '#969896; font-style: italic' );
		ini_set( 'highlight.default', '#FFFFFF' );
		ini_set( 'highlight.html', '#D16568' );
		ini_set( 'highlight.keyword', '#7FA3BC; font-weight: bold' );
		ini_set( 'highlight.string', '#F2C47E' );

		$output = highlight_string( "<?php\n\n" . var_export( $data, true ), true );

		echo "<div style=\"background-color: #1C1E21; padding: 1rem\">{$output}</div>";
		die;
	}
}

//Obtem o link de embed do google maps a partir do link
function resolve_google_maps_url( $url, $width="435", $height="405" ) {

    if ( empty( $url ) ) {
		return null;
	}


    // Regex para capturar latitude e longitude do link
    if ( preg_match( '/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches ) ) {

        $lat = $matches[1];
        $lng = $matches[2];

        $src = "https://www.google.com/maps?q={$lat},{$lng}&output=embed";

        echo "<iframe width='{$width}' height='{$height}' frameborder='0' style='border:0' allowfullscreen src='{$src}'></iframe>";
    }
}

//Obtem o valor anterior de um input com base na query string
function old( string $param ) {

	return !empty( $_GET[$param] ) ? sanitize_text_field( $_GET[$param] ) : null;
}

//Retorna a URL da página anterior
function get_previous_page_url() {

	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		return esc_url( $_SERVER['HTTP_REFERER'] );
	}

	return esc_url( home_url() );
}

/**
 * Impede que usuários não autenticados tenham acesso a determinadas páginas
 * 
 * A função deve ser chamada no inicio do arquivo.
*/
function _theme_require_login( ?string $redirect_to = null ) {
	if ( !is_user_logged_in() ) {
		$redirect_url = $redirect_to ?: wp_login_url( $_SERVER['REQUEST_URI'] );
		wp_redirect( esc_url( $redirect_url ) );
		exit;
	}
}

// Oculta a barra de admin do wordpress para usuários que não tem o perfil de adminstrador
function ocultar_wordpress_admin_bar() {
    if ( !current_user_can( 'administrator' ) && !is_admin() ) {
        show_admin_bar( false );
    }
}
add_action ('after_setup_theme', 'ocultar_wordpress_admin_bar' );

/**
 * Retorna o html da lista de imagens utilizadas no rodapé dos emails e dos documentos
*/
function renderizar_rodape_email() {
	$html_rodape = '';

	if ( $logos_rodape = get_field( 'email_rodape_logos', 'options' ) ) {
		foreach ( $logos_rodape as $logo ) {
			$url = esc_url( $logo );
			$url = str_replace( 'http://', 'https://', $url );
			$html_rodape .= "<img src=\"{$url}\">";
		}
	}

	return $html_rodape;
}

/**
 * Retorna as informações das unidades de um roteiro
*/
function roteiro_unidades_info( int $inscricao_id ) {

	$roteiro_id = get_post_meta( $inscricao_id, 'id_roteiro_inscricao', true );
	$unidades_roteiro = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
	$unidades_args = [
		'post_type' => 'post_up',
		'post__in' => $unidades_roteiro
	];

    return get_posts( $unidades_args );
}

/**
 * Retorna as informações da ficha de autorização do aluno com base no status
*/
function get_status_ficha_aluno( string $status ){
    switch( $status ){
        case 'analise':
            return [
                'texto' => 'Em análise',
                'classe' => 'text-warning'
            ];
        break;
        case 'validado':
            return [
                'texto' => 'Validado',
                'classe' => 'text-success'
            ];;
        break;
        case 'invalido':
            return [
                'texto' => 'Inválido',
                'classe' => 'text-danger'
            ];
        break;

        default:
            return [
                'texto' => 'Não enviado',
                'classe' => 'text'
            ];;
        break;
    }
}

/**
 * Busca um aluno em um array de turmas pelo codigoAluno
 *
 * @param array $turmas Array com a estrutura de turmas e alunos
 * @param int|string $codigoAluno Código do aluno a buscar
 * @return array|null Retorna os dados do aluno ou null se não encontrado
 */
function buscar_aluno_por_codigo( array $turmas, $codigoAluno ) {
    foreach ( $turmas as $turma ) {
        if ( !empty( $turma['alunosTurma'] ) ) {
            foreach ( $turma['alunosTurma'] as $aluno ) {
                if ( (string)$aluno['codigoAluno'] === (string)$codigoAluno ) {
                    return $aluno;
                }
            }
        }
    }
    return null;
}

/**
 * Redireciona o usuário para uma URL específica exibindo uma mensagem temporária.
 *
 * @param string $url     A URL para onde o usuário será redirecionado.
 * @param string $message A mensagem a ser exibida após o redirecionamento.
 * @param string $type    O tipo da mensagem, usado para definir o estilo visual (ex: 'success', 'error', 'info', 'warning').
 *
 * @return void
 */
function wp_redirect_with_message( $url, $message, $type = 'success' ) {
    $key = 'msg_' . wp_generate_password( 6, false, false );

    set_transient( $key, [
        'message' => $message,
        'type'    => $type,
    ], 15 );

    $url = add_query_arg( 'flash', $key, $url );

    wp_redirect( $url );
    exit;
}


/**
 * Exibe uma mensagem temporária armazenada via `wp_redirect_with_message()`.
 *
 * Essa função deve ser chamada no carregamento da página de destino.
 *
 * @return void
 */
function show_flash_message() {
    if ( empty( $_GET['flash'] ) ) {
        return;
    }

    $flash_key = sanitize_text_field( $_GET['flash'] );
    $data = get_transient( $flash_key );

    if ( !$data ) {
        return;
    }

    delete_transient( $flash_key );

	echo "<script>toastr['{$data['type']}']('{$data['message']}')</script>";
}

/**
 * Retorna a data atual formatada no timezone especificado.
 *
 * @param string $formato Formato da data, ex: 'd/m/Y H:i:s'
 * @param string $timezone Timezone, ex: 'America/Sao_Paulo'
 * @return string Data formatada no timezone desejado
 */
function obter_data_com_timezone( $formato = 'd/m/Y H:i:s', $timezone = 'UTC' ) {

    $original_tz = date_default_timezone_get();
    date_default_timezone_set( $timezone );

    $data_formatada = date( $formato );

    date_default_timezone_set( $original_tz );

    return $data_formatada;
}


/**
 * Retorna um texto compactado pelo número de caracteres.
 */
function retornaTextoReduzido($texto, $qtdCaracteres){
  $str = strlen($texto) > $qtdCaracteres ? substr($texto, 0, $qtdCaracteres).'...' : $texto;
  return $str;
}