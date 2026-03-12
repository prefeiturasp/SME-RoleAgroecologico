<?php

namespace App\Controllers;

use WP_Query;

class RoteiroQueryController {

    /**
     * Lista de seções fixas da home.
    */
    private static $secoes = [
        'regiao' => [
            'titulo' => 'Rolês por Região',
        ],
        'acessibilidade' => [
            'titulo' => 'Rolês com Acessibilidade',
            'meta_query' => [
                [
                    'key' => 'roteiro_com_acessibilidade',
                    'value' => 1,
                    'compare' => '='
                ]
            ]
        ],
        'integral' => [
            'titulo' => 'Rolês de Tempo Integral',
            'meta_query' => [
                [
                    'key' => 'roteiro_de_tempo',
                    'value' => 0, // Parcial = 1 Integral = 0
                    'compare' => '='
                ]
            ]
        ],
        'parcial-almoco' => [
            'titulo' => 'Rolês de Tempo Parcial com Almoço',
            'meta_query' => [
                [
                    'key' => 'roteiro_com_oferta_de_almoco',
                    'value' => 1,
                    'compare' => '='
                ],
                [
                    'key' => 'roteiro_de_tempo',
                    'value' => 1, // Parcial = 1 Integral = 0
                    'compare' => '='
                ]
            ]
        ],
        'parcial-sem-almoco' => [
            'titulo' => 'Rolês de Tempo Parcial sem Almoço',
            'meta_query' => [
                [
                    'key' => 'roteiro_com_oferta_de_almoco',
                    'value' => 0,
                    'compare' => '='
                ],
                [
                    'key' => 'roteiro_de_tempo',
                    'value' => 1, // Parcial = 1 Integral = 0
                    'compare' => '='
                ]
            ]
        ],

    ];

