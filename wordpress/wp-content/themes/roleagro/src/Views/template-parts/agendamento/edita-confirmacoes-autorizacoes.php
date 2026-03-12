<?php
    extract( $args );

    $unidade_educacional = get_user_meta( get_current_user_id(), 'unidade_locacao', true );
?>
<div class="container my-5" id="confirmacoes-autorizacoes">

  <!-- Informações principais -->
  <div class="row">
    <div class="col-md-8">
      <h5>Informações do agendamento</h5>
      <p>
        <strong>Data da vivência pedagógica:</strong>
        <span id="data-agendamento"><span id="dataAgendamento"></span> - <?php echo !get_field( 'roteiro_de_tempo', $roteiro_id ) ? 'Período Integral' : 'Período Parcial'; ?></span>
    </p>
      <p><strong>Local:</strong> <?php echo esc_html( get_the_title( $roteiro_id ) ); ?></p>
      <p><strong>Unidade:</strong> <?php echo esc_html( $unidade_educacional['nomeUnidade'] ); ?></p>
      <p><strong>Quantidade de inscritos:</strong> <span id="qtdEstudantes"></span> estudantes, <span id="qtdProfessores"></span> professores, <span id="qtdOutros"></span> outros = <span id="qtdParticipantes"></span> participantes</p>
      <p><strong>Alunos com acessibilidade:</strong> <span id="qtdAlunosComAcessibilidade"></span></p>
      <p><strong>Alunos com dieta:</strong> <span id="qtdAlunosComDieta"></span></p>
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
    </div>

    <div class="col-md-4">
        <?php if ( $atrativos ) : ?>
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
    </div>
  </div>

  <div class="row mt-4" id="exibe-turma-aluno-conf"></div>
  
  <!-- Educadores -->
  <div class="row mt-3" id="exibe-educadores-acompanhantes-conf"></div>

    <?php if ( $informacoes_adicionais = get_field( 'informacoes_adicionais_agendamento', 'options' ) ) : ?>
        <div class="my-5" id="informacoes-adicionais">
            <?php echo wp_kses_post( $informacoes_adicionais ); ?>
        </div>
    <?php endif; ?>
</div>
