<?php 

use App\Controllers\AgendamentoController;
use App\Controllers\RoteiroController;

wp_enqueue_script( 'alpine' );
wp_enqueue_script( 'agendamento' );

if ( !is_user_logged_in() ) {
    wp_redirect( home_url() . '/login?redirect_to=%2Fagendamento%2F%3Frid%3D'.$_REQUEST['iID'] );
    exit;
} else {
    $acesso = null;
    $current_user = wp_get_current_user();
    foreach ( $current_user->allcaps as $key => $value ) {
        if ($key == 'edit_inscricaos') { 
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

if(isset($_REQUEST['rid']) && isset($_REQUEST['iid'])){
    $inscricao_id = $_REQUEST['iid'];
    $roteiro_id = $_REQUEST['rid'];
}

$agendamento = new AgendamentoController( $roteiro_id );
$inscricao = AgendamentoController::get_inscricao($inscricao_id);
$roteiro = new RoteiroController( $roteiro_id );

$atrativos = $roteiro->get_atrativos_local();
$aspectos = $roteiro->get_aspectos_local();
$datas_ofertadas = $roteiro->get_datas_ofertadas();

$dataAgendamento = $inscricao['data_agendamento'];

$data_objeto = DateTime::createFromFormat("d/m/Y", $dataAgendamento);
$data_iso = $data_objeto->format("Y-m-d");

$dataPrazo = date("d/m/Y", strtotime("-7 days", strtotime($data_iso)));

$horarioSaida = $inscricao['horario_saida'];
$horarioRetorno = $inscricao['horario_retorno'];

$qtdAlunos = 0;
$qtdEducadores = $inscricao['acompanhantes'] ? count($inscricao['educadores']) : 0;
$qtdAcompanhante = $inscricao['acompanhantes'] ? count($inscricao['acompanhantes']) : 0;

$qtdAlunosAcessibilidade = 0;
$qtdAlunosDietas = 0;

foreach($inscricao['turmas'] as $turma){
    foreach($turma['alunosTurma'] as $alunos){
        if($alunos['possuiDeficiencia']){
            $qtdAlunosAcessibilidade++;
        }
        $qtdAlunos++;
    }
}

$totalParticipants = $qtdAlunos+$qtdEducadores+$qtdAcompanhante;

//Total de autorizações enviadas
$total_autorizacoes_enviadas = 0;
if ( isset( $inscricao['turmas'] ) && is_array( $inscricao['turmas'] ) ) {
    $array_alunos = array_column( $inscricao['turmas'], 'alunosTurma' );
    $total_autorizacoes_enviadas = array_reduce( $array_alunos, function ($count, $alunos) {
        foreach ( $alunos as $aluno ) {
            if ( isset($aluno['situacaoFicha']) && !empty( $aluno['situacaoFicha'] ) ) {
                $count++;
            }
        }
        return $count;
    }, 0);
}

get_header();

?>

<div class="page-wrapper content-wrapper page-agendamento my-2">

    <section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
        <div class="row">
            <a href="<?php echo get_previous_page_url(); ?>" class="col-md-2 mb-4 btn-voltar">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                Voltar
            </a>
            <div class="col-md-10 titulo d-flex flex-column flex-md-row align-items-start align-align-items-md-center justify-content-center mb-2">
                <h1><span>Rolê:</span> <?= esc_html( get_the_title($roteiro_id) ) ?></h1>
            </div>
        </div>
    </section>


    <section class="container justify-content-center mb-4 mt-4" id="agendamento-info">
        <div class="row d-flex justify-content-start align-items-center">
            <div class="col-md-9" id="detalhe-roteiro__tags">
                <div class="row">
                    <?php if ( $tags = $agendamento->get_tags_roteiro() ): 
                        foreach ( $tags as $tag ) : ?>
                            <span class="tag col-md col-auto">
                                <?php if ( $imagem_tag = get_field( 'icone-tax', $tag ) ) : ?>
                                    <img src="<?php echo esc_url( $imagem_tag ); ?>" class="mr-2">
                                <?php endif; ?>
                                <?php echo esc_html( $tag->name ); ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                </div>
            </div>
            <div class="col-md-3 mt-4 mt-sm-0" id="capacidade-roteiro">
                <span>Total de autorizações enviadas: <?= "{$total_autorizacoes_enviadas}/{$qtdAlunos}" ?></span>
            </div>
        </div>
    </section>

    <section>
        <?php
            extract( $args );

            $unidade_educacional = get_user_meta( get_current_user_id(), 'unidade_locacao', true );
        ?>
        <div class="container my-5" id="confirmacoes-autorizacoes">

        <!-- Informações principais -->
        <div class="row">
            <div class="col-md-6">
                <h5>Informações do agendamento</h5>
                <p>
                    <strong>Data da vivência pedagógica:</strong>
                    <span id="data-agendamento"><span id="dataAgendamento"><?= $dataAgendamento; ?></span> - <?php echo !get_field( 'roteiro_de_tempo', $roteiro_id ) ? 'Período Integral' : 'Período Parcial'; ?></span>
                </p>
                <p><strong>Local:</strong> <?php echo esc_html( get_the_title( $roteiro_id ) ); ?></p>
                <p><strong>Unidade:</strong> <?php echo esc_html( $unidade_educacional['nomeUnidade'] ); ?></p>
                <p><strong>Quantidade de inscritos:</strong> <span id="qtdEstudantes"><?= $qtdAlunos; ?></span> estudantes, <span id="qtdProfessores"><?= $qtdEducadores; ?></span> professores, <span id="qtdOutros"><?= $qtdAcompanhante; ?></span> outros = <span id="qtdParticipantes"><?= $totalParticipants; ?></span> participantes</p>
                <p><strong>Horário de saída da unidade:</strong> <span id="horarioSaida"><?= $horarioSaida; ?></span></p>
                <p><strong>Horário previsto para retorno à unidade:</strong> <span id="horarioRetorno"><?= $horarioRetorno; ?></span></p>
                <p><strong>Alunos com acessibilidade/cadeirantes:</strong> <span id="qtdAlunosComAcessibilidade"><?= $qtdAlunosAcessibilidade; ?></span></p>
                <p><strong>Alunos com dieta:</strong> <span id="qtdAlunosComDieta"><?= $qtdAlunosDietas; ?></span></p>
                <?php if ( $lembrete = get_field( 'texto_lembrete', 'options' ) ) : ?>
                    <p>
                        <strong>
                            <?php if ( !empty( $lembrete['texto'] ) ) : ?>
                                <?php echo esc_html( $lembrete['texto'] ); ?>
                            <?php endif; ?>
                            <?php if ( !empty( $lembrete['link'] ) ) : ?>
                                <a href="<?php echo esc_url( $lembrete['link']['url'] ); ?>" target="<?php echo esc_html( $lembrete['link']['target'] ); ?>">
                                    <?php echo esc_html( $lembrete['link']['title'] ); ?>
                                </a>
                            <?php endif; ?>
                        </strong>
                    </p>
                <?php endif; ?>

                <?php if ( $atrativos ) : ?>
                    <br>
                        <div id="detalhe-roteiro__atrativos" class="mb-4">
                            <h5>Atrativos encontrados neste Roteiro</h5>
                            <div class="row mb-4" id="listagem-atrativos">
                                <?php foreach ( $atrativos as $atrativo ) : ?>
                                    <span class="atrativo-item col-6">
                                        <?php if ( $icone_atrativo = get_field( 'icone-tax', $atrativo ) ) : ?>
                                            <img src="<?php echo esc_url( $icone_atrativo ); ?>">
                                        <?php endif; ?>
                                        <?php echo esc_html( $atrativo->name ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $aspectos ) : ?>
                        <div id="detalhe-roteiro__atrativos" class="mt-5">
                            <h5>Particularidades deste Roteiro</h5>
                            <div class="row mb-4" id="listagem-atrativos">
                                <?php foreach ( $aspectos as $aspecto ) : ?>
                                    <span class="atrativo-item col-6">
                                        <?php if ( $icone_particularidade = get_field( 'icone-tax', $aspecto ) ) : ?>
                                            <img src="<?php echo esc_url( $icone_atrativo ); ?>">
                                        <?php endif; ?>
                                        <?php echo esc_html( $aspecto->name ); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p>&nbsp;</p>
                    <!-- Lista os Educadores e acompanhantes -->        
                    <p>Educadores e acompanhantes:</p>
                    <div class="card card-custom">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th colspan="3">RF/CPF e Nome Completo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $arrEduAcomp = !empty($inscricao['acompanhantes']) ? array_merge_recursive($inscricao['educadores'], $inscricao['acompanhantes']) : $inscricao['educadores']; 
                                    foreach($arrEduAcomp as $edu):  
                                        
                                ?>
                                    <tr>
                                        <td><?= $edu['rf'] .' - '. mb_convert_case($edu['nome'], MB_CASE_TITLE) . ' - ' . esc_html(date('d/m/Y', strtotime($edu['data_nascimento']))); ?></td>
                                        <td>
                                            <?php if(isset($edu['necessidades']) && !empty($edu['necessidades'])): ?>
                                                <img class="icon-tb-alunos" src="<?= URL_IMG_THEME ?>/icons/acessibilidade.png" data-toggle="tooltip" data-placement="right" title="<?=$edu['necessidades'];?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(isset($edu['dieta']) && !empty($edu['dieta'])): ?>
                                                <img class="icon-tb-alunos" src="<?= URL_IMG_THEME ?>/icons/dieta.png" data-toggle="tooltip" data-placement="right" title="<?=$edu['dieta'];?>">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>                         
                    </div>
                    <p>&nbsp;</p>

            </div>

            <div class="col-md-6">
                <p>
                    <strong>Prazo máximo para envio das autorizações:</strong>
                    <span id="prazo-maximo"><?= $dataPrazo; ?></span>
                </p>
                <?php foreach($inscricao['turmas'] as $turma): ?>              
                    <p>Marque os estudantes da turma <strong><?= $turma['nomeTurma']?></strong> que você enviará as autorizações assinadas pelos pais ou responsáveis:</p>
                    <div class="card card-custom">
                        <table class="table table-sm tabela-turma" data-id="<?php echo esc_attr( $turma['idTurma'] ); ?>">
                            <thead>
                                <tr>
                                    <th colspan="5">Cód. EOL e Nome do Estudante</th>
                                    <th colspan="1">Autorizações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 0;
                                foreach($turma['alunosTurma'] as $aluno):
                                    $ficha_url = add_query_arg([
                                        'action' => 'gerar_ficha_autorizacao_aluno',
                                        'aluno' => $aluno['codigoAluno'],
                                        'inscricao' => $inscricao_id
                                    ], admin_url( 'admin-ajax.php' ) );

                                    ?>
                                    <tr>
                                        <th><input type="checkbox" name="autorizacao" value="<?php echo esc_attr( $aluno['codigoAluno'] ); ?>" id="aut-aluno-t<?=$turma['idTurma'].'-'.$i;?>"></th>
                                        <td><label for="aut-aluno-t<?=$turma['idTurma'].'-'.$i;?>"><?= $aluno['codigoAluno'] .' - '. mb_convert_case($aluno['nomeAluno'], MB_CASE_TITLE); ?></label></td>
                                        <td>
                                            <?php if(isset($aluno['possuiDeficiencia']) && $aluno['possuiDeficiencia'] == '1'): ?>
                                                <img class="icon-tb-alunos" src="<?= URL_IMG_THEME ?>/icons/acessibilidade.png">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if(isset($aluno['possuiDieta']) && $aluno['possuiDieta'] == '1'): ?>
                                                <img class="icon-tb-alunos" src="<?= URL_IMG_THEME ?>/icons/dieta.png" onclick="exibeModalDieta('<?= $aluno['nomeAluno'] ?>', '<?= $aluno['classificacaoDieta'] ?>')">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url( $ficha_url ); ?>" target="_blank" style="border-bottom: none;">
                                                <img class="icon-tb-alunos" src="<?= URL_IMG_THEME ?>/icons/icon-pdf.png">
                                            </a>
                                        </td>
                                        <td>
                                            <?php $status_ficha = get_status_ficha_aluno( $aluno['situacaoFicha'] ?? '' ) ?>
                                            <input type="hidden" id="sta-aluno-t<?=$turma['idTurma'].'-'.$i;?>" value="<?= esc_html( $status_ficha['texto'] ); ?>">
                                            <button class="btn btn-sm btn-tb-alunos <?php echo esc_html( $status_ficha['classe'] ); ?>">
                                                <?php echo esc_html( $status_ficha['texto'] ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php $i++; endforeach; ?>
                            </tbody>
                        </table>                         
                    </div>
                    <p>&nbsp;</p>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Educadores -->
        <div class="row">
            
            <div class="col-md-6 text-right">
                <a href="<?= site_url('/edita-agendamento/?iid='.$inscricao['ID'].'&rid='.$inscricao['id_roteiro']);?>" target="_blank"  class="btn btn-lg shadow-sm btn-adc-rem-cancel">Adicionar/Remover Participantes</a>
            </div>
            <div class="col-md-6">
                <button
                    type="button"
                    class="btn btn-lg shadow-sm btn-adc-rem-cancel"
                    id="btn-solicitar-cancelamento"
                    <?php echo esc_html( !can_solicitar_cancelamento( $inscricao['ID'] ) ? 'disabled' : '' ); ?>
                    >
                    Solicitar Cancelamento
                </button>
            </div>
        </div>

        <div class="row col"><?php echo get_field( 'status_inscricao', $roteiro_id )?></div>

        <div class="row mt-5 d-none" id="justificativa-cancelamento-container">
            <div class="col-12">
                <div class="form-group">
                    <label for="justificativa-cancelamento">Insira uma justificativa para a Solicitação de Cancelamento <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="justificativa-cancelamento" rows="5" required></textarea>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-outline-success" id="cancelar-solicitacao">Desistir</button>
                <button class="btn btn-success" id="btn-enviar-cancelamento">Enviar solicitação</button>
            </div>
        </div>

        <p>&nbsp;</p>

        <div class="row">
            <div class="col">
                <?php if ( $data_iso >= date( 'Y-m-d' ) ) :  ?>
                    <div class="dropzone" id="enviaArquivo">
                        <!-- Ícone de pasta -->
                        <img src="<?= URL_IMG_THEME ?>/icons/icons8-folder-64.png" alt="Pasta">

                        <!-- Texto principal -->
                        <h5><strong>Cole aqui o(s) arquivo(s) para envio</strong></h5>

                        <!-- Texto secundário -->
                        <p>ou clique no botão abaixo para selecionar o arquivo no seu computador</p>

                        <!-- Input de arquivo escondido -->
                        <input type="file" id="file-input-meus-agendamentos" accept=".pdf, .doc, docx, .odt">

                        <!-- Botão estilizado que aciona o input -->
                        <label for="file-input-meus-agendamentos" class="btn btn-selecionar">Selecionar arquivos</label>

                        <!-- Nome do arquivo exibido -->
                        <div id="file-name" class="file-name"></div>
                    </div>
                
                    <div class="dropzone" id="filePreview"></div>
                <?php endif; ?>

                <!-- Botão de envio -->
                <div class="text-center">
                    <?php if ( $data_iso < date( 'Y-m-d' ) ) :  ?>
                        <strong class="text-danger">
                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                            O prazo para envio das autorizações já terminou.
                        </strong>
                    <?php else : ?>
                        <button class="btn btn-enviar-autorizacoes">Enviar Autorizações</button>
                    <?php endif; ?>
                </div>

            </div>
        </div>

</section>

  <script>
    function getQueryParam(key, defaultValue = null) {
        const params = new URLSearchParams(window.location.search);
        return params.has(key) ? params.get(key) : defaultValue;
    }

    function retornaIdStatusAutorizacao(idCheckbox){
        let arrInfo = idCheckbox.split('-');
        return 'sta-'+arrInfo[1]+'-'+arrInfo[2]+'-'+arrInfo[3];
    }


    jQuery(function ($) {

        let qtdAlunosChecked = false;
        jQuery("#filePreview").hide(); 

        // VALIDA OS CHECKBOXs DE SELEÇÃO DOS ALUNOS
        $(document).on('click', 'input[type="checkbox"][name="autorizacao"]', function() {
            if(this.checked){
                let idAlunoChk = this.id;
                let staAluno = retornaIdStatusAutorizacao(idAlunoChk);
                let status = $("#"+staAluno).val();
                if(status == "Validado"){
                    $('#'+idAlunoChk).prop('checked', false);
                    Swal.fire({icon: "warning",html:'<b>A autorização deste aluno, já foi enviada e validada!</b>',showConfirmButton: false,timer: 4000});
                } else {
                    qtdAlunosChecked = true;
                }
            } else {
                qtdAlunosChecked = false;
            }
        });

        $btnSelecionarArquivo = $('#file-input-meus-agendamentos');
        $btnSelecionarArquivo.on('click', function (e) {
            document.querySelectorAll('input[type="checkbox"][name="autorizacao"').forEach(checkbox => {
                if(checkbox.checked){
                    qtdAlunosChecked = true;
                }
            });

            if(!qtdAlunosChecked){
                Swal.fire({icon: "warning",html:'<b>É necessário selecionar no mínimo 1 estudante para enviar a(s) autorização(ões)!</b>',showConfirmButton: false,timer: 4000});
                event.preventDefault();
            }
        });

        $btnSelecionarArquivo.on('change', function (e) {
            filePreview.innerHTML = ''; // limpa preview anterior
            if (this.files.length > 0) {

                jQuery(".btn-enviar-autorizacoes").attr("disabled", true);
                
                jQuery("#enviaArquivo").hide();
                jQuery("#filePreview").show(); 

                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(1); // MB
                const extension = file.name.split('.').pop().toLowerCase();
                const tamArquivoAnexado = 15; //MB
                if(fileSize > tamArquivoAnexado ){
                    setTimeout(function(){
                        $('.remove-file').trigger('click');
                        Swal.fire({
                            icon: "warning",
                            title: "Atenção",
                            text: "O tamanho do arquivo, não poderá ser maior que 15 MB."
                        });
                    },100);
                }
                if(extension != 'pdf'){
                    setTimeout(function(){
                        $('.remove-file').trigger('click');
                        Swal.fire({
                            icon: "warning",
                            title: "Atenção",
                            text: "É permitido apenas arquivos em formato PDF."
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

                setTimeout(() => {
                    jQuery(".btn-enviar-autorizacoes").attr("disabled", false);
                }, 500);

                filePreview.appendChild(preview);
            }
        });
        

        $btnEnviarAutorizacoes = $('.btn-enviar-autorizacoes');
        $btnEnviarAutorizacoes.on('click', function (e) {
            e.preventDefault();

            $turmas = $('.tabela-turma');
            const formData = new FormData();
            const inscricao = getQueryParam('iid');
            const alunosData = [];
            let autorizacoes = jQuery('#file-input-meus-agendamentos')[0].files[0];

            if (!autorizacoes) {
                toastr["error"]("Selecione um arquivo para continuar.")
                return;
            }

            formData.append('action', 'enviar_autorizacoes');
            formData.append('autorizacoes', autorizacoes);
            formData.append('id_inscricao', inscricao);

            $turmas.each(function (index, turma) {

                alunosData[index] = {
                    turma: $(turma).data('id'),
                    alunos: []
                };

                $(turma).find('input[type="checkbox"]:checked').each(function () {
                    alunosData[index].alunos.push($(this).val());
                });
            });


            // if (!alunosData[0].alunos.length) {
            //     toastr["error"]("Selecione os alunos para continuar.")
            //     return;
            // }

            formData.append('dados_alunos',  JSON.stringify(alunosData));

            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    Swal.fire({
                        icon: "info",
                        title: "Enviando autorizações",
                        text: "As fichas de autorização estão sendo enviadas. Por favor, aguarde.",
                        showConfirmButton: false,
                    });
                },
                success: function (res) {
                    console.log(res)
                    if (res.success === true) {
                        Swal.fire({
                            icon: "success",
                            title: "Autorizações enviadas!",
                            text: "As fichas de autorização foram enviadas e serão analisadas. Em breve você receberá atualizações!",
                            showConfirmButton: true,
                            confirmButtonColor: "#005C2C",
                            confirmButtonText: "Fechar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Algo deu errado!",
                            text: res.data.message,
                            showConfirmButton: true,
                            confirmButtonColor: "#005C2C",
                            confirmButtonText: "Fechar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.reload();
                            }
                        });
                    }
                }
            });
        });
    })
  </script>

    <script>
        jQuery(function ($) {
            $btnSolicitarCancelamento = $('#btn-solicitar-cancelamento');
            $btnCancelamento = $('#btn-enviar-cancelamento');
            $btnCancelarSolicitacao = $('#cancelar-solicitacao');

            $btnSolicitarCancelamento.on('click', function () {
                $('#justificativa-cancelamento-container').focus().removeClass('d-none');
            });

            $btnCancelarSolicitacao.on('click', function () {
                $('#justificativa-cancelamento-container').addClass('d-none');
            });

            $btnCancelamento.on('click', function (e) {
                e.preventDefault();
                const inscricao = getQueryParam('iid');
                const justificativa = $('#justificativa-cancelamento').val();

                if (!justificativa.length) {
                    toastr["error"]("O campo de justificativa é obrigatório.");
                    return;
                }

                Swal.fire({
                    iconHtml: "<i class='fa fa-exclamation-triangle' aria-hidden='true'></i>",
                    title: "Deseja enviar sua solicitação de cancelamento?",
                    text: "Após avalição da equipe, será enviado um e-mail confirmando o cancelamento do passeio.",
                    showCancelButton: true,
                    showConfirmButton: true,
                    cancelButtonText: "Retornar",
                    confirmButtonColor: "#005C2C",
                    confirmButtonText: "Enviar",
                }).then((result) => {
                    if (result.isConfirmed) {

                        $btnCancelamento.prop('disabled', true);
                        $btnCancelarSolicitacao.prop('disabled', true);
                        $btnCancelamento.html('Enviando...');
                        
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            dataType: "json",
                            data: {
                                action: 'solicitar_cancelamento_inscricao',
                                id_inscricao: inscricao,
                                justificativa: justificativa
                            },
                            success: function (res) {
                                if (res.success === true) {
                                    Swal.fire({
                                        icon: "success",
                                        title: "Solicitação de cancelamento enviada!",
                                        text: "Sua solicitação de cancelamento da inscrição foi enviada com sucesso!",
                                        showConfirmButton: true,
                                        confirmButtonColor: "#005C2C",
                                        confirmButtonText: "Fechar",
                                        allowOutsideClick: false
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.reload();
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        icon: "error",
                                        title: "Algo deu errado!",
                                        text: res.data.message,
                                        showConfirmButton: true,
                                        confirmButtonColor: "#005C2C",
                                        confirmButtonText: "Fechar",
                                        allowOutsideClick: false
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.reload();
                                        }
                                    });
                                }
                            }
                        });
                    }
                });
            });
        })
    </script>

</div>


<?php get_footer(); ?>