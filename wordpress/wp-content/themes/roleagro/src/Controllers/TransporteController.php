<?php

namespace App\Controllers;

class TransporteController {

    public function __construct(  ) {
        add_action( 'wp_ajax_get_transportador_veiculos', [$this, 'get_transportador_veiculos'] );
        add_action( 'wp_ajax_nopriv_get_transportador_veiculos', [$this, 'get_transportador_veiculos'] );
    }

    /*
    * Retorna os IDs dos tipos de veículos disponíveis em uma transportadora
    */
    public function get_transportador_veiculos() {
        $transportador_id = isset( $_POST['transportador_id'] ) ? sanitize_text_field( $_POST['transportador_id'] ) : null;

        $veiculos = get_field( 'tipos_transporte_disponiveis', $transportador_id );
        $opcoes = $veiculos ?  wp_list_pluck( $veiculos, 'tipo_veiculo' ) : [];

        wp_send_json_success($opcoes);
    }
}
