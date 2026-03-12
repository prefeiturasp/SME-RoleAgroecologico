<?php

namespace App\Controllers;

use App\Services\ApiEolService;
use App\Services\DocumentoService;
use EnviaEmail\classes\Envia_Emails;

class AgendamentoController {
    private $roteiro_id;
    private $api_service;
    private $idCodUe;

    public function __construct( $roteiro_id = null ) {

        $this->roteiro_id = $roteiro_id;
        $this->api_service = new ApiEolService();

        $retLocacao = get_current_user_id() ? get_user_meta( get_current_user_id(), 'unidade_locacao', true ) : null;
       
        if(isset($retLocacao) && !empty($retLocacao)){
			if ( isset( $retLocacao['codUnidade'] ) ) {
				$this->idCodUe = $retLocacao['codUnidade'];
				$_SESSION['turmas'] = $this->get_turmas($this->idCodUe);
			}
        }

        add_action( 'rest_api_init', [ $this, 'registrar_rotas_api' ] );
        // add_action( 'wp_ajax_get_informacoes_acompanhante', [ $this, 'get_informacoes_acompanhante' ] );
		
		//Envio de autorizações para o painel administrativo
        add_action( 'wp_ajax_enviar_autorizacoes', [$this, 'handle_enviar_autorizacoes'] );
        add_action( 'wp_ajax_nopriv_enviar_autorizacoes', [$this, 'handle_enviar_autorizacoes'] );

        //Gerar ficha de autorização individual do aluno
        add_action( 'wp_ajax_gerar_ficha_autorizacao_aluno', [$this, 'gerar_ficha_autorizacao_aluno'] );

        //Atualiza o status da ficha de autorização para "validado"
        add_action( 'save_post', [$this, 'valida_fichas_autorizacao'], 10, 2 );

        //Envio da solicitação de cancelamento de uma inscrição
        add_action( 'wp_ajax_solicitar_cancelamento_inscricao', [$this, 'handle_solicitar_cancelamento_inscricao'] );
        add_filter( 'acf/update_value/name=cancelar_agendamento_do_roteiro', [$this, 'handle_cancelar_inscricao'], 20, 3 );

        //Atualiza o status do agendamento para "confirmado".
        add_filter( 'acf/update_value/name=confirmar_agendamento_da_ue', [$this, 'handle_confirmar_inscricao'], 20, 3 );

        //Gera as listas de participantes para o produtor e para o transportador
        add_action( 'admin_post_gerar_lista_participantes', [$this, 'gerar_listas_participantes'] );
        //Gera a planilha de lista de presenca
        add_action( 'admin_post_gerar_planilha_lista_presenca', [$this, 'gerar_planilha_lista_presenca'] );
        //Baixar a planilha de lista de presenca
        add_action('wp_ajax_baixa_arquivo_lista_presenca',  [$this, 'baixa_arquivo_lista_presenca']);
        add_action('wp_ajax_nopriv_baixa_arquivo_lista_presenca',  [$this, 'baixa_arquivo_lista_presenca']);

        //Baixar dieta dos alunos
        add_action('wp_ajax_baixar_dieta_aluno',  [$this, 'baixar_dieta_aluno']);
        add_action('wp_ajax_nopriv_baixar_dieta_aluno',  [$this, 'baixar_dieta_aluno']);
        
        
    }

    public function get_tags_roteiro(){
        return wp_get_post_tags( $this->roteiro_id );
    }

    public function get_turmas( ?string $codigo_ue = null, ?int $ano_letivo = null, ?string $ano = '6' ) {
        
        $codigo_ue = $codigo_ue ?: $this->idCodUe;
        $ano_letivo = $ano_letivo ?: date('Y');
        $turmas = $this->api_service->get_turmas( $codigo_ue, $ano_letivo );

        if ( isset( $turmas['error'] ) ) {
            return [];
        }

        return $this->filtrar_turmas_por_ano( $turmas, $ano );
        exit();
    }

