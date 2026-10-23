<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() || ! $aluno_selecionado ) { return; }
$perguntas_aluno = get_perguntas_avaliacao_aluno_vivencia();
$respostas_anteriores = isset( $_POST['respostas'] ) && is_array( $_POST['respostas'] ) ? wp_unslash( $_POST['respostas'] ) : [];
?>
<?php if ( is_wp_error( $resultado ) ) : ?>
    <div class="alert alert-danger"><?php echo esc_html( $resultado->get_error_message() ); ?></div>
<?php endif; ?>
<div class="card bg-light border-0 mb-4"><div class="card-body">
    <small class="text-muted">ESTUDANTE SELECIONADO</small>
    <h4 class="mb-1"><?php echo esc_html( $aluno_selecionado['nomeAluno'] ); ?></h4>
    <p class="mb-0 text-muted"><?php echo esc_html( $aluno_selecionado['nomeTurma'] ); ?></p>
</div></div>
<p>Marque apenas uma opção em cada pergunta. Nas perguntas com notas, 1 é a menor nota e 5 é a maior.</p>
<form method="post">
    <?php wp_nonce_field( 'avaliar_aluno_vivencia_' . $vivencia_id . '_' . $codigo_aluno_selecionado ); ?>
    <input type="hidden" name="acao" value="avaliar_aluno_vivencia">
    <?php foreach ( $perguntas_aluno as $indice => $config ) :
        $valor_atual = isset( $respostas_anteriores[ $indice ] ) && is_scalar( $respostas_anteriores[ $indice ] ) ? (string) $respostas_anteriores[ $indice ] : '';
        $obrigatoria = $config['obrigatoria'] ?? true;
        $opcoes = $config['tipo'] === 'nota'
            ? [ '1'=>'😫 1', '2'=>'😞 2', '3'=>'😐 3', '4'=>'🙂 4', '5'=>'😃 5' ]
            : ( $config['opcoes'] ?? [] );
    ?>
        <fieldset class="card mb-3 pergunta-avaliacao"><div class="card-body">
            <legend class="h6 font-weight-bold"><?php echo esc_html( $config['pergunta'] ); ?><?php if ( ! $obrigatoria ) : ?> <small class="text-muted">(opcional)</small><?php endif; ?></legend>
            <?php if ( $config['tipo'] === 'texto' ) : ?>
                <textarea class="form-control" name="respostas[<?php echo esc_attr( $indice ); ?>]" rows="3" maxlength="1000" <?php echo $obrigatoria ? 'required' : ''; ?>><?php echo esc_textarea( $valor_atual ); ?></textarea>
            <?php else : ?>
                <?php foreach ( $opcoes as $chave => $rotulo ) :
                    $id_campo = 'aluno_' . $indice . '_' . $chave;
                ?>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="respostas[<?php echo esc_attr( $indice ); ?>]" id="<?php echo esc_attr( $id_campo ); ?>" value="<?php echo esc_attr( $chave ); ?>" <?php checked( $valor_atual, (string) $chave ); ?> <?php echo $obrigatoria ? 'required' : ''; ?>>
                        <label class="form-check-label" for="<?php echo esc_attr( $id_campo ); ?>"><?php echo esc_html( $rotulo ); ?></label>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></fieldset>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-success">Enviar avaliação</button>
    <a class="btn btn-outline-secondary" href="<?php echo esc_url( get_link_avaliacao_vivencia( $vivencia_id, 'aluno' ) ); ?>">Voltar à lista de alunos</a>
</form>
