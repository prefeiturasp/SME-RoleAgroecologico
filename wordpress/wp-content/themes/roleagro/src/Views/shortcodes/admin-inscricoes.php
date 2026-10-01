<?php

if (is_user_logged_in()){

    if ( !session_id() ) {
       session_start();
    }

    $user_id = get_current_user_id();

    add_shortcode('conteudo_admin_inscricoes', 'conteudoInscricoes');
    add_shortcode('listagem_estudantes_inscritos', 'listagemEstudantesInscritos');
    add_shortcode('listagem_educadores_acompanhantes', 'listagemEducadoresAcompanhantes');
    add_shortcode('fornecedor_kit_complementar', 'fornecedorKitComplementar');

    add_shortcode('exibe_btn_envia_email_fornecedor_kit_complementar', 'exibeBtnFornecedorKitComplementar');

    add_shortcode('listagem_autorizacoes_estudantes', 'listagemAutorizacaosEstudantes');
    add_shortcode('justificativa_cancelamento_ue', 'justificativaCancelamentoUE'); //add_shortcode('listagem_cancelamento_ue', 'listagemCancelamentoUE');
	add_shortcode('btn_lista_participantes', 'exibir_botao_lista_participantes');
    add_shortcode('btn_lista_presenca', 'botao_gerar_lista_presenca');
    add_shortcode('planilha_da_lista_de_presenca', 'exibe_planilha_enviada_lista_presenca');
    add_shortcode('lista_de_presenca', 'exibe_lista_de_presenca');
    // FILTROS
    add_filter('manage_edit-post_inscricao_columns', 'add_novas_colunas_inscricao');
    add_action('manage_post_inscricao_posts_custom_column', 'conteudo_novas_colunas_iscricao', 10, 2);
    add_filter('manage_edit-post_inscricao_sortable_columns', 'colunas_ordenaveis');
    // Adiciona os filtros no topo da listagem
    // add_action('restrict_manage_posts', 'geraFiltros', 10, 2);
    // Aplica os filtros na query
    // add_action('pre_get_posts', 'aplicaQueryFiltros');

    add_filter('acf/load_field/name=observacoes_do_role', 'my_acf_load_field_readonly');

    add_action( 'save_post', 'salvar_kit_complementar', 10, 3 );

}

// Torna readonly o campo de observações de lista de presença
function my_acf_load_field_readonly( $field ) {
    // Check if the field name matches
    if ( $field['name'] == 'observacoes_do_role' ) {
        $field['readonly'] = 1;
    }
    return $field;
}

function conteudoInscricoes() { 

    echo retornaResumoInscricoes();

}

function exibeBtnFornecedorKitComplementar(){ ?>
        <div class="acf-like-field">

            <label class="acf-like-toggle">
                <input 
                    type="checkbox"
                    name="notificar_unidade"
                    value="1"
                >

                <span class="acf-like-slider">
                    <div class="acf-switch">
                        <span class="acf-switch-on" style="min-width: 23.9688px;">Sim</span>
                        <span class="acf-switch-off" style="min-width: 23.9688px;">Não</span>
                        <div class="acf-switch-slider">
                        </div>
                    </div>
                </span>
            </label>

        </div>
<?php }

