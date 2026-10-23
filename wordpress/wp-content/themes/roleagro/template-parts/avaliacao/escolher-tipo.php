<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$link_professor = get_link_avaliacao_vivencia(
    $vivencia_id,
    'professor'
);

$link_aluno = get_link_avaliacao_vivencia(
    $vivencia_id,
    'aluno'
);
?>

<div class="container my-5">

    <div class="text-center mb-4">
        <h2>Avaliação da Vivência</h2>

        <p class="text-muted">
            Selecione quem irá responder à avaliação.
        </p>
    </div>

    <div class="row justify-content-center">

        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">

                    <h4 class="card-title">
                        Professores
                    </h4>

                    <p class="card-text">
                        Avaliação destinada aos professores
                        e educadores participantes.
                    </p>

                    <a
                        href="<?php echo esc_url( $link_professor ); ?>"
                        class="btn btn-primary"
                    >
                        Avaliação dos Professores
                    </a>

                </div>
            </div>
        </div>

        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">

                    <h4 class="card-title">
                        Alunos
                    </h4>

                    <p class="card-text">
                        Avaliação destinada aos estudantes
                        participantes da vivência.
                    </p>

                    <a
                        href="<?php echo esc_url( $link_aluno ); ?>"
                        class="btn btn-success"
                    >
                        Avaliação dos Alunos
                    </a>

                </div>
            </div>
        </div>

    </div>

</div>