    public function get_alunos($turmas) {
        
        $arrAlunoTurmas = [];
        $i = 0;
        foreach($turmas as $turma){
            
            $alunos = (new ApiEolService())->get_alunos( $turma['idTurma'] );
           
            usort( $alunos, function( $a, $b ) {
                return strcmp( $a['nomeAluno'], $b['nomeAluno'] );
            });
            
            foreach ( $alunos as $key => $aluno ) {
                if ( isset($aluno['possuiDieta']) && $aluno['possuiDieta'] == 1 ) {
                    $aluno['possuiDieta'] = 1;
                } else {
                    $aluno['possuiDieta'] = 0;
                }
                
                if ( $aluno['possuiDeficiencia'] == 1 ) {
                    $necessidades_especiais = (new ApiEolService())->get_necessidades_especiais_aluno( $aluno['codigoAluno'] );
                    $alunos[$key]['necessidades_especiais'] = $necessidades_especiais;
                }
               
            }
            $arrRetorno = array("turma"=>$turma[$i],"alunosTurma"=>$alunos);
            array_push($arrAlunoTurmas, $arrRetorno);

            $i++;
        }
        
       return $arrAlunoTurmas;
    
    }

    public function get_informacoes_acompanhante( $request ) {
       
        $rf = isset( $_POST['rf'] ) ? sanitize_text_field( $_POST['rf'] ) : null;
        wp_send_json_success( $this->api_service->get_servidor( $rf ));
 
    }

    /**
     * Retorna a lista de turmas filtradas por ano
     *
     * Filtra dentro do array de turmas apenas as turmas do ano selecionado
     *
     * @param  array  $turmas  Array de turmas seguindo o formato da API do EOL.
     * @param  string  $ano     Ano da turma: 6, 7, 8 (Ex.: 6 = 6ª ano)
    */
    private function filtrar_turmas_por_ano( array $turmas, string $ano = '6' ) {

        $turmas_filtradas = array_filter( $turmas, function ($turma) use ( $ano ) {
            return preg_match( "/\b{$ano}/", $turma['nomeTurmaEOL'] );
        } );

        return array_map(function($turma) use ( $ano ) {
            return [
                'codigo_turma' => $turma['codigoTurma'],
                'nome_turma_eol' => $turma['nomeTurmaEOL'],
                'nome_turma' => $turma['nomeTurma'],
                'modalidade' => $turma['siglaModalidade'],
                'nome_ano' => "{$ano}º ano"
            ];

        }, $turmas_filtradas);
    }