function fornecedorKitComplementar(){

    $idInscricao = get_the_ID();
    $idRoteiro = get_post_meta( $idInscricao, 'id_roteiro_inscricao', true );
    $idUps = get_post_meta( $idRoteiro, 'ids_up_roteiro', true );

    $idFornecedor = get_post_meta( $idInscricao, '_fornecedor_kit_complementar', true );

    $fornecedorKit = [];

    foreach($idUps as $id){
        $tipo = get_post_meta($id, 'tipo_cadastro', true);

        if($tipo == 'unidade'){
            $fornecedor = get_post_meta( $id, 'fornecedor_de_kit_up', true );
            if($fornecedor){
                $fornecedorKit[] = array('idUnidade'=>$id, 'nomeUnidade' => get_the_title( $id ));
            }
        }
        
    }

    $args = array(
        'post_type' => 'post_up',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'fornecedor_de_kit_complementar_up',
                'value' => '1',
                'compare' => '=' 
            )
        ),
    );

    $unidadesProdutivas = new WP_Query( $args );

    // Array para guardar os resultados se precisar usar depois
    $posts_encontrados = array();

    if ( $unidadesProdutivas->have_posts() ) {
        $i=0;
        while ( $unidadesProdutivas->have_posts() ) {
            
            $unidadesProdutivas->the_post();
            
            // 1. Pegando o ID e o Título
            $id_post    = get_the_ID();
            $titulo_post = get_the_title();
            
            if(!in_array($id_post, $idUps)) {
                $posts_encontrados[] = array(
                    'id'     => $id_post,
                    'titulo' => $titulo_post
                );
            }
            
        } $i++; 
        
        ?>
        <div class="row">
            <div class="col-4">
                <form>                    
                    <select class="custom-select mr-sm-2" id="selectKitComplementar" name="selectKitComplementar">
                        <option value="">-- Selecione --</option>
                        <?php  foreach($posts_encontrados as $post){  
                            $termo_nome = buscaTaxonomia( $post['id'], 'tax_up_regioes' );
                            $selecionado = '';
                            if(isset($idFornecedor) && $idFornecedor == $post['id']){
                                $selecionado = 'selected';
                            }
                            ?>
                            <option value="<?= $post['id'] ?>" <?=$selecionado?> ><?= $post['titulo']; echo ' - ' . $termo_nome; ?> </option>
                        <?php } ?>
                    </select>
                </form>
            </div>
       </div>
       <br>
       <?php 
       // Sempre restaure os dados globais do post após o loop  
       wp_reset_postdata();
    } else { ?>
        <div class="row">
            <div class="col">
                <div class="card bg-light mb-3" id="card-fornecedor-selecionado">
                    <div class="card-body">
                        <strong>NÃO HÁ UNIDADE PRODUTIVA QUE FORNEÇA KIT COMPLEMENTAR.</strong>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div class="row">
    
    <?php
    if($idFornecedor): 
    
    $nomeSetor = get_the_title( $idFornecedor ). ' - ' . buscaTaxonomia( $idFornecedor, 'tax_up_regioes' );
    $endereco = get_post_meta($idFornecedor, 'logradouro', true) . ', '. get_post_meta($idFornecedor, 'numero', true) .', '. get_post_meta($idFornecedor, 'bairro', true).', '. get_post_meta($idFornecedor, 'cidade', true).', CEP '. get_post_meta($idFornecedor, 'cep', true);
    $telefone = get_post_meta($idFornecedor, 'telefone_contato', true);
    ?>
            <div class="col-4">
                <div class="card bg-light mb-3" id="card-fornecedor-selecionado">
                    <div class="card-body">
                        <strong>FORNECEDOR COMPLEMENTAR SELECIONADO</strong>
                        <hr> 
                        <p><strong>NOME:</strong> <?= $nomeSetor ?></p>
                        <p><strong>ENDEREÇO:</strong> <?= $endereco ?></p>
                        <p><strong>TELEFONE:</strong> <?= $telefone ?></p>
                    </div>
                </div>
            </div>
    <?php endif; 
    
        if(isset($fornecedorKit) && !empty($fornecedorKit)): 
            foreach($fornecedorKit as $item): ?>
                <div class="col-4">
                    <div class="card bg-light mb-3">
                        <div class="card-header">ATENÇÃO</div>
                        <div class="card-body">
                            <h5 class="card-title">A unidade: <strong><?= $item['nomeUnidade']; ?></strong></h5>
                            <p class="card-text">Fornece os kits para os participantes do rolê.</p>
                        </div>
                    </div>
                </div>
        <?php endforeach; 
        endif; ?>

        </div>
    <?php
}

function buscaTaxonomia($idPost, $taxonomia){
    $tax = wp_get_post_terms( $idPost, $taxonomia, array( 'fields' => 'names' ) );
    return $tax[0];
}

// 1. CAPTURAR O GANCHO AO SALVAR O POST
// Esse gancho roda toda vez que um post ou página é salvo
function salvar_kit_complementar( $post_id, $post, $update ) {
    // Evita executar em salvamentos automáticos (autosave)
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Evita executar em revisões de posts
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if(!empty($_POST['selectKitComplementar'])){
        $idFornecedor = sanitize_text_field($_POST['selectKitComplementar']);
        update_post_meta( $post_id, '_fornecedor_kit_complementar', $idFornecedor );
    } else {
        delete_post_meta( $post_id, '_fornecedor_kit_complementar' );
    }
}

