<?php
extract( $args );

use App\Controllers\AgendamentoController;
use App\Controllers\RoteiroController;

$agendamento = new AgendamentoController( $roteiro_id );
$inscricao = AgendamentoController::get_inscricao($inscricao_id);
$roteiro = new RoteiroController( $roteiro_id );

$turmasEdit = $inscricao['turmas'];
$TurmasEAlunosAPI = $agendamento->get_alunos($turmasEdit);

$educadores = $inscricao['educadores'];
$acompanhantes = $inscricao['acompanhantes'];
$acompanhantes = $acompanhantes ? $acompanhantes : [];

$turmas_cacheadas = get_transient( 'alunos-turmas-selecionadas-'.$inscricao_id );

$turmasMescladas = [];
if(!$turmas_cacheadas){
  delete_transient('alunos-turmas-selecionadas-'.$inscricao_id);
  $turmasMescladas = $TurmasEAlunosAPI;
  set_transient( 'alunos-turmas-selecionadas-'.$inscricao_id , $TurmasEAlunosAPI, 3600 );
} else {
  $turmasMescladas = $turmas_cacheadas;
}

$qtdMax = get_post_meta($roteiro_id, 'capacidade_maxima_de_participantes', true);
$qtdMin = get_post_meta($roteiro_id, 'capacidade_minima_de_participantes', true);
$nomeRoteiro = get_the_title($roteiro_id);

$idsTurma = '';
$idsAlunosTurma = [];
$contador = 0;
foreach($turmasEdit as $turma){
  $idsTurma .= $turma['nomeTurma'].' - '.$turma['idTurma'].',';

  $strIdsAlunosTurma = '';
  foreach($turma['alunosTurma'] as $aluno){
    $strIdsAlunosTurma .= $aluno['codigoAluno'].',';
  }
  $idsAlunosTurma[$contador] = substr($strIdsAlunosTurma, 0, -1);
  $contador++;
}
for($i=0; $i < count($idsAlunosTurma); $i++) { 
  echo '<input type="hidden" id="alunos-selecionados-turma-'.($i+1).'-edit" value="' . $idsAlunosTurma[$i] . '">'; 
}

$dataAgendamento = $inscricao['data_agendamento'];

$idsTurma = substr($idsTurma, 0, -1);
echo '<input type="hidden" id="qtdTurmas" value="' .  count($turmasEdit) . '">'; 
echo '<input type="hidden" id="idsTurmasSelecionadasEdit" value="' .  $idsTurma . '">';  
echo '<input type="hidden" id="nomeRoteiro" value="' . $nomeRoteiro . '">';
echo '<input type="hidden" id="idRoteiro" value="' . $roteiro_id . '">';
echo '<input type="hidden" id="idUserEdit" value="' . get_current_user_id() . '">';
echo '<input type="hidden" id="qtdParticipantesMax" value="' . $qtdMax . '">';
echo '<input type="hidden" id="qtdParticipantesMin" value="' . $qtdMin . '">';  
echo '<input type="hidden" id="idPost" value="' . $inscricao_id . '">';
echo '<input type="hidden" id="dataAgendamento" value="' . $dataAgendamento . '">';

$anoAtual = date('Y');

