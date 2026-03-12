<?php
$idRoteiro = $_REQUEST['rid'];   
$qtdMax = get_post_meta($_REQUEST['rid'], 'capacidade_maxima_de_participantes', true);
$qtdMin = get_post_meta($_REQUEST['rid'], 'capacidade_minima_de_participantes', true);
$nomeRoteiro = get_the_title($idRoteiro);

echo '<input type="hidden" id="nomeRoteiro" value="' . $nomeRoteiro . '">';
echo '<input type="hidden" id="idRoteiro" value="' . $idRoteiro . '">';
echo '<input type="hidden" id="idUser" value="' . get_current_user_id() . '">';
echo '<input type="hidden" id="qtdParticipantesMax" value="' . $qtdMax . '">';
echo '<input type="hidden" id="qtdParticipantesMin" value="' . $qtdMin . '">';  

$anoAtual = date('Y');

?>
<div class="container">
  <!-- SELEÇÃO DE ESTUDANTES -->
  <h5 class="section-title">Selecione os estudantes que participarão deste agendamento:</h5>
  <div class="row" id="tab-turmas">
    <div class="col text-center"><img src="<?= URL_IMG_THEME . '/gifs/carregando-info.gif'; ?>"></div>
  </div>

  <!-- EDUCADORES/ACOMPANHANTES -->
  <h5 class="section-title">Informe os educadores e acompanhantes que participarão deste agendamento:</h5>

 
  <div class="form-row">
    <div class="form-group col-md-3">
      <label class="txt-label">RF do Educador que acompanhará a turma <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="idRF1" placeholder="Insira o RF com 7 dígitos" x-model="educadores[0].rf" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');">
    </div>
    <div class="form-group col-md-5">
      <label class="txt-label">Nome do Educador <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="nomeEducador1" placeholder="Nome completo do educador" x-init="educadores[0].nome = ''" x-model="educadores[0].nome">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
      <input type="date" class="form-control" x-model="educadores[0].data_nascimento" max="<?= $anoAtual ?>-12-31">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
      <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpine" x-model="educadores[0].celular" placeholder="(11) 90000-0000" required>
    </div>
  
    <div class="form-group col-md-6">
      <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadores[0].dieta">
    </div>
    <div class="form-group col-md-6">
      <label class="txt-label">Deficiência ou Necessidade Especial</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadores[0].necessidades">
    </div>
  </div>

  <p><hr class="separacao-perguntas"></p>

  <div class="form-row">
    <div class="form-group col-md-3">
      <label class="txt-label">RF do Educador que acompanhará a turma <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="idRF2" placeholder="Insira o RF com 7 dígitos" x-model="educadores[1].rf" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');">
    </div>
    <div class="form-group col-md-5">
      <label class="txt-label">Nome do Educador <span class="text-danger">*</span></label>
      <input type="text" class="form-control" id="nomeEducador2" placeholder="Nome completo do educador" x-init="educadores[1].nome = ''" x-model="educadores[1].nome">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
      <input type="date" class="form-control" x-model="educadores[1].data_nascimento" max="<?= $anoAtual ?>-12-31">
    </div>
    <div class="form-group col-md-2">
      <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
      <input type="text" class="form-control" x-mask:dynamic="mascaraTelefoneAlpine" x-model="educadores[1].celular" placeholder="(11) 90000-0000">
    </div>
  
    <div class="form-group col-md-6">
      <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
      <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="educadores[1].dieta">
    </div>
    <div class="form-group col-md-6">
      <label class="txt-label">Deficiência ou Necessidade Especial</label>
      <input type="text" class="form-control form-celular" placeholder="Informe, se necessário" x-model="educadores[1].necessidades">
    </div>
  </div>


<template x-if="acompanhantes.length > 0">
    <!-- Repetir bloco se necessário -->
    <template x-for="(item, index) in acompanhantes" :key="index">
    
    <div class="form-row educador-item">
        <div class="form-group col-md-12"><p><hr class="separacao-perguntas"></p></div>
        <div class="form-group col-md-3">
          <label class="txt-label">RF do Educador ou CPF do Acompanhante<span class="text-danger">*</span></label>
          <input type="text" class="form-control" placeholder="Insira o RF com 7 dígitos" x-model="item.rf" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*?)\..*/g, '$1');" :id="$id('idRFCpf')" onblur="buscaAcompanhantePorRF(this)">
        </div>
        <div class="form-group col-md-5">
          <label class="txt-label">Nome completo do Educador ou Acompanhante<span class="text-danger">*</span></label>
          <input type="text" class="form-control" placeholder="Nome completo do educador" x-model="item.nome" :id="$id('nomeAcompanhante')">
        </div>
        <div class="form-group col-md-2">
          <label class="txt-label">Data de Nascimento <span class="text-danger">*</span></label>
          <input type="date" class="form-control" x-model="item.data_nascimento" max="<?= $anoAtual ?>-12-31" :id="$id('dataNascimentoAcompanhante')">
        </div>
        <div class="form-group col-md-2">
          <label class="txt-label">Celular para contato <span class="text-danger">*</span></label>
          <input type="text" class="form-control" x-mask="(99) 99999-9999" x-model="item.celular" placeholder="(11) 90000-0000" :id="$id('celAcompanhante')">
        </div>
      
        <div class="form-group col-md-6">
          <label class="txt-label">Dieta Especial ou Restrição Alimentar</label>
          <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="item.dieta" :id="$id('dietaAcompanhante')">
        </div>
        <div class="form-group col-md-6">
          <label class="txt-label">Deficiência ou Necessidade Especial</label>
          <input type="text" class="form-control" placeholder="Informe, se necessário" x-model="item.necessidades" :id="$id('necessidadesAcompanhante')">
        </div>
        <div class="form-group col-md-12">
          <label class="txt-label">Justifique a necessidade de mais um educador ou acompanhante <span class="text-danger">*</span></label>
          <textarea
            class="form-control"
            :id="$id('justificativaAcompanhante')"
            rows="3"
            placeholder="Informe a necessidade de mais um educador ou acompanhante"
            x-model="item.justificativa"
            ></textarea>
        </div>
        <button type="button" class="close btn-remove-item-duplicado" @click="removerAcompanhante(index)" data-toggle="tooltip" data-placement="right" title="Remover educador/acompanhante">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </template>
  </template>


  <div class="row">
    <div class="col-8">
      <label class="txt-green">Caso seja necessário, podem ser adicionados outros participantes a este passeio...</label>
    </div>
    <div class="col-4 text-right">
      <a type="button" id="add-educador" @click="adicionarAcompanhante()" class="add-link">
        + Adicionar Educador/Acompanhante
      </a>
    </div>
  </div>
  
  <br>
</div>