function listagemEstudantesInscritos() { 
    $idIsncricao = get_the_ID();
    $arrTurmas = get_post_meta( $idIsncricao, 'dados_turmas', true );

    get_template_part( 'src/Views/shortcodes/templates/lista-estudantes', null, [
        'idIsncricao' => $idIsncricao,
        'arrTurmas' => $arrTurmas
    ]);
}

function listagemEducadoresAcompanhantes() { ?>

    <table class="table table-sm table-borderless">
        <thead>
            <tr class="border-top">
                <th scope="col"></th>
                <th scope="col">RF/CPF e Nome Completo</th>
                <th scope="col">Tipo</th>
                <th scope="col">Celular</th>
                <th scope="col">Data de Nascimento</th>
                <th scope="col">Deficiência ou<br>Necessidade Especial</th>
                <th scope="col">Dieta Especial ou<br>Restrição Alimentar</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $idIsncricao = get_the_ID();
                $arrEducadores = get_post_meta($idIsncricao, 'dados_educadores', true); 
                $arrAcompanhantes = get_post_meta($idIsncricao, 'dados_acompanhantes', true);

                if(!is_array($arrEducadores) || count($arrEducadores) == 0){ 
                    echo '<tr><td colspan="6" class="text-center">Nenhum educador ou acompanhante encontrado</td></tr>';
                } else {

                    if($arrAcompanhantes && count($arrAcompanhantes) > 0){
                        foreach ($arrAcompanhantes as $acompanhante) {
                            array_push($arrEducadores, $acompanhante); 
                        }
                    }
                    $i = 1;
                    foreach ($arrEducadores as $educador) { ?>
                        <tr class="border-top">
                            <th scope="row"><?= $i; ?></th>
                            <td><span><?= $educador['rf']; ?> - <?= $educador['nome']; ?></span></td>
                            <td><?= $educador['tipo']; ?></td>
                            <td><?php echo esc_html( $educador['celular'] ?? '-' ); ?></td>
                            <td><?= date('d/m/Y', strtotime($educador['data_nascimento'])); ?></td>
                            <td><?= $educador['necessidades']; ?></td>
                            <td><?= $educador['dieta']; ?></td>
                        </tr>
                        <?php if(isset($educador['justificativa'])) { ?>

                            <tr class="border-bottom">
                                <th scope="row"></th>
                                <td colspan="5">
                                    <p><b>Justificativa:</b> <?= $educador['justificativa']; ?></p>
                                </td>
                            </tr>

                    <?php } $i++; }
                } 
            ?>
            
        </tbody>
    </table>
    
<?php }

function retornaResumoInscricoes() {
    $post_ID = get_the_ID();
    $post = get_post($post_ID);

    $link = add_query_arg([
        'action' => 'gerar_lista_participantes',
        'post' => get_the_ID()
    ], admin_url( 'admin-post.php' ));

    $txtComplementar = "<div class='acf-actions'>
        <a href='{$link}' class='btn btn-outline-success-admin' id='emitir-lista-participantes-resumo'>
            <span class='dashicons dashicons-printer'></span>
            Emitir Lista de Participantes
        </a>
    </div>
    
    <p><b>Resumo dos participantes Inscritos:</b> última atualização em " . get_the_modified_date('d/m/Y \à\s h:i', $post_ID) . '</p>';
    $conteudo = $post->post_content;
    return $txtComplementar . $conteudo;
}

function listagemAutorizacaosEstudantes() { 

    $lista_autorizacoes = get_post_meta( get_the_ID(), 'lista_autorizacoes_recebidas', true );
    get_template_part( 'src/Views/shortcodes/templates/lista-autorizacoes', null, [ 'autorizacoes' => $lista_autorizacoes ]);
	
}

function justificativaCancelamentoUE() {

    $status_inscricao = get_post_meta( get_the_ID(), 'status_inscricao', true );
    
    if ( $status_inscricao == 'solicitacao-cancelamento' ) {
        $justificativa = get_post_meta( get_the_ID(), 'justificativa_solicitacao_cancelamento', true ); 
        echo esc_html( $justificativa );
    } else {
        echo 'Não existe solicitação de cancelamento para esta inscrição.';
    }

}

