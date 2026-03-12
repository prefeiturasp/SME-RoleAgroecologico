<?php

namespace App\Models;

use App\Models\Base\PostType;

class UnidadesProdutivas extends PostType {

    public function __construct() {
        parent::__construct('post_unidades_produtivas');
    }
    
    public function getParams(): array {
        return [
            'key'			=> 'up',
            'slug'          => 'unidades-produtivas',
            'name'          => 'Unidades Produtivas / Parques',
            'singular_name' => 'Unidade produtiva / Parque',
            'dashicon'      => 'dashicons-admin-multisite',
            'supports'      => [ 'title', 'author' ],
            'visibility'	=> 'private',
            'taxonomy'      => [
                    [
                        'name' => 'Atrativos',
                        'slug' => 'atrativos',
                        'meta_box_cb' => false // Remove/Adiciona o metabox da taxonomia no post
                    ],
                    [
                        'name' => 'Alimentos Cultivados',
                        'slug' => 'alimentos-cultivados',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Aspectos do Local',
                        'slug' => 'aspectos-do-local',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Distritos',
                        'slug' => 'distritos',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Regiões',
                        'slug' => 'regioes',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Períodos de Oferta',
                        'slug' => 'periodos-de-oferta',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Tipos de CNPJ',
                        'slug' => 'tipos-de-cnpj',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Tipos de Unidades Produtivas',
                        'slug' => 'tipos-unidades',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Tipos de Parques',
                        'slug' => 'tipos-de-parques',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Vegetações e Biomas',
                        'slug' => 'vegetacoes-e-biomas',
                        'meta_box_cb' => false
                    ],
                    [
                        'name' => 'Disponibilidade na semana',
                        'slug' => 'disponibilidade-semana',
                        'meta_box_cb' => false
                    ]
                ],
            ];
    }
}