?>
<div class="container" x-data="carregaAcompanhantesEdit();carregaEducadoresEdit();">

  <!-- SELEÇÃO DE ESTUDANTES -->
  <h5 class="section-title">Selecione os estudantes que participarão deste agendamento:</h5>
  <div class="row" id="tab-turmas">
    <?php $i=0; foreach($turmasMescladas as $turma): ?>
      <div class="col-md-6">
        <h6>Estudantes da Turma: <span class="text-dark"><?= $turmasEdit[$i]['nomeTurma'] ?></span></h6>
        <div class="box-border">
          <div class="checkbox-label">
            <label><input type="checkbox" name="ckb-turma-<?= $i+1 ?>" id="turma<?= $i+1 ?>" value="turma<?= $i+1 ?>"> <strong class="txt-label-green">Cód. EOL e Nome do Estudante</strong></label>
          </div>
          <?php  $c=0; foreach($turma['alunosTurma'] as $aluno): ?>
            <div class="checkbox-label">
                <label class="txt-green">
                  <input name="ckb-alunos-t<?= $i+1 ?>[]" id="at<?= $i+1 ?>-<?= $aluno['codigoAluno'] ?>" type="checkbox" value="<?= $aluno['codigoAluno'] ?>" > 
                  <?= $aluno['codigoAluno'] ?> - <?= $aluno['nomeAluno'] ?> 
                </label>
            </div>
          <?php $c++; endforeach; ?>          
        </div>
      </div>
      <?php $i++; endforeach; ?>
  </div>

  <!-- EDUCADORES/ACOMPANHANTES -->
  <h5 class="section-title">Informe os educadores e acompanhantes que participarão deste agendamento:</h5>

  <div class="form-row" x-init="educadoresEdit[0].rf = '<?= $educadores[0]['rf'] ?>'; educadoresEdit[0].nome = '<?= $educadores[0]['nome'] ?>'; educadoresEdit[0].data_nascimento = '<?= $educadores[0]['data_nascimento'] ?>'; educadoresEdit[0].celular = '<?= $educadores[0]['celular'] ?>'; educadoresEdit[0].dieta = '<?= $educadores[0]['dieta'] ?>'; educadoresEdit[0].necessidades = '<?= $educadores[0]['necessidades'] ?>'">
    <div class="form-group col-md-3">
      <label class="txt-label">RF do Educador que acompanhará a turma <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="idRFEdit1" placeholder="Insira o RF com 7 dígitos" x-model="educadoresEdit[0].rf"  oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');">
    </div>
    <div class="form-group col-md-5">
      <label class="txt-label">Nome do Educador <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="nomeEducadorEdit1" placeholder="Nome completo do educador" x-model="educadoresEdit[0].nome" readonly>
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
      <input type="date" class="form-control" x-model="educadoresEdit[0].data_nascimento" max="<?= $anoAtual ?>-12-31">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
      <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpineEdit" x-model="educadoresEdit[0].celular" placeholder="(11) 90000-0000" required>
    </div>
  
    <div class="form-group col-md-6">
      <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadoresEdit[0].dieta">
    </div>
    <div class="form-group col-md-6">
      <label class="txt-label">Deficiência ou Necessidade Especial</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadoresEdit[0].necessidades">
    </div>
  </div>

  <p><hr class="separacao-perguntas"></p>

  <div class="form-row" x-init="educadoresEdit[1].rf = '<?= $educadores[1]['rf'] ?>'; educadoresEdit[1].nome = '<?= $educadores[1]['nome'] ?>'; educadoresEdit[1].data_nascimento = '<?= $educadores[1]['data_nascimento'] ?>'; educadoresEdit[1].celular = '<?= $educadores[1]['celular'] ?>'; educadoresEdit[1].dieta = '<?= $educadores[1]['dieta'] ?>'; educadoresEdit[1].necessidades = '<?= $educadores[1]['necessidades'] ?>'">
    <div class="form-group col-md-3">
      <label class="txt-label">RF do Educador que acompanhará a turma <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="idRFEdit2" placeholder="Insira o RF com 7 dígitos" x-model="educadoresEdit[1].rf" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');">
    </div>
    <div class="form-group col-md-5">
      <label class="txt-label">Nome do Educador <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="nomeEducadorEdit2" placeholder="Nome completo do educador" x-model="educadoresEdit[1].nome" readonly>
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
      <input type="date" class="form-control" x-model="educadoresEdit[1].data_nascimento" max="<?= $anoAtual ?>-12-31">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
      <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpineEdit" x-model="educadoresEdit[1].celular" placeholder="(11) 90000-0000" required>
    </div>
  
    <div class="form-group col-md-6">
      <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadoresEdit[1].dieta">
    </div>
    <div class="form-group col-md-6">
      <label class="txt-label">Deficiência ou Necessidade Especial</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadoresEdit[1].necessidades">
    </div>
  </div>

      
      
    <!-- Repetir bloco se necessário -->
    <template x-for="(item, index) in acompanhantesEdit" :key="index" >

    <div class="form-row educador-item">
        <div class="form-group col-md-12"><p><hr class="separacao-perguntas"></p></div>
        <div class="form-group col-md-3">
          <label class="txt-label">RF do Educador ou CPF do Acompanhante<span class="text-danger">*</span></label>
          <input type="text" class="form-control" placeholder="Insira o RF com 7 dígitos" x-model="item.rf" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');" :id="$id('idRFCpfEdit')" onblur="buscaAcompanhantePorRFEdit(this)">
        </div>
        <div class="form-group col-md-5">
          <label class="txt-label">Nome completo do Educador ou Acompanhante<span class="text-danger">*</span></label>
          <input type="text" class="form-control" placeholder="Nome completo do educador" x-model="item.nome" :id="$id('nomeAcompanhanteEdit')">
        </div>
        <div class="form-group col-md-2">
          <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
          <input type="date" class="form-control" x-model="item.data_nascimento" max="<?= $anoAtual ?>-12-31" :id="$id('dataNascimentoAcompanhanteEdit')">
        </div>
        <div class="form-group col-md-2">
          <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
          <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpineEdit" x-model="item.celular" placeholder="(11) 90000-0000" :id="$id('celAcompanhanteEdit')">
        </div>
      
        <div class="form-group col-md-6">
          <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
          <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="item.dieta" :id="$id('dietaAcompanhanteEdit')">
        </div>
        <div class="form-group col-md-6">
          <label class="txt-label">Deficiência ou Necessidade Especial</label>
          <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="item.necessidades" :id="$id('necessidadesAcompanhanteEdit')">
        </div>
        <div class="form-group col-md-12">
          <label class="txt-label">Justifique a necessidade de mais um educador ou acompanhante <span class="text-danger">*</span></label>
          <textarea
            class="form-control"
            :id="$id('justificativaAcompanhanteEdit')"
            rows="3"
            placeholder="Informe a necessidade de mais um educador ou acompanhante"
            x-model="item.justificativa"
            ></textarea>
        </div>
        <button type="button" class="close btn-remove-item-duplicado" @click="removerAcompanhanteEdit(index)" data-toggle="tooltip" data-placement="right" title="Remover educador/acompanhante">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

    </template>


  <div class="row">
    <div class="col-8">&nbsp;</div>
    <div class="col-4 text-right">
      <a type="button" id="add-educador" @click="adicionarAcompanhanteEdit()" class="add-link">
        + Adicionar Educador/Acompanhante
      </a>
    </div>
  </div>

  <div class="row">
    <div class="col text-center">
      <!-- <span>Total de participantes neste agendamento</span> -->
       <ul class="list-group">
        <li class="list-group-item list-group-item-success">Total de participantes nesse agendamento: <b><span id="qtdTotalParticipantesEdit"></span></b></b></li>
       </ul>
    </div>
  </div>
  
  <br>
</div>