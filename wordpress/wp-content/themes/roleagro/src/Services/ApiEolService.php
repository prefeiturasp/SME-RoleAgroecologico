<?php

namespace App\Services;

if ( ! defined( 'ABSPATH' ) ) exit;

class ApiEolService {
    
    private $api_url;
    private $token;

    public function __construct() {
        $this->api_url = getenv('SMEINTEGRACAO_API_URL');
        $this->token   = getenv('SMEINTEGRACAO_API_TOKEN');
    }

    private function request( $endpoint, $params = [] ) {
        $url = $this->api_url . $endpoint;

        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        $response = wp_remote_get( $url, [
            'headers' => [
                'x-api-eol-key' => $this->token,
                'Accept'        => 'application/json',
            ],
            'timeout' => 20,
        ]);

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['status'] ) && $data['status'] != 200 ) {
            return [];
        }

        return $data ?: [];
    }

    /**
     * Busca todas as turmas
     */
    public function get_turmas( string $codigo_ue, int $ano_letivo ) {
        return $this->request( "/api/escolas/{$codigo_ue}/turmas/anos_letivos/$ano_letivo" );
    }

    /**
     * Busca alunos de uma turma
     */
    public function get_alunos( string $codigo_turma ) {
        $data = date('Y-12-31\T\00:00:00');

        return $this->request( "/api/alunos/turmas/{$codigo_turma}/ativos/{$data}" );
    }

    /**
     * Busca os dados de um servidor pelo RF
     */
    public function get_servidor( string $rf ) {
        return $this->request( "/api/funcionarios/nome-servidor/{$rf}" );
    }

    /**
     * Busca as necessidades especiais pelo código do aluno
     */
    public function get_necessidades_especiais_aluno( string $codigo_aluno ) {
        return $this->request( "/api/alunos/{$codigo_aluno}/necessidades-especiais" );
    }

    /**
     * Busca os dados Unidade Educacional
     */
    public function get_ue( string $codigo_ue ) {
        $dados = $this->request( "/api/escolas/dados/{$codigo_ue}");
        set_transient('dados_eol_ue_emef_pfom_'.$codigo_ue, (object) $dados, 3600);
        return $dados;
    }
}
