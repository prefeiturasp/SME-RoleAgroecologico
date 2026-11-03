<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autorização provisória: substituir pela regra do perfil de monitor.
if ( ! is_user_logged_in() ) : ?>
    <div class="alert alert-warning" role="alert">
        A seleção de estudantes está disponível apenas para usuários autorizados.
    </div>
<?php else :
    $alunos = get_alunos_status_avaliacao( $vivencia_id );
    $turmas = [];
    $respondidos = 0;
    foreach ( $alunos as $aluno ) {
        $turma = $aluno['nomeTurma'] ?: 'Turma não informada';
        $turmas[ $turma ][] = $aluno;
        if ( ! empty( $aluno['respondeu'] ) ) {
            $respondidos++;
        }
    }
    ksort( $turmas, SORT_NATURAL | SORT_FLAG_CASE );
?>
    <div class="mb-4">
        <p>Escolha o estudante que responderá à avaliação.</p>
        <p class="text-muted">
            <?php echo esc_html( count( $alunos ) ); ?> estudantes cadastrados ·
            <?php echo esc_html( $respondidos ); ?> avaliações concluídas ·
            <?php echo esc_html( count( $alunos ) - $respondidos ); ?> pendentes
        </p>
    </div>

    <div class="form-group">
        <label for="selecionar-aluno-vivencia" class="font-weight-bold">Estudante</label>
        <select id="selecionar-aluno-vivencia" class="form-control" style="width:100%">
            <option value="">Selecione ou pesquise um estudante</option>
            <?php foreach ( $turmas as $nome_turma => $lista ) : ?>
                <optgroup label="<?php echo esc_attr( $nome_turma ); ?>">
                    <?php foreach ( $lista as $aluno ) :
                        $respondido = ! empty( $aluno['respondeu'] );
                        $rotulo = trim( $aluno['numeroAlunoChamada'] . ' - ' . $aluno['nomeAluno'] );
                        if ( $respondido ) {
                            $rotulo .= ' (já respondeu)';
                        }
                    ?>
                        <option value="<?php echo esc_attr( $aluno['codigoAluno'] ); ?>" <?php disabled( $respondido ); ?>>
                            <?php echo esc_html( $rotulo ); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Estudantes que já responderam não podem ser selecionados.</small>
    </div>
    <button type="button" class="btn btn-success" id="iniciar-avaliacao-aluno" disabled>Iniciar avaliação</button>

    <?php // A biblioteca é carregada pelo template principal. ?>
        <script>
        jQuery(function($) {
            if ($.fn.select2) {
                $('#selecionar-aluno-vivencia').select2({
                    width: '100%',
                    placeholder: 'Selecione ou pesquise um estudante',
                    language: { noResults: function() { return 'Nenhum estudante encontrado'; } }
                });
            }
            var $campo = $('#selecionar-aluno-vivencia');
            var $botao = $('#iniciar-avaliacao-aluno');
            $campo.on('change', function() { $botao.prop('disabled', !$(this).val()); });
            $botao.on('click', function() {
                var codigo = $campo.val();
                if (!codigo) return;
                var url = new URL(<?php echo wp_json_encode( get_link_avaliacao_vivencia( $vivencia_id, 'aluno' ) ); ?>, window.location.href);
                url.searchParams.set('aluno', codigo);
                window.location.href = url.toString();
            });
        });
        </script>
<?php endif; ?>
