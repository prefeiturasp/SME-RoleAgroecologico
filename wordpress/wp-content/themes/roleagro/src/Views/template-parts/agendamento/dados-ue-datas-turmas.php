<?php

extract($args);
wp_enqueue_script( 'moment-tz' );

$unidade_educacional = get_user_meta( get_current_user_id(), 'unidade_locacao', true );
$unidade_educacional_info = get_user_meta( get_current_user_id(), 'dados_ue', true );

use App\Controllers\AgendamentoController;

$idRoteiro = $_REQUEST['rid']; 
$agendamento = new AgendamentoController($idRoteiro);
$arrTurmas = $agendamento->get_turmas();
if(!$arrTurmas){
    $arrTurmas = [];
}

?>
<div class="container">
    <div class="row">
        <div class="col">
            <div class="form-group">
                <label class="txt-label" for="formGroupExampleInput">Nome da Unidade Educacional<span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Nome da Unidade"
                    value="<?php echo esc_html( $unidade_educacional['nomeUnidade'] ); ?>"
                    x-init="dados.nomeUe = '<?php echo esc_html( $unidade_educacional['nomeUnidade'] ); ?>'"
                    disabled
                    >
            </div>
        </div>
    </div>
  
    <div class="row">
        <div class="col">
            <div class="form-group">
                <label class="txt-label" for="formGroupExampleInput">DRE<span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    placeholder="DRE"
                    value="<?php echo esc_html( $unidade_educacional_info->nomeDRE ?? '' ); ?>"
                    x-init="dados.dre = '<?php echo esc_html( $unidade_educacional_info->nomeDRE ?? '' ); ?>'"
                    disabled
                    >
            </div>
        </div>
        <div class="col">
            <div class="form-group">
                <label class="txt-label" for="formGroupExampleInput">Telefone da UE para Contato <span class="text-danger">*</span></label>
                <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpine" x-model="dados.telefoneUe" maxlength="15" placeholder="(11) 9999-9999">
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <div class="form-group">
                <label class="txt-label" for="formGroupExampleInput">Nome do Responsável da UE pelo Agendamento<span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    placeholder="Nome do Responsável da EU pelo Agendameno"
                    value="<?php echo esc_html( ( wp_get_current_user() )->display_name ); ?>"
                    x-init="dados.nomeResponsavel = '<?php echo esc_html( ( wp_get_current_user() )->display_name ); ?>'"
                    x-model="dados.nomeResponsavel"
                    readonly>
            </div>
        </div>
        <div class="col">
            <div class="form-group">
                <label class="txt-label" for="formGroupExampleInput">E-mail da UE para Contato<span class="text-danger">*</span></label>
                <input
                    type="text"
                    class="form-control"
                    placeholder="E-mail da UE para Contanto"
                    value="<?php echo esc_html( $unidade_educacional_info->email ?? '' ); ?>"
                    x-init="dados.emailUe = '<?php echo esc_html( $unidade_educacional_info->email ?? '' ); ?>'"
                    x-model="dados.emailUe"
                    >
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <?php 
                $date = new DateTime();
                $date->modify('+14 day');
                $arrDatasPermitidas = [];
                $dataMinimaVivencia = $date->format('Y-m-d');
                foreach($datas_ofertadas as $dataVivencia){
                    if(strtotime($dataVivencia) >= strtotime($dataMinimaVivencia)){
                        array_push($arrDatasPermitidas, $dataVivencia);
                    }
                } 
            ?>
            <div class="form-group">
                <input type="hidden" id="dataMinPermitida" value="<?= $arrDatasPermitidas[0]; ?>">
                <label class="txt-label-green" for="formGroupExampleInput">Datas disponíveis para agendamento</label>
                <select class="form-control" id="select-datas-disponiveis" x-model="dados.dataAgendamento" x-on:change="alteraDataSelect">
                    <option>Selecione uma data disponível</option>
                    <?php
                    $timezone = new DateTimeZone('America/Sao_Paulo');
                    $formatter = new IntlDateFormatter(
                        'pt_BR',
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::NONE,
                        'America/Sao_Paulo',
                        IntlDateFormatter::GREGORIAN,
                        "EEEE, dd/MM/yyyy"
                    );

                    foreach ( $arrDatasPermitidas as $data ) :
                        if($data > date('Y-m-d')){
                            $data_formatada = new DateTime( $data, $timezone );
                            $data_formatada = $formatter->format($data_formatada);
                        ?>
                        <option value="<?php echo esc_html( $data ); ?>"><?php echo esc_html( $data_formatada ); ?></option>
                    <?php } endforeach; ?>
                </select> 
            </div>

            <!-- O Calendário será aqui -->
            <div class="calendario" id="div_calendario">
                <?php get_template_part( 'src/Views/template-parts/calendario' ); ?>
            </div>
            
        </div>
        <div class="col">
            <div class="form-grouptxt-label">
                <label class="txt-label-green" for="formGroupExampleInput">Turmas de 6° ano disponíveis</label>
               
                <div class="row padding">
                        <?php foreach($arrTurmas as $turma): ?>
                            <div class="col-12 col-sm-12 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="selecao-alunos[]" value="<?= $turma['nome_turma'] .' - '.$turma['nome_ano'] .' - '. $turma['codigo_turma'] ?>" id="turma-<?=$turma['codigo_turma']?>">
                                    <label class="form-check-label" for="turma-<?=$turma['codigo_turma']?>">
                                        <?php echo $turma['modalidade'] .' - <b>'.$turma['nome_turma_eol'].'</b> - '.$turma['nome_ano']; ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>

  </div>