function salvaResumoInscricoes($idInscricao, $arrEstudantes, $arrEducadores, $arrAcompanhantes = null) {

    // Estudantes
    $qtdEstudantes = 0;
    $qtdEstudantesEspeciais = 0;
    $qtdEstudantesDieta = 0;
    $txtTurma = '';
    foreach ($arrEstudantes as $estudante) {    
        $qtdEstudantes += count($estudante['alunosTurma']);
        $turma = explode(' - ', $estudante['nomeTurma']);
        $txtTurma .= $turma[1] . ', ';

        foreach ($estudante['alunosTurma'] as $aluno) {
            if ($aluno['possuiDeficiencia'] == 1) {
                $qtdEstudantesEspeciais += 1;
            }
            if ($aluno['possuiDieta'] == 1) {
                $qtdEstudantesDieta += 1;
            }
        }
    }
    $txtTurma = substr($txtTurma, 0, -2);

    // Educadores
    $qtdEducadoresEspeciais = 0;
    $qtdEducadoresDieta = 0;
    $qtdEducadores = count($arrEducadores);
    foreach ($arrEducadores as $educador) {
        if ($educador['necessidades']) {
            $qtdEducadoresEspeciais += 1;
        }
        if ($educador['dieta']) {
            $qtdEducadoresDieta += 1;
        }
    }

    // Acompanhantes
    $qtdAcompanhantesEspeciais = 0;
    $qtdAcompanhantesDieta = 0;
    $qtdAcompanhantes = count($arrAcompanhantes);
    foreach ($arrAcompanhantes as $acompanhante) {
        if ($acompanhante['necessidades']) {
            $qtdAcompanhantesEspeciais += 1;
        }
        if ($acompanhante['dieta']) {
            $qtdAcompanhantesDieta += 1;
        }
    }

    $post = get_post($idInscricao);
    $conteudo = $post->post_content;

    $txtAdd = $qtdEstudantes.' Estudantes (Turma(s) '.$txtTurma.'):<br>' .
              ' - '.$qtdEstudantesEspeciais.' estudantes com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdEstudantesDieta.' estudantes com dieta especial ou restrição alimentar.<br>'.
              $qtdEducadores.' Educadores:<br>'.
              ' - '.$qtdEducadoresEspeciais.' educador com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdEducadoresDieta.' educador com dieta especial ou restrição alimentar.<br>';

    if ($arrAcompanhantes && count($arrAcompanhantes) > 0) {
        $txtAdd .= $qtdAcompanhantes.' outros:<br>'.
              ' - '.$qtdAcompanhantesEspeciais.' com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdAcompanhantesDieta.' com dieta especial ou restrição alimentar.<br><br>';
    }

    $novoConteudo = $txtAdd . $conteudo;

    wp_update_post(array('ID' => $idInscricao, 'post_content' => $novoConteudo));

}

function atualizaResumoInscricoes($idInscricao, $arrEstudantes, $arrEducadores, $arrAcompanhantes = null) {
   
    // Estudantes
    $qtdEstudantes = 0;
    $qtdEstudantesEspeciais = 0;
    $qtdEstudantesDieta = 0;
    $txtTurma = '';
    foreach ($arrEstudantes as $estudante) {    
        $qtdEstudantes += count($estudante['alunosTurma']);
        $turma = explode(' - ', $estudante['nomeTurma']);
        $txtTurma .= $turma[1] . ', ';

        foreach ($estudante['alunosTurma'] as $aluno) {
            if ($aluno['possuiDeficiencia'] == 1) {
                $qtdEstudantesEspeciais += 1;
            }
            if ($aluno['possuiDieta'] == 1) {
                $qtdEstudantesDieta += 1;
            }
        }
    }
    $txtTurma = substr($txtTurma, 0, -2);

    // Educadores
    $qtdEducadoresEspeciais = 0;
    $qtdEducadoresDieta = 0;
    $qtdEducadores = count($arrEducadores);
    foreach($arrEducadores as $educador) {
        if ($educador->necessidades) {
            $qtdEducadoresEspeciais += 1;
        }
        if ($educador->dieta) {
            $qtdEducadoresDieta += 1;
        }
    }

    // Acompanhantes
    $qtdAcompanhantesEspeciais = 0;
    $qtdAcompanhantesDieta = 0;
    $qtdAcompanhantes = count($arrAcompanhantes);
    foreach ($arrAcompanhantes as $acompanhante) {
        if ($acompanhante->necessidades) {
            $qtdAcompanhantesEspeciais += 1;
        }
        if ($acompanhante->dieta) {
            $qtdAcompanhantesDieta += 1;
        }
    }

    $post = get_post($idInscricao);
    $conteudo = $post->post_content;

    $txtAdd = $qtdEstudantes.' Estudantes (Turma(s) '.$txtTurma.'):<br>' .
              ' - '.$qtdEstudantesEspeciais.' estudantes com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdEstudantesDieta.' estudantes com dieta especial ou restrição alimentar.<br>'.
              $qtdEducadores.' Educadores:<br>'.
              ' - '.$qtdEducadoresEspeciais.' educador com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdEducadoresDieta.' educador com dieta especial ou restrição alimentar.<br>';

    if ($arrAcompanhantes && count($arrAcompanhantes) > 0) {
        $txtAdd .= $qtdAcompanhantes.' outros:<br>'.
              ' - '.$qtdAcompanhantesEspeciais.' com deficiência ou necessidade especial.<br>'.
              ' - '.$qtdAcompanhantesDieta.' com dieta especial ou restrição alimentar.<br><br>';
    }

    $novoConteudo = $txtAdd .'<br>'. $conteudo;

    wp_update_post(array('ID' => $idInscricao, 'post_content' => $novoConteudo));

}