    public function registrar_rotas_api() {
        register_rest_route( 'role-agroecologico/v1', 'servidor/(?P<id>\d+)/informacoes', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_informacoes_acompanhante' ],
            'permission_callback' => function () {
                return true;//is_user_logged_in();
            },
        ] );

    }

    public static function get_inscricao($idIscricao){
        global $wpdb;

        $sql = "SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE ID = $idIscricao";
        $post = $wpdb->get_row( $sql );

        $idRoteiro = get_field( "id_roteiro_inscricao", $post->ID );
        $tipoRoteiro = get_field( 'tipo_de_roteiro', $idRoteiro );
        $regiao = get_field( 'regiao_tag_roteiro', $idRoteiro );
        $acessibilidade = get_field( 'roteiro_com_acessibilidade', $idRoteiro );
        $almoco = get_field( 'roteiro_com_oferta_de_almoco', $idRoteiro );
        $dataInscricao = get_field('data_reservada_para_o_roteiro', $post->ID );
        $horarioSaida = get_field('horario_de_saida_da_ue', $post->ID );
        $horarioRetorno = get_field('horario_previsto_de_retorno_a_ue', $post->ID );
        $thumbRoteiro = esc_url( _theme_get_thumbnail( $idRoteiro ));
        $status = get_field( "status_inscricao", $post->ID );
        $turmas = get_field( "dados_turmas", $post->ID );
        $educadores = get_field( "dados_educadores", $post->ID );
        $acompanhantes = get_field( "dados_acompanhantes", $post->ID );

        $arr = array(
            'ID'              => $post->ID,
            'post_title'      => esc_html($post->post_title), 
            'thumbnail'       => $thumbRoteiro,
            'id_roteiro'      => $idRoteiro,
            'tipo_roteiro'    => $tipoRoteiro,
            'regiao'          => $regiao->name,
            'acessibilidade'  => $acessibilidade,
            'almoco'          => $almoco,
            'data_agendamento'=>  date('d/m/Y', strtotime($dataInscricao)),
            'horario_saida'   => $horarioSaida,
            'horario_retorno' => $horarioRetorno,
            'turmas'          => $turmas,
            'educadores'      => $educadores,
            'acompanhantes'   => $acompanhantes,
            'status'          => $status
        );

        return $arr;
    }

    public static function getAgendamentos($idUser) {
        global $wpdb;

        if($idUser){

            $dadosUser = get_user_meta( $idUser, 'unidade_locacao', true );
            $idUE = ($dadosUser['codUnidade']);

            $arrPostsAgendados = array();
            $arrPostsRealizados = array();
            $arrPostsCancelados = array();

            $sql = "SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'post_inscricao' AND post_status NOT IN ('trash') AND post_author = $idUser ORDER BY ID";

            $posts = $wpdb->get_results( $sql );

            if(isset($posts) && count($posts) > 0 ){
                
                foreach($posts as $post){

                    $idRoteiro = get_field( "id_roteiro_inscricao", $post->ID );
                    $tipoRoteiro = get_field( 'tipo_de_roteiro', $idRoteiro );
                    $regiao = get_field( 'regiao_tag_roteiro', $idRoteiro );
                    $acessibilidade = get_field( 'roteiro_com_acessibilidade', $idRoteiro );
                    $almoco = get_field( 'roteiro_com_oferta_de_almoco', $idRoteiro );
                    $dataInscricao = get_field('data_reservada_para_o_roteiro', $post->ID );
                    $thumbRoteiro = esc_url( _theme_get_thumbnail( $idRoteiro ));
                    $status = get_field( "status_inscricao", $post->ID );

                    $CodUeAgendamento = get_field( "codigo_eol_ue", $post->ID );

                    if($CodUeAgendamento == $idUE){

                        $data_obj = new \DateTime($dataInscricao);
                        $dataInscricao = $data_obj->format('d/m/Y');

                        $arr = array(
                            'ID'              => $post->ID,
                            'post_title'      => esc_html($post->post_title), 
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
                }
            }

            return array($arrPostsAgendados, $arrPostsRealizados, $arrPostsCancelados );
        }
    }

    public function handle_enviar_autorizacoes() {

        $id_inscricao = isset( $_POST['id_inscricao'] ) ? intval( $_POST['id_inscricao'] ) : null;
        $dados_alunos = isset( $_POST['dados_alunos'] ) ? stripslashes( $_POST['dados_alunos'] ) : null;
        $arquivo = $_FILES['autorizacoes'] ?? null;

        if ( !$id_inscricao ) {
            wp_send_json_error([
                'message' => 'Não foi possível localizar o agendamento.'
            ]);
        }

        if ( !$dados_alunos ) {
            wp_send_json_error([
                'message' => 'Por favor, selecione os alunos na listagem.'
            ]);
        }

        if ( !$arquivo ) {
            wp_send_json_error([
                'message' => 'É necessário adicionar o arquivo com as autorizações.'
            ]);
        }

        $dados_alunos = json_decode( $dados_alunos, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error([
                'message' => 'Erro ao interpretar os dados dos alunos: ' . json_last_error_msg()
            ]);
        }


        if ( $arquivo && $arquivo['error'] === UPLOAD_ERR_OK ) {

            $unidade_escolar = get_post_meta( $id_inscricao, 'codigo_eol_ue', true );
            $roteiro = get_post_meta( $id_inscricao, 'id_roteiro_inscricao', true );
            $upload_dir = get_theme_file_path( 'storage' );
            $path = "autorizacoes/recebidas/{$unidade_escolar}/{$roteiro}/{$id_inscricao}";
            $file_path  = $upload_dir . '/' . $path;
            $tipo_arquivo = pathinfo( $arquivo['name'], PATHINFO_EXTENSION );
            $data = date( 'dmYHis' );

            if ( !file_exists( $file_path ) ) {
                wp_mkdir_p ( $file_path );
            }

            $nome_arquivo = "autorizacoes_{$id_inscricao}_{$data}.{$tipo_arquivo}";
            $caminho_final = $file_path . '/' . $nome_arquivo;

            if ( !move_uploaded_file( $arquivo['tmp_name'], $caminho_final ) ) {
                wp_send_json_error([
                    'message' => 'Houve um erro ao salvar o arquivo enviado.'
                ]);
            }

        } else {
            wp_send_json_error([
                'message' => 'O arquivo enviado não é válido. Verifique as informações e tente novamente.'
            ]);
        }

        $this->atualizar_status_autorizacoes( $id_inscricao, $dados_alunos, 'analise' );
        $this->atualizar_lista_autorizacoes_recebidas( $id_inscricao, $nome_arquivo, $path );

        wp_send_json_success([
            'message' => 'Autorizações enviadas com sucesso!'
        ]);
    }

    /**
     * Atualiza o status das autorizações dos alunos vinculados a uma inscrição.
     * 
     * @param int    $id_inscricao   ID do post de inscrição onde os dados serão atualizados.
     * @param array  $dados_alunos   Estrutura contendo turmas e respectivos alunos para atualização.
     *                               Exemplo: [
     *                                 ['turma' => 123, 'alunos' => ['001', '002']]
     *                               ]
     * @param string $status         Status a ser atribuído (ex.: 'analise', 'enviado').
     *
     * @return void
    */
    private function atualizar_status_autorizacoes( int $id_inscricao, array $dados_alunos, string $status ) {

        $alunos_inscricao = get_post_meta( $id_inscricao, 'dados_turmas', true );

        if ( empty( $alunos_inscricao ) ) {
            return false;
        }

        try {
            $alunos_por_turma = [];
            foreach ( $dados_alunos as $turma ) {
                $alunos_por_turma[$turma['turma']] = $turma['alunos'];
            }
    
            $alunos_inscricao = array_map( function( $turma_inscricao ) use ( $alunos_por_turma, $status ) {
        
                $turma_id = $turma_inscricao['idTurma'] ?? null;
        
                if ( $turma_id && isset( $alunos_por_turma[$turma_id] ) ) {
        
                    $turma_inscricao['alunosTurma'] = array_map( function( $aluno ) use ( $alunos_por_turma, $turma_id, $status ) {
                        if ( in_array( $aluno['codigoAluno'], $alunos_por_turma[$turma_id] ) ) {
                            $aluno['situacaoFicha'] = $status;
                        }
                        return $aluno;
                    }, $turma_inscricao['alunosTurma']);
        
                }
        
                return $turma_inscricao;
        
            }, $alunos_inscricao);
    
            update_post_meta( $id_inscricao, 'dados_turmas', $alunos_inscricao );
        } catch (\Throwable $th) {

            return $th->getMessage();
        }
    }

    /**
     * Atualiza a lista de arquivos de autorizações recebidas para uma inscrição.
     *
     * Cada envio de arquivo é registrado como um novo item em um array no post meta.
     *
     * @param int    $id_inscricao   ID do post de inscrição onde a lista será atualizada.
     * @param string $nome_arquivo   Nome do arquivo salvo (ex.: 'autorizacoes.pdf').
     * @param string $caminho        Caminho relativo onde o arquivo foi armazenado.
     *                               Exemplo: '/autorizacoes/recebidas/'
     *
     * @return bool
    */
    private function atualizar_lista_autorizacoes_recebidas( int $id_inscricao, string $nome_arquivo, string $caminho ) {

        $lista_autorizacoes_recebidas = get_post_meta( $id_inscricao, 'lista_autorizacoes_recebidas', true );

        if ( empty( $lista_autorizacoes_recebidas ) ) {
            $lista_autorizacoes_recebidas = [];
        }

        $lista_autorizacoes_recebidas[] = [
            'nome_arquivo' => $nome_arquivo,
            'caminho' => $caminho,
            'data_recebimento' => current_time( 'mysql' ),
            'usuario' => get_current_user_id()
        ];

        return update_post_meta( $id_inscricao, 'lista_autorizacoes_recebidas', $lista_autorizacoes_recebidas );
    }

    /**
     * Gera o arquivo .pdf com a ficha de autorização de um estudante 
    */
    public function gerar_ficha_autorizacao_aluno() {

        if  (empty( $_GET['aluno'] ) || empty( $_GET['inscricao'] ) ) {
            wp_die('Parâmetros inválidos.');
        }
    
        $aluno_id = sanitize_text_field( $_GET['aluno'] );
        $inscricao_id = intval( $_GET['inscricao'] );

        $dados_turma = get_post_meta( $inscricao_id, 'dados_turmas', true );
        $aluno = [buscar_aluno_por_codigo( $dados_turma, $aluno_id )];

        $documento_service = new DocumentoService();

        $documento_service->gerar_pdf_ficha_aluno($inscricao_id, $aluno, false);
    }

    /**
     * Atualiza o status da ficha de autorização dos alunos para "validado" ao atualizar o post 
    */
    public function valida_fichas_autorizacao( $post_id, $post ) {

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }
    
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
    
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    
        if ( $post->post_type !== 'post_inscricao' ) {
            return;
        }
    
        $dados_turmas = get_post_meta( $post_id, 'dados_turmas', true );
    
        // pega os alunos selecionados na tabela no admin
        $selecionados = isset( $_POST['documentos_validos'] ) && is_array( $_POST['documentos_validos'] )
            ? array_map( 'sanitize_text_field', $_POST['documentos_validos'] )
            : [];
    
        if ( is_array( $dados_turmas ) ) {
            foreach ( $dados_turmas as &$turma ) {
                if ( !isset( $turma['alunosTurma'] ) || !is_array( $turma['alunosTurma'] ) ) {
                    continue;
                }
    
                foreach ( $turma['alunosTurma'] as &$aluno ) {
    
                    if ( in_array( (string) $aluno['codigoAluno'], $selecionados, true ) ) {
                        $aluno['situacaoFicha'] = 'validado';
                    }
                }
            }
    
            unset( $aluno, $turma );
    
            update_post_meta( $post_id, 'dados_turmas', $dados_turmas );
        }
    }

    public function handle_solicitar_cancelamento_inscricao() {

        $id_inscricao = isset( $_POST['id_inscricao'] ) ? intval( $_POST['id_inscricao'] ) : null;
        $justificativa = isset( $_POST['justificativa'] ) ? sanitize_text_field( $_POST['justificativa'] ) : null;

        if ( !$id_inscricao ) {
            wp_send_json_error([
                'message' => 'Não foi possível localizar o agendamento.'
            ]);
        }

        if ( !$justificativa ) {
            wp_send_json_error([
                'message' => 'É necessário preencher o campo de justificativa.'
            ]);
        }

        update_post_meta( $id_inscricao, 'status_inscricao', 'solicitacao-cancelamento' );
        update_post_meta( $id_inscricao, 'justificativa_solicitacao_cancelamento', $justificativa );

        //Envia o e-mail de confirmação da solicitação de cancelamento.
        new Envia_Emails( $id_inscricao, 'solicitacao_cancelamento', 'solicitar_cancelamento' );

        wp_send_json_success([
            'message' => 'Solicitação realizada com sucesso!'
        ]);
    }

    public function handle_cancelar_inscricao( $novo_valor, $post_id, $campo ) {

        if ( get_post_type( $post_id ) !== 'post_inscricao' ) {
            return $novo_valor;
        }

        if ( boolval( $novo_valor ) ) {
            update_post_meta( $post_id, 'status_inscricao', 'cancelado' );
        }

        return $novo_valor;
    }

    public function handle_confirmar_inscricao( $novo_valor, $post_id, $campo ) {

        if ( get_post_type( $post_id ) !== 'post_inscricao' ) {
            return $novo_valor;
        }

        if ( boolval( $novo_valor ) ) {
            update_post_meta( $post_id, 'status_inscricao', 'inscricao-confirmada' );
        }

        return $novo_valor;
    }

    /**
     * Gera os arquivos .pdf com as listas de participantes
    */
    public function gerar_listas_participantes() {

        if  ( !isset( $_GET['post'] ) ) {
            wp_die('Parâmetros inválidos.');
        }
    
        $inscricao_id = intval( $_GET['post'] );
        $dados_turma = get_post_meta( $inscricao_id, 'dados_turmas', true );
  
        $documento_service = new DocumentoService();

        $documento_service->gerar_lista_participantes( $inscricao_id );
    }

    /**
     * Retorna todos o agendamentos para exibição da lista de presença
    */
    public static function getAgendamentoListaPresenca() {
        
        global $wpdb;

        $arrPostsAgendados = array();
        $arrPostsRealizados = array();
        $arrPostsCancelados = array();

        $sql = "SELECT DISTINCT(ID), post_title 
        FROM {$wpdb->prefix}posts p
        INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
        WHERE p.post_type = 'post_inscricao' AND p.post_status NOT IN ('trash') AND pm.meta_key = 'data_reservada_para_o_roteiro' 
        ORDER BY pm.meta_value DESC";

        $posts = $wpdb->get_results( $sql );

        if(isset($posts) && count($posts) > 0 ){
             
            foreach($posts as $post){

                $idRoteiro = get_field( "id_roteiro_inscricao", $post->ID );
                $tipoRoteiro = get_field( 'tipo_de_roteiro', $idRoteiro );
                $regiao = get_field( 'regiao_tag_roteiro', $idRoteiro );
                $acessibilidade = get_field( 'roteiro_com_acessibilidade', $idRoteiro );
                $almoco = get_field( 'roteiro_com_oferta_de_almoco', $idRoteiro );
                $dataInscricao = get_field('data_reservada_para_o_roteiro', $post->ID );
                $thumbRoteiro = esc_url( _theme_get_thumbnail( $idRoteiro ));
                $status = get_field( "status_inscricao", $post->ID );

                $data_obj = new \DateTime($dataInscricao);
                $dataInscricao = $data_obj->format('d/m/Y');

                $arr = array(
                    'ID'              => $post->ID,
                    'post_title'      => esc_html($post->post_title), 
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
        }

        return array($arrPostsAgendados, $arrPostsRealizados, $arrPostsCancelados );
    }

    /**
     * Gera a lista de presença
    */
    public function gerar_planilha_lista_presenca() {

        if  ( !isset( $_GET['post'] ) ) {
            wp_die('Parâmetros inválidos.');
        }
    
        $inscricao_id = intval( $_GET['post'] );
        $dados_turma = get_post_meta( $inscricao_id, 'dados_turmas', true );
  
        $documento_service = new DocumentoService();

        $documento_service->gerar_planilha_lista_presenca( $inscricao_id );
    }

    /**
     * Baixa a lista de presença
    */
    public function baixa_arquivo_lista_presenca(){

        if  (empty( $_GET['id_inscricao'] ) ) {
            wp_die('Parâmetros inválidos.');
        }

        $post_id = intval($_GET['id_inscricao']);

        $upload_dir = get_theme_file_path( 'storage' );
        $path = "lista-presenca/recebida/{$post_id}";
        $file_path  = $upload_dir . '/' . $path;
        $nome_arquivo = "lista_presenca_{$post_id}.pdf";
        $caminho_arquivo = $file_path . '/' . $nome_arquivo;

        if (file_exists($caminho_arquivo)) {
            // Inserre o cabeçalho para forçar download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.$nome_arquivo.'"');
            header('Content-Length: ' . filesize($caminho_arquivo));
            readfile($caminho_arquivo);
            exit();
        }
    }

    /**
     * Baixa a dieta do aluno
    */
    public function baixar_dieta_aluno(){

        if  (empty( $_GET['uuid'] )) {
            wp_die('Parâmetros inválidos.');
        }

        $uuid = sanitize_text_field($_GET['uuid']);

        $url_api = explode('/api/', getenv('API_URL_SIGPAE_DIETAS'));
        $url_base =  $url_api[0];
        $url_request = $url_base."/api/solicitacoes-dieta-especial/{$uuid}/protocolo/?sem_foto=true";

        $curl = curl_init();
        curl_setopt_array($curl, 
            array(
                CURLOPT_URL => $url_request,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic '.getenv('API_TOKEN_SIGPAE_DIETAS')
                ),
            )
        );

        $pdf_content = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($http_code == 200) {
            // Headers para forçar o download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="documento_baixado.pdf"');
            header('Content-Length: ' . strlen($pdf_content));

            echo $pdf_content; // Envia o conteúdo do PDF para o navegador
        } else {
            echo "Erro ao baixar o PDF. Código HTTP: " . $http_code;
        }

        curl_close($ch);
    }

    public static function verifica_lista_presenca_inscritos($inscricao_id, $arrTurmas, $arrEducadores, $arrAcompanhantes){

        $listaPresencaTurmas = [];
        $listaPresencaEdu = [];
        $listaPresencaAco = [];

        // ALUNOS
        if(!empty($arrTurmas)){
            $verificaPresencaArrAlunos = false;
            foreach ($arrTurmas as $turma) {
                $arrAlunos = [];
                foreach ($turma['alunosTurma'] as $aluno) {
                    if(!isset($aluno['confirmacaoPresenca'])){
                        $aluno['confirmacaoPresenca'] = true;
                        $verificaPresencaArrAlunos = true;
                    } else if(isset($aluno['confirmacaoPresenca']) && $aluno['confirmacaoPresenca'] == true){
                        $aluno['confirmacaoPresenca'] = true;
                    } else {
                        $aluno['confirmacaoPresenca'] = false;
                    }
                    $arrAlunos[] = $aluno;
                }
                $listaPresencaTurmas[] = array("idTurma" => $turma['idTurma'], "nomeTurma" => $turma['nomeTurma'], "alunosTurma" => $arrAlunos);
            }
            if($verificaPresencaArrAlunos){
                update_post_meta($inscricao_id, 'dados_turmas', $listaPresencaTurmas);
            }

        }

        // EDUCADORES
        if(!empty($arrEducadores)){
            $verificaPresencaArrEdu = false;
            foreach ($arrEducadores as $edu) {
                if(!isset($edu['confirmacaoPresenca'])){
                    $edu['confirmacaoPresenca'] = true;
                    $verificaPresencaArrEdu = true;
                } else if(isset($edu['confirmacaoPresenca']) && $edu['confirmacaoPresenca'] == true){
                    $edu['confirmacaoPresenca'] = true;
                } else{
                    $edu['confirmacaoPresenca'] = false;
                }
                $listaPresencaEdu[] = $edu; 
            }

            if($verificaPresencaArrEdu){
                update_post_meta($inscricao_id, 'dados_educadores', $listaPresencaEdu);
            }
        }

        // ACOMPANHANTES
        if(!empty($arrAcompanhantes)){
            $verificaPresencaArrAco = false;
            foreach ($arrAcompanhantes as $aco) {
                if(!isset($aco['confirmacaoPresenca'])){
                    $aco['confirmacaoPresenca'] = true;
                    $verificaPresencaArrAco = true;
                } else if(isset($aco['confirmacaoPresenca']) && $aco['confirmacaoPresenca'] == true){
                    $aco['confirmacaoPresenca'] = true;
                } else{
                    $aco['confirmacaoPresenca'] = false;
                }
                $listaPresencaAco[] = $aco; 
            }

            if($verificaPresencaArrAco){
                update_post_meta($inscricao_id, 'dados_acompanhantes', $listaPresencaAco);
            }
        }

        return array('id_inscricao'=>$inscricao_id, 'arrTurmas'=>$listaPresencaTurmas, 'arrEducadores'=>$listaPresencaEdu, 'arrAcompanhantes'=>$listaPresencaAco);
    }
}