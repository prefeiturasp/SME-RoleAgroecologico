<?php

use App\Controllers\AgendamentoController;
use App\Controllers\RoteiroController;

if ( !is_user_logged_in() ) {
    wp_redirect( home_url() . '/login?redirect_to=%2Fagendamento%2F%3Frid%3D'.$_REQUEST['iID'] );
    exit;
} else {
    $acesso = null;
    $current_user = wp_get_current_user();
    foreach ( $current_user->allcaps as $key => $value ) {
        if ($key == 'acessar_lista_de_presenca') { 
            if($value){
                $acesso = true;
            }
        }
    }
    if (!$acesso) {
        wp_redirect( site_url() );
        exit;
    }
}

if(isset($_REQUEST['iid']) && isset($_REQUEST['rid'])){
    $inscricao_id = $_REQUEST['iid'];
    $roteiro_id = $_REQUEST['rid'];
} else {
    wp_redirect( site_url('agendamento-lista-presenca/') );
}

// Adiciona o script da lista de presença
wp_enqueue_script( 'lista-presenca' );

$agendamento = new AgendamentoController( $roteiro_id );
$inscricao = AgendamentoController::get_inscricao($inscricao_id);
$roteiro = new RoteiroController( $roteiro_id );

$dataAgendamento = $inscricao['data_agendamento'];
$obsRole = get_post_meta($inscricao_id, 'observacoes_do_role', true);

$turmas = get_post_meta($inscricao_id, 'dados_turmas', true);
$qtdTurmas = count($turmas);

$arrConfPresenca = [];
$verificaPresencaArrAlunos = false;
foreach ($turmas as $turma) {
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
    $arrConfPresenca[] = array("idTurma" => $turma['idTurma'], "nomeTurma" => $turma['nomeTurma'], "alunosTurma" => $arrAlunos);
}

if($verificaPresencaArrAlunos){
    update_post_meta($inscricao_id, 'dados_turmas', $arrConfPresenca);
}