function retornaStatusInscricao($sta){
    switch($sta){
        case 'novo':
            return 'Nova Inscrição';
        break;
        case 'inscricao-confirmada':
            return 'Inscrição confirmada';
        break;
        case 'realizado':
            return 'Passeio já realizado';
        break;
        case 'solicitacao-cancelamento':
            return 'Solicitação de cancelamento';
        break;
        case 'cancelado':
            return 'Cancelado';
        break;
        case 'aguardando-autorizacoes':
            return 'Aguardando autorizações';
        break;
    }
}

function add_novas_colunas_inscricao($columns) {
  $new_columns = array(
    'cb' => '<input type="checkbox" />', // Checkbox para seleção
    'title' => __('Nome do Roteiro'), // Coluna do título
    'ue' => __('Unidade Educacional'), // Sua nova coluna
    'total_insc' => __('Total de Inscritos'), // Coluna do total de inscritos
    'data_res' => __('Data da Reserva'), // Coluna da data  da reserva  
    'date' => __('Data da Solicitação'), // Coluna da data da solicitação    
    'status' => __('Status')// Coluna de status
  );
  return $new_columns;
}

//Aqui você insere o código para exibir o conteúdo da sua coluna.
function conteudo_novas_colunas_iscricao($column, $post_id) {
  
  switch ($column) {
    case 'ue':
        echo get_post_meta($post_id, 'nome_da_unidade_educacional', true);
    break;
    case 'total_insc':
        echo getQtdParticipantes($post_id);
    break;
    case 'data_res':
        $data = get_post_meta($post_id, 'data_reservada_para_o_roteiro', true);
        echo date('d/m/Y', strtotime($data));
    break;
    case 'status':
        echo retornaStatusInscricao(get_post_meta($post_id, 'status_inscricao', true));
    break;
  }
}
// Função para ordenar as colunas da tabela
function colunas_ordenaveis($columns) {
    $columns['data_res'] = 'Data da Reserva';
    $columns['data'] = 'Data da Solicitação';
    return $columns;
}

function exibir_botao_lista_participantes() {
    $link = add_query_arg([
        'action' => 'gerar_lista_participantes',
        'post' => get_the_ID()
    ], admin_url( 'admin-post.php' ));
    ?>
    <div class="acf-actions">
        <a href="<?php echo esc_url( $link ); ?>" class="btn btn-outline-success-admin" id="emitir-lista-participantes">
            <span class="dashicons dashicons-printer"></span>
            Emitir Lista de Participantes
        </a>
    </div>
    <?php
}

function botao_gerar_lista_presenca() { ?>
    <div class="acf-actions">
        <input type="file" name="doc-lista-presenca" id="file-input-lista-presenca" accept=".pdf, .doc, docx, .odt">
        <label for="file-input-lista-presenca" class="btn btn-outline-success-admin">
            <span class="dashicons dashicons-printer"></span>
            Importar planilha
        </label>
    </div>
    
    <?php
}

