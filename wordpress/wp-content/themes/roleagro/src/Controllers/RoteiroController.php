<?php

namespace App\Controllers;

use DateInterval;
use DatePeriod;
use DateTime;
use WP_Query;

class RoteiroController {
    private $roteiro_id;
    private $roteiro;
    private $id_unidades;

    public function __construct( $roteiro_id ) {
        $this->roteiro_id = $roteiro_id;
        $this->roteiro = get_post( $roteiro_id );
        $this->id_unidades = get_post_meta( $roteiro_id, 'ids_up_roteiro', true );
    }

    public function check() {

        if ( !$this->id_unidades ) {
            return null;
        }
    }

    public function get_galeria_imagens() {

        $this->check();
       
        $galeria_imagens = [];
        foreach ( $this->id_unidades as $unidade ) {
            array_push( $galeria_imagens, ...get_field( 'imagens_destacadas', $unidade ) );
        }
        
        return $galeria_imagens;
    }

    public function get_atrativos_local() {
        $this->check();

        $atrativos = [];
		
		if ( is_array( $this->id_unidades ) ) {
            foreach ( $this->id_unidades as $unidade ) {
                $arrAtrativos = get_field( 'atrativos_local', $unidade );
                if(isset($arrAtrativos)){
                    array_push( $atrativos, ...$arrAtrativos);
                }
            }
        }

		$ids = array_unique( $atrativos );

		$atrativos = get_terms([
			'taxonomy' => 'tax_up_atrativos',
			'include' => $ids,
			'orderby' => 'name',
			'order'   => 'ASC'
		]);

		if ( is_wp_error( $atrativos ) ) {
			return null;
		}
		
        return $atrativos;
    }

    public function get_aspectos_local() {
        $this->check();
        
        $aspectos = [];
		if(!empty($this->id_unidades)){
			foreach ( $this->id_unidades as $unidade ) {
                isset($unidade) ?: array_push( $aspectos, ...get_field( 'aspectos_local', $unidade ) );
			}

			$ids = array_unique( $aspectos );

			$aspectos = get_terms([
				'taxonomy' => 'tax_up_aspectos-do-local',
				'include' => $ids,
				'orderby' => 'name',
				'order'   => 'ASC'
			]);

			if ( is_wp_error( $aspectos ) ) {
				return null;
			}
		}
        return $aspectos;
    }

    public function get_unidades_info() {

        $this->check();

        $unidades_info = [];
        foreach ( $this->id_unidades as $unidade ) {

            array_push( $unidades_info, [
                'titulo' => get_the_title( $unidade ),
                'desc_resumida' => get_field( 'descricao_resumida', $unidade ),
                'desc_completa' => get_field( 'descricao_completa_do_local_e_atividades', $unidade ),
                'link_mapa' => get_field( 'link_mapa', $unidade )
            ]);
        }

        return $unidades_info;
    }

    public function get_datas_ofertadas() {

        if ( !get_field( 'datas_oferta_roteiro', $this->roteiro_id ) ) {
            return [];
        }
        
        $dias_ofertados = get_field( 'datas_oferta_roteiro', $this->roteiro_id );
        $dias_ofertados = wp_list_pluck( $dias_ofertados, 'dias_da_semana_roteiro' );
        $dias_ofertados = wp_list_pluck( $dias_ofertados, 'term_order' );
        
        $inicio = new DateTime( date( 'Y-01-01' ) );
        $fim = new DateTime( date( 'Y-12-31' ) );
        $fim->modify( '+1 day' );

        $intervalo = new DateInterval( 'P1D' );
        $periodo = new DatePeriod( $inicio, $intervalo, $fim );

        $datas = [];

        foreach ($periodo as $data) {
            $dia_semana = $data->format('N');

            if ( in_array( $dia_semana, $dias_ofertados ) ) {
                $datas[] = $data->format('Y-m-d');
            }
        }

        return $datas;
    }

    public function get_datas_indisponiveis() {
        $datas_indisponiveis = [];
        $datas_indisponiveis_unidades = [];
        $unidades_roteiro = get_post_meta( $this->roteiro_id, 'ids_up_roteiro', true );

        /**
         * Busca todas as datas com agendamentos realizados para o roteiro
        */
        $query = new WP_Query([
            'post_type'      => 'post_inscricao',
            'post_status'   => ['publish', 'pending'],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation'   => 'AND',
                [
                    'key'   => 'status_inscricao',
                    'compare' => '!=',
                    'value' => 'cancelado',
                ],
                [
                    'key'   => 'id_roteiro_inscricao',
                    'value' => $this->roteiro_id,
                    'type'  => 'NUMERIC',
                ],
            ],
        ]);

        if ( $query->have_posts() ) {
            foreach ( $query->posts as $inscricao_id ) {
                
                $data_formatada = DateTime::createFromFormat( 'Ymd', get_post_meta( $inscricao_id, 'data_reservada_para_o_roteiro', true ) );
                if ( !$data_formatada ) {
                    $data_formatada = DateTime::createFromFormat( 'Y-m-d', get_post_meta( $inscricao_id, 'data_reservada_para_o_roteiro', true ) );
                }

                $datas_indisponiveis[] = $data_formatada->format( 'Y-m-d' );
            }
        }

        /**
         * Busca todas as datas configuradas no cadastro da unidade como indisponiveis para agendamento.
        */
        foreach ( $unidades_roteiro as $unidade_id ) {
            $periodos_indisponiveis_unidade = get_field( 'dias_insdisponiveis_visitas', $unidade_id );

            if ( $periodos_indisponiveis_unidade ) {
                foreach ( $periodos_indisponiveis_unidade as $periodo_indisponivel ) {
                    $inicio = new DateTime( $periodo_indisponivel['dia_inicio'] );
                    $fim = new DateTime( $periodo_indisponivel['dia_fim'] );
                    $fim->modify( '+1 day' ); // +1 dia para o intervalo ficar correto

                    $intervalo = new DateInterval( 'P1D' );
                    $periodo = new DatePeriod( $inicio, $intervalo, $fim );

                    foreach ( $periodo as $data ) {
                        $datas_indisponiveis_unidades[] = $data->format('Y-m-d');
                    }
                }
            }
        }
        
        return array_values( array_unique( [...$datas_indisponiveis, ...$datas_indisponiveis_unidades] ) );
    }

    public function get_roteiro_id() {
        return $this->roteiro_id;
    }
}