$educadores = $inscricao['educadores'];
$listaPresencaEdu = [];
$verificaPresencaArrEdu = false;
foreach ($educadores as $edu) {
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

$acompanhantes = $inscricao['acompanhantes'];
$listaPresencaAco = [];
$verificaPresencaArrAco = false;
foreach ($acompanhantes as $aco) {
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


$arrEdAc = !empty($inscricao['acompanhantes']) ? array_merge_recursive($listaPresencaEdu, $listaPresencaAco) : $listaPresencaEdu; 
$listaPresencaAcomp = [];
foreach ($arrEdAc as $acompanhante) {
    if(!isset($acompanhante['confirmacaoPresenca'])){
        $acompanhante['confirmacaoPresenca'] = true;
    } else if(isset($acompanhante['confirmacaoPresenca']) && $acompanhante['confirmacaoPresenca'] == true){
        $acompanhante['confirmacaoPresenca'] = true;
    } else{
        $acompanhante['confirmacaoPresenca'] = false;
    }
  $listaPresencaAcomp[] = $acompanhante;  
}

?>

<section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
    <div class="row">
        <button class="col-md-2 mb-4 btn-voltar" x-on:click="acionaBtnVoltarEdit()">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
            Voltar
        </button>
        <div class="col-md-10 titulo d-flex flex-column flex-md-row align-items-start align-align-items-md-center justify-content-center mb-2">
            <h1><span>Rolê:</span> <?= esc_html( get_the_title($roteiro_id) ) ?></h1>
        </div>
    </div>
</section>

<section>
    <div class="container my-5" id="info-lista-presenca">
        <!-- Informações principais -->
        <div class="row">
            <div class="col-md">
                <?php
                    extract( $args );
                    $id_unidade = get_post_meta( $inscricao_id, 'codigo_eol_ue', true );
                    $nome_unidade = get_post_meta( $inscricao_id, 'nome_da_unidade_educacional', true );
                ?>
                <p>
                    <strong>Data da vivência pedagógica:</strong>
                    <span class="destaque-data-vivencia"><?= $dataAgendamento; ?> - <?= !get_field( 'roteiro_de_tempo', $roteiro_id ) ? 'Período Integral' : 'Período Parcial'; ?></span>
                </p>
                <p><strong>Local:</strong> <?php echo esc_html( get_the_title( $roteiro_id ) ); ?></p>
                <p><strong>Unidade:</strong> <?php echo esc_html( $id_unidade.' - '.$nome_unidade ); ?></p>
            </div>
        </div>
    </div>
</section>


<?php 
$linkGeraLista = add_query_arg([
    'action' => 'gerar_planilha_lista_presenca',
    'post' => $inscricao_id
], admin_url( 'admin-post.php' ));
?>

<section class="container justify-content-center mb-4 mt-4">
    <div class="row">
        <div class="col-md-6" id="detalhe-lista-presenca">
            <h1>Desmarque os ausentes no Rolê.</h1>
            <hr>
        </div>
        <div class="col-md-6 text-right">
            <a href="<?php echo esc_url( $linkGeraLista ); ?>" class="btn btn-outline-success shadow" id="btn-gerar-lista-presenca">Baixar planilha</a>
            <button class="btn btn-success shadow" id="btn-importar-planilha">Importar planilha</button>
        </div>
    </div>
    <br><br>
    <form id="salva-lista-presenca" enctype="multipart/form-data">
        <input type="hidden" value="<?= $inscricao_id; ?>" name="post_id" id="post_id">
        <input type="hidden" value="<?= $qtdTurmas; ?>" name="qtd_turma" id="qtd_turma">
        <div class="row" id="conteudo-importar-planilha">
                <div class="col">
                
                        <div class="dropzone" id="enviaArquivo">
                            <!-- Ícone de pasta -->
                            <img src="<?= URL_IMG_THEME ?>/icons/icons8-folder-64.png" alt="Pasta">

                            <!-- Texto principal -->
                            <h5><strong>Importar planilha da lista de presença</strong></h5>

                            <!-- Texto secundário -->
                            <p>baixe a <a href="<?php echo esc_url( $linkGeraLista ); ?>" class="negrito-verde">planilha</a> e suba a nova planilha com os dados preenchidos</p>

                            <!-- Input de arquivo escondido -->
                            <input type="file" name="doc-lista-presenca" id="arquivo-input-lista-presenca" accept=".pdf, .doc, docx, .odt">

                            <!-- Botão estilizado que aciona o input -->
                            <label for="arquivo-input-lista-presenca" class="btn btn-selecionar">Selecionar arquivos</label>

                            <!-- Nome do arquivo exibido -->
                            <div id="file-name" class="file-name"></div>
                        </div>
                    
                        <div class="dropzone" id="filePreview"></div>

                </div>
            </div>

        <div class="row">
            <?php
            $c=0;
            foreach($arrConfPresenca as $turma): $idTurma = $turma['idTurma']; ?> 
                <div class="col-md-6">             
                    <div class="card card-custom box-border">
                        <table class="table table-sm tabela-turma" data-id="<?php echo esc_attr( $idTurma ); ?>">
                            <thead>
                                <tr>
                                    <th class="no-border-top txt-green" colspan="2">Cód. EOL - Nome do Estudante - Turma</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $ci = 1; foreach($turma['alunosTurma'] as $aluno):  
                                    $confPresenca = esc_attr( $aluno['confirmacaoPresenca']);
                                    ?>
                                    <tr>
                                        <th>
                                            <input type="checkbox" class="chkb-lista-presenca-alunos" name="ckb-alunos-t<?= $c+1 ?>" value="<?= $aluno['codigoAluno']; ?>" id="al-<?= $aluno['codigoAluno'] ?>-tu-<?= $idTurma ?>" <?php echo $confPresenca ? 'checked' : '';?> onClick="salvaCheckAlunoTurma(this)">
                                        </th>
                                        <td><label class="txt-green" for="al-<?= $aluno['codigoAluno'] ?>-tu-<?= $idTurma ?>"><?= $aluno['codigoAluno'] .' - '. mb_convert_case($aluno['nomeAluno'], MB_CASE_TITLE) .' - <strong>'. $turma['nomeTurma'].'</strong>'; ?></label></td>
                                    </tr>
                                <?php $ci++; endforeach; ?>
                            </tbody>
                        </table>                         
                    </div>
                    <p>&nbsp;</p>
                </div>
            <?php $c++; endforeach; ?>
            <div class="col-md-6">
                <div class="card card-custom box-border">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="no-border-top txt-green" colspan="2">RF/CPF - Nome Completo - Data de Nascimento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $c = 0;
                                foreach($listaPresencaAcomp as $edu): 
                                $confPresencaAcoEdu = esc_attr( $edu['confirmacaoPresenca']);    
                            ?>
                                <tr>
                                    <th><input type="checkbox" name="chk-educadores-acompanhantes" value="<?php echo esc_attr( $edu['rf'] ).'-'.esc_attr( $edu['tipo'] );; ?>" id="chk-edu-acomp<?=$edu['rf'].'-'.$c;?>" <?php echo $confPresencaAcoEdu ? 'checked' : '';?> onClick="salvaCheckAcoEdu(this)"></th>
                                    <td class="txt-green"><label class="txt-green" for="chk-edu-acomp<?=$edu['rf'].'-'.$c;?>"><?= $edu['rf'] .' - '. mb_convert_case($edu['nome'], MB_CASE_TITLE) . ' - ' . esc_html(date('d/m/Y', strtotime($edu['data_nascimento']))); ?></label></td>
                                </tr>
                            <?php $c++; endforeach; ?>
                        </tbody>
                    </table>                         
                </div>
            </div>
        </div>
    </section>

    <section class="container">
        <div class="row mt-5" id="observacoes-role">
            <div class="col-12">
                <div class="form-group">
                    <label for="obs-role">Observações do Rolê</label>
                    <textarea class="form-control" id="obs-role" rows="5" placeholder="Informe se houver alunos ou acompanhantes não previstos"><?= $obsRole; ?></textarea>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-center">
                <button type="submit" class="btn btn-success shadow" id="btn-salvar-obs-role">Salvar informações</button>
            </div>
        </div>
    </section>
</form>

<br><br>

<script>
jQuery(function ($) {
    jQuery("#filePreview").hide(); 
    jQuery("#conteudo-importar-planilha").hide(); 

    $btnImportarPlanilha = $('#btn-importar-planilha');
    $btnImportarPlanilha.on('click', function (e) {
        jQuery("#conteudo-importar-planilha").toggle();
    });

    $btnSelecionarArquivo = $('#arquivo-input-lista-presenca');

     $btnSelecionarArquivo.on('change', function (e) {

        jQuery("#filePreview").show();
        jQuery("#enviaArquivo").hide();

        filePreview.innerHTML = ''; // limpa preview anterior
        if (this.files.length > 0) {

            const file = this.files[0];
            const fileSize = (file.size / 1024 / 1024).toFixed(1); // MB
            const extension = file.name.split('.').pop().toLowerCase();
            const tamArquivoAnexado = 5; //MB
            if(fileSize > tamArquivoAnexado ){
                setTimeout(function(){
                    $('.remove-file').trigger('click');
                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text: "O tamanho do arquivo, não poderá ser maior que 5 MB."
                    });
                },100);
            }

            // ícone genérico (pode trocar conforme extensão)
            let icon = `https://img.icons8.com/fluency/96/000000/${extension}.png`;

            // cria o card
            const preview = document.createElement('div');
            preview.classList.add('file-preview');
            preview.innerHTML = `
            <img src="${icon}" alt="file icon" onerror="this.src='https://img.icons8.com/fluency/96/000000/file.png'">
            <div class="file-info">
                <div class="file-name">${file.name}</div>
                <span class="tamArquivo">${fileSize} MB</span>
            </div>
            <span class="remove-file">&times;</span>
            `;

            // botão remover
            preview.querySelector('.remove-file').addEventListener('click', () => {
                jQuery("#enviaArquivo").show();
                jQuery("#filePreview").hide();  
                fileInput.value = '';
                filePreview.innerHTML = '';
            });

            filePreview.appendChild(preview);
        }
        });

});
</script>