    /**
     * Retorna roteiros de uma seção, aplicando filtros extras.
     * @return WP_Query
     */
    public static function listar ( $secao_id, $filtros = [], $limite = 12 )
    {
        if ( !isset( self::$secoes[$secao_id] ) ) {
            return new WP_Query();
        }

        $secao = self::$secoes[$secao_id];

        $args = [
            'post_type'      => 'post_roteiro',
            'post_status'    => 'publish',
            'posts_per_page' => $limite,
            'meta_query'     => $secao['meta_query'] ?? [],
            'tax_query'      => [],
        ];

        // --- TAXONOMIAS ---

        if ( !empty( $filtros['periodo'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tax_up_periodos-de-oferta',
                'field'    => 'slug',
                'terms'    => (array) $filtros['periodo'],
            ];
        }

        if ( !empty( $filtros['dia'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tax_up_disponibilidade-semana',
                'field'    => 'slug',
                'terms'    => (array) $filtros['dia'],
            ];
        }

        if ( !empty( $filtros['regiao'] ) ) {
            $args['tax_query'][] = [
                'taxonomy' => 'tax_up_regioes',
                'field'    => 'slug',
                'terms'    => (array) $filtros['regiao'],
            ];
        }

        // --- POSTMETA ---

        if ( !empty( $filtros['atrativo'] ) ) {

            $atrativo = get_term_by( 'slug', $filtros['atrativo'], 'tax_up_atrativos' );

            $args['meta_query'][] = [
                'key'     => 'roteiro_atrativos',
                'value'   => intval( $atrativo->term_id ),
                'compare' => 'LIKE',
            ];
        }

        if ( !empty( $filtros['particularidade'] ) ) {

            $particularidade = get_term_by( 'slug', $filtros['particularidade'], 'tax_up_aspectos-do-local' );

            $args['meta_query'][] = [
                'key'     => 'roteiro_particularidades',
                'value'   => $particularidade->term_id,
                'compare' => 'LIKE',
            ];
        }

        if ( !empty( $filtros['local'] ) ) {
            $local = get_page_by_path( $filtros['local'], OBJECT, 'post_up' );
            $args['meta_query'][] = [
                'key'     => 'ids_up_roteiro',
                'value'   => '"' . $local->ID . '"',
                'compare' => 'LIKE',
            ];
        }

        if ( !empty( $filtros['acessibilidade'] ) ) {
            $args['meta_query'][] = [
                'key' => 'roteiro_com_acessibilidade',
                'value' => 1,
                'compare' => '='
            ];
        }

        if ( !empty( $filtros['almoco'] ) ) {
            $args['meta_query'][] = [
                'key' => 'roteiro_com_oferta_de_almoco',
                'value' => 1,
                'compare' => '='
            ];
        }

        return new WP_Query( $args );
    }

     public static function buscar_agendamentos ( $filtros = [], $limite = 12 ){
        global $wpdb;

        $intervaloData = explode('-', $filtros['periodo']);
        $dataIni = $intervaloData[2].'-'.$intervaloData[1].'-'.$intervaloData[0];
        $dataFim = $intervaloData[5].'-'.$intervaloData[4].'-'.$intervaloData[3]; 

        $args = [
            'post_type'      => 'post_inscricao',
            'post_status'    => array('publish','pending','draft', 'private', 'future'),
            'posts_per_page' => $limite,
            'orderby'        => 'date',
            'order'          => 'DESC', // DESC = Descendente (mais novos primeiro)
            'meta_query'     => []
        ];

        if ( isset($filtros['ue']) ) {
            $ue = $filtros['ue'];
            $res = explode('-',$ue);

            if(count($res) > 1){
                $arrIds = [];
                $strBusca = '';
                for($i=0; count($res) > $i; $i++){
                    if($i==0){
                        $strBusca .= strtoupper($res[$i]).' - ';
                    } else {
                        $strBusca .= strtoupper($res[$i]).' ';
                    }
                }

                $result = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT(p.ID) 
                    FROM wp_posts p 
                    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id 
                    WHERE post_type = 'post_inscricao' AND pm.meta_value 
                    LIKE '%".$strBusca."%';"));

                if(count($result) > 0){
                    foreach($result as $value){
                        $arrIds[] = get_post_meta( $value->ID, 'codigo_eol_ue', true );
                    }
                }

                $args['meta_query'][] = [
                    'key'     => 'codigo_eol_ue',
                    'value'   => $arrIds,
                    'compare' => 'IN'
                ];
            } else {
                $args['meta_query'][] = [
                    'key'     => 'codigo_eol_ue',
                    'value'   => $ue,
                    'compare' => 'LIKE'
                ];
            }
        }

        if (isset($filtros['roteiro'])) {
            
            $roteiro = get_page_by_path( $filtros['roteiro'], OBJECT, 'post_roteiro' );
            $id_roteiro = $roteiro->ID;

            $args['meta_query'][] = [
                'key'     => 'id_roteiro_inscricao',
                'value'   => $id_roteiro,
                'compare' => 'LIKE'
            ];
        }

        if(isset($filtros['periodo'])){

            $args['meta_query'][] = [
                'relation' => 'AND', // Ou 'OR'
                array(
                    'key'     => 'data_reservada_para_o_roteiro', // Sua chave de meta-campo
                    'value'   => $dataIni,
                    'type'    => 'DATE',
                    'compare' => '>=', // Pega eventos a partir de 1º de Janeiro de 2025
                ),
                array(
                    'key'     => 'data_reservada_para_o_roteiro', // Outra chave de meta-campo
                    'value'   => $dataFim,
                    'type'    => 'DATE',
                    'compare' => '<=', // Pega eventos até 31 de Janeiro de 2025
                )
            ];
        }
        
        return new WP_Query($args);
  
     }

     public static function retorna_inscricao($arrPosts){
        
        $arrPostsAgendados = array();
        $arrPostsRealizados = array();
        $arrPostsCancelados = array();

        foreach($arrPosts as $post){

            $idRoteiro = get_field( "id_roteiro_inscricao", $post['id_post'] );
            $tipoRoteiro = get_field( 'tipo_de_roteiro', $idRoteiro );
            $regiao = get_field( 'regiao_tag_roteiro', $idRoteiro );
            $acessibilidade = get_field( 'roteiro_com_acessibilidade', $idRoteiro );
            $almoco = get_field( 'roteiro_com_oferta_de_almoco', $idRoteiro );
            $dataInscricao = get_field('data_reservada_para_o_roteiro', $post['id_post'] );
            $thumbRoteiro = esc_url( _theme_get_thumbnail( $idRoteiro ));
            $status = get_field( "status_inscricao", $post['id_post'] );

            $CodUeAgendamento = get_field( "codigo_eol_ue", $post['id_post'] );

            $data_obj = new \DateTime($dataInscricao);
            $dataInscricao = $data_obj->format('d/m/Y');

            $arr = array(
                'ID'              => $post['id_post'],
                'post_title'      => esc_html($post['titulo']), 
                'thumbnail'       => $thumbRoteiro,
                'id_roteiro'      => $idRoteiro,
                'tipo_roteiro'    => $tipoRoteiro,
                'regiao'          => $regiao->name,
                'acessibilidade'  => $acessibilidade,
                'almoco'          => $almoco,
                'data_agendamento'=> $dataInscricao,
                'status'          => $status
            );
    
            if($status == 'novo'){
                array_push($arrPostsAgendados, $arr);
            } elseif ($status == 'realizado'){
                array_push($arrPostsRealizados, $arr);
            } elseif($status == 'cancelado'){
                array_push($arrPostsCancelados, $arr);
            }
            
        }
        
        return array($arrPostsAgendados, $arrPostsRealizados, $arrPostsCancelados );
     }

    /**
     * Retorna as informações da seção.
     *
     * @param string $secao_id
     * @return array|null
     */
    public static function get_secao_info( $secao_id )
    {
        return self::$secoes[$secao_id] ?? null;
    }
}