function exibe_planilha_enviada_lista_presenca(){

    $idPost = get_the_ID();

    $url_lista = add_query_arg([
        'action' => 'baixa_arquivo_lista_presenca',
        'id_inscricao' => $idPost
    ], admin_url( 'admin-ajax.php' ) );

    // $url_arquivo = get_template_directory_uri() . "/storage/lista-presenca}/{$idPost}/lista_presenca_{$idPost}.pdf";
    $upload_dir = get_theme_file_path( 'storage' );
    $url_arquivo = $upload_dir."/lista-presenca/recebida/{$idPost}/lista_presenca_{$idPost}.pdf"; 

    if ( !file_exists( $url_arquivo ) ) {
        echo 'Sem arquivo para baixar';
    } else {
        date_default_timezone_set('America/Sao_Paulo');
        $timestamp = filemtime($url_arquivo);
        $data_modificacao = date('d/m/Y H:i:s', $timestamp);
        echo '<a href="'.esc_url( $url_lista ).'">Lista de Presença</a> - Enviado em: '.$data_modificacao;
    }
}

function exibe_lista_de_presenca(){
    $idIsncricao = get_the_ID();
    $arrTurmas = get_post_meta( $idIsncricao, 'dados_turmas', true );
    $arrEducadores = get_post_meta( $idIsncricao, 'dados_educadores', true );
    $arrAcompanhantes = get_post_meta( $idIsncricao, 'dados_acompanhantes', true );

    get_template_part( 'src/Views/shortcodes/templates/lista-presenca', null, [
        'idIsncricao' => $idIsncricao,
        'arrTurmas' => $arrTurmas,
        'arrEducadores'=>$arrEducadores,
        'arrAcompanhantes'=>$arrAcompanhantes
    ]);
}

function getQtdParticipantes($post_id){

    $html = '';

    $arrTurmas = get_post_meta($post_id, 'dados_turmas', true);
    $arrEducadores = get_post_meta($post_id, 'dados_educadores', true);
    if(!$arrEducadores){
        $arrEducadores = [];
    }
    $arrAcompanhantes = get_post_meta($post_id, 'dados_acompanhantes', true);
    if(!$arrAcompanhantes){
        $arrAcompanhantes = [];
    }

    $qtdEstudantes = 0;
    $qtdEduAco = 0;

    if(!empty($arrTurmas)) {
        foreach($arrTurmas as $turma){
            $arrAlunos = $turma['alunosTurma'];
            $qtdEstudantes += count($arrAlunos);
        }
    }

    if(!empty($arrEducadores)) {
        $qtdEduAco = count($arrEducadores);
        
        if (isset($arrAcompanhantes) && count($arrAcompanhantes) > 0) {
            $qtdEduAco = count($arrEducadores) + count($arrAcompanhantes);
        }
    }

    $totalParticipantes = $qtdEstudantes + $qtdEduAco;
       
    $html .= $totalParticipantes.' Participantes<br>';
    $html .= '( '.$qtdEstudantes.' Estudantes, '.$qtdEduAco.' Educadores/Acompanhantes )';
        

    return $html;

}

// Adiciona filtro de datas da reserva
add_action('restrict_manage_posts', function($post_type) {
    global $wpdb;

    if ($post_type === 'post_inscricao') {
    
    // Busca todas as datas únicas cadastradas no meta 'data_reservada_para_o_roteiro'
    $dates = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM $wpdb->postmeta 
        WHERE meta_key = 'data_reservada_para_o_roteiro' 
        ORDER BY meta_value ASC
    ");

    $current_date = isset($_GET['data_reservada_para_o_roteiro']) ? $_GET['data_reservada_para_o_roteiro'] : '';

    echo '<select name="data_reservada_para_o_roteiro">';
    echo '<option value="">Datas da Reserva</option>';
    foreach ($dates as $date) {
        if (empty($date)) {
            continue;
        }
       echo '<option value="' . esc_attr($date) . '" ' . selected($current_date, $date, false) . '>' . esc_html(date('d/m/Y', strtotime($date))) . '</option>';
    }
    echo '</select>';

    } else {
        return;
    }
});

// Aplica o filtro
add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        $query->is_main_query() &&
        !empty($_GET['data_reservada_para_o_roteiro'])
    ) {
        $query->set('meta_query', [
            [
                'key' => 'data_reservada_para_o_roteiro',
                'value' => sanitize_text_field($_GET['data_reservada_para_o_roteiro']),
                'compare' => 'LIKE',
            ]
        ]);
    }
});
// ############################################################################
// Adiciona filtro de datas do agendamento no admin
add_action('restrict_manage_posts', function($post_type) {
    global $wpdb;

    if ($post_type === 'post_inscricao') {
    
    // Busca todas as datas únicas cadastradas no meta 'data_reservada_para_o_roteiro'
    $dates = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM $wpdb->postmeta 
        WHERE meta_key = 'data_da_solicitacao' 
        ORDER BY meta_value ASC
    ");

    $current_date = isset($_GET['data_da_solicitacao']) ? $_GET['data_da_solicitacao'] : '';

    echo '<select name="data_da_solicitacao">';
    echo '<option value="">Datas de Agendamento</option>';

    foreach ($dates as $date) {
        if (empty($date)) {
            continue;
        }
       echo '<option value="' . esc_attr($date) . '" ' . selected($current_date, $date, false) . '>' . esc_html($date) . '</option>';
    }
    echo '</select>';

    } else {
        return;
    }
});

// Aplica o filtro
add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (
        is_admin() &&
        $pagenow === 'edit.php' &&
        $query->is_main_query() &&
        !empty($_GET['data_da_solicitacao'])
    ) {
        $data = sanitize_text_field($_GET['data_da_solicitacao']);
        $query->set('meta_query', [
            [
                'key' => 'data_da_solicitacao',
                'value' => $data,
                'compare' => 'LIKE',
            ]
        ]);
    }
});

// ############################################################################
// Adiciona o filtro Roteiro
add_action('restrict_manage_posts', function($post_type) {
    // Se quiser restringir para um CPT específico, troque 'post' pelo slug do seu CPT
    if ($post_type === 'post_inscricao') {

            $current_value = isset($_GET['id_roteiro_inscricao']) ? $_GET['id_roteiro_inscricao'] : '';

            $args = array(
                'post_type' => 'post_roteiro',
                'posts_per_page' => 100, // Exemplo: 5 posts por página
            );

            $posts = get_posts($args);
            $options = array();
            foreach ($posts as $post) {
                $options[$post->ID] = $post->post_title;
            }

            echo '<select name="id_roteiro_inscricao">';
            echo '<option value="">Roteiro</option>';
            foreach ($options as $value => $label) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($value),
                    selected($current_value, $value, false),
                    esc_html($label)
                );
            }
            echo '</select>';
        
    } else {
        return;
    }
});

// Aplica o filtro Roteiro na query
add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && !empty($_GET['id_roteiro_inscricao'])) {
        $query->set('meta_query', [
            [
                'key' => 'id_roteiro_inscricao',
                'value' => sanitize_text_field($_GET['id_roteiro_inscricao']),
            ]
        ]);
    }
});

// ############################################################################
// Adiciona o filtro Status
add_action('restrict_manage_posts', function($post_type) {
    // Se quiser restringir para um CPT específico, troque 'post' pelo slug do seu CPT
    if ($post_type === 'post_inscricao') {

            $current_value = isset($_GET['status_inscricao']) ? $_GET['status_inscricao'] : '';

            $options = [
                '' => 'Status',
                'Nova Inscrição' => 'Nova Inscrição',
                'Aguardando autorização' => 'Aguardando autorização',
                'Inscrição confirmada' => 'Inscrição confirmada',
                'Cancelado' => 'Cancelado'
            ];

            echo '<select name="status_inscricao">';
            foreach ($options as $value => $label) {
                printf(
                    '<option value="%s" %s>%s</option>',
                    esc_attr($value),
                    selected($current_value, $value, false),
                    esc_html($label)
                );
            }
            echo '</select>';
        
    } else {
        return;
    }
});

// Aplica o filtro status na query
add_action('pre_get_posts', function($query) {
    global $pagenow;

    if (is_admin() && $pagenow === 'edit.php' && $query->is_main_query() && !empty($_GET['status_inscricao'])) {
        $query->set('meta_query', [
            [
                'key' => 'status_inscricao',
                'value' => sanitize_text_field($_GET['status_inscricao']),
            ]
        ]);
    }
});

