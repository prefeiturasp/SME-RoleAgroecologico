<?php

namespace App\Models;

use App\Models\Base\PostType;

class Inscricao extends PostType {

    public function __construct() {
        parent::__construct('post_inscricao');

        //Exibe a região no select de transportador no gerenciamento das inscrições
        add_filter( 'acf/fields/post_object/result/name=transporte', [$this, 'exibir_informacoes_adicionais_campo'], 10, 2 );

        add_filter('acf/load_field/name=veiculos_passeio', [$this, 'carregar_tipos_veiculos'] );
    }
    
    public function getParams(): array {
        return [
            'key'            => 'inscricao',
            'slug'           => 'inscricao',
            'name'           => 'Inscrições',
            'singular_name'  => 'Inscrição',
            'dashicon'       => 'dashicons-tickets-alt',
            'supports'       => [ 'title' ],
            'visibility'     => 'private',
        ];
    }

    /**
     * Adiciona a informação da região no seletor de transporte no gerenciamento das inscrições 
    */
    function exibir_informacoes_adicionais_campo( $titulo, $post ) {

        $regiao = get_field( 'regiao', $post->ID );

        if ( $regiao && is_object( $regiao ) && isset( $regiao->name ) ) {
            return "{$titulo} - [ {$regiao->name} ]";
        }

        return $titulo;
    }

    /**
     * Adiciona dinamicamente as opções do select com base na taxonomia de tipos de veículos 
    */
    public function carregar_tipos_veiculos( $field ) {
        $tipos = get_terms([
            'taxonomy' => 'tax_transporte_tipos-veiculos',
            'hide_empty' => false,
        ]);
    
        $field['choices'] = [];
        foreach ( $tipos as $tipo ) {
            $field['choices'][$tipo->term_id] = $tipo->name;
        }
    
        return $field;
    } 
}