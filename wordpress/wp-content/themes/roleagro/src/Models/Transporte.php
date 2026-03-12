<?php

namespace App\Models;

use App\Models\Base\PostType;
use WP_Query;

class Transporte extends PostType {

    private $post_name;

    public function __construct() {

        $this->post_name = 'post_transporte'; 
        parent::__construct( $this->post_name );

        // Vincula as taxonomias compartilhadas do projeto ao post type.
        add_action( 'init', [$this, 'carregar_taxonomias_compartilhadas'], 20 );

        // Validação de CNPJ único
        add_filter( 'acf/validate_value/name=cnpj', [$this, 'validar_cnpj_unico'], 10, 2 );

        // Impede que a transportadora seja exluida ou enviada para a lixeira se estiver sendo utilizada em algum agendamento
        add_action( 'before_delete_post', [$this, 'validar_exclusao'] );
        add_action( 'wp_trash_post', [$this, 'validar_exclusao'] );
        add_action( 'admin_notices', [$this, 'exibir_erros_exclusao'] );

        add_filter( "manage_{$this->post_name}_posts_columns", [$this, 'adicionar_colunas_personalizadas'] );
        add_action( "manage_{$this->post_name}_posts_custom_column", [$this, 'preencher_colunas_personalizadas'], 10, 2 );
    }
    
    public function getParams(): array {
        return [
            'key'			=> 'transporte',
            'slug'          => 'transporte',
            'name'          => 'Transportes',
            'singular_name' => 'Transportes',
            'dashicon'      => 'dashicons-car',
            'supports'      => [ 'title', 'author' ],
            'visibility'	=> 'private',
            'taxonomy'      => [
                [
                    'name' => 'Tipos de veículos',
                    'slug' => 'tipos-veiculos',
                    'meta_box_cb' => false
                ],
            ]
        ];
    }

    public function carregar_taxonomias_compartilhadas() {
        register_taxonomy_for_object_type( 'tax_up_distritos', $this->post_name );
        register_taxonomy_for_object_type( 'tax_up_regioes', $this->post_name );
    }

    /**
     * Garante que o CNPJ seja único no post_type.
    */
    public function validar_cnpj_unico( $valido, $valor ) {
        if ( $valido !== true ) {
            return $valido;
        }

        $cnpj = trim( $valor );
        if ( empty( $cnpj ) ) {
            return $valido;
        }

        $post_id = (int) acf_get_valid_post_id( $_POST['_acf_post_id'] ?? null );

        if ( !$post_id ) {
            $post_id = get_the_ID();
        }

        $query = new WP_Query([
            'post_type'      => $this->post_name,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => 'cnpj',
                    'value'   => $cnpj,
                    'compare' => '=',
                ],
            ],
            'post__not_in'   => [$post_id],
            'fields'         => 'ids',
            'posts_per_page' => 1,
        ]);

        if ( $query->have_posts() ) {
            return 'Já existe um transportador cadastrado com este CNPJ.';
        }

        return $valido;
    }

    /**
     * Garante que apenas transportadoras que não estejam em uso sejam exluídas.
    */
    public function validar_exclusao( $post_id ) {

        $post = get_post( $post_id );

        if ( !$post || $post->post_type !== $this->post_name ) {
            return;
        }

        $query = new WP_Query([
            'post_type'      => 'post_inscricao',
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'     => 'transporte',
                    'value'   => $post_id,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => -1,
        ]);

        if ( $query->have_posts() ) {
            $mensagem = "
                <p>Não é possível remover este transportador, pois ele está vinculado a uma ou mais inscrições.</p>
                <p>Inscrições vinculadas:</p>
            ";
            foreach ( $query->posts as $inscricao ) {
                $link_admin = get_edit_post_link( $inscricao->ID );
                $mensagem .= "
                    <span>-></span>
                    <a href='{$link_admin}' target='_blank'>{$inscricao->post_title}</a></br>
                ";
            }

            set_transient( 'transportador_erro', $mensagem, 30 );
            wp_safe_redirect( wp_get_referer() );
            exit;
        }
    }

    // Exibir aviso no admin
    public function exibir_erros_exclusao() {
        if ( $mensagem = get_transient( 'transportador_erro' ) ) {
            echo '<div class="notice notice-error is-dismissible">' . $mensagem . '</div>';
            delete_transient( 'transportador_erro' );
        }
    }

    public function adicionar_colunas_personalizadas( $colunas ) {

        $novas_colunas = [];
        foreach ( $colunas as $nome => $titulo ) {

            $novas_colunas[$nome] = $titulo;

            if ( $nome === 'title' ) {
                $novas_colunas['localizacao'] = 'Localização';
                $novas_colunas['telefone'] = 'Telefone da Empresa';
                $novas_colunas['cnpj'] = 'CNPJ';
            }
        }

        return $novas_colunas;
    }

    public function preencher_colunas_personalizadas( $coluna, $post_id ) {

        if ( $coluna === 'localizacao' ) {
            $regiao = get_field( 'regiao', $post_id );
            $distrito = get_field( 'distrito', $post_id );

            $localizacao = [$regiao->name ?? '', $distrito->name ?? ''];
            $localizacao = implode( ', ', array_filter( $localizacao ) );
            $localizacao = !empty( $localizacao ) ? $localizacao : '-';

            echo esc_html( $localizacao );
        }

        if ( $coluna === 'cnpj' ) {
            $cnpj = get_field( 'cnpj', $post_id );
            echo esc_html( $cnpj ?: '-' );
        }
        
        if ( $coluna === 'telefone' ) {
            $telefone = get_field( 'telefone_contato_principal', $post_id );
            echo esc_html( $telefone ?: '-' );
        }
    }
}