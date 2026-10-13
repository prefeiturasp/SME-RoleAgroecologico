<?php

/*
Template Name: Avaliação de Vivência
*/

get_header();

$vivencia_id = isset( $_GET['vivencia'] )
    ? absint( $_GET['vivencia'] )
    : 0;

$vivencia = $vivencia_id
    ? get_post( $vivencia_id )
    : null;

if ( ! $vivencia ) {
    ?>

    <div class="container my-5">
        <div class="alert alert-danger">
            Vivência não encontrada.
        </div>
    </div>

    <?php

    get_footer();
    return;
}


/*
 * Verifica o status.
 */
$status = get_post_meta(
    $vivencia_id,
    'status_inscricao',
    true
);

if ( $status !== 'realizado' ) {
    ?>

    <div class="container my-5">
        <div class="alert alert-warning">
            Esta vivência ainda não está disponível para avaliação.
        </div>
    </div>

    <?php

    get_footer();
    return;
}

$participante = null;
$erro_identificacao = '';

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $documento = isset( $_POST['documento'] )
        ? sanitize_text_field( wp_unslash( $_POST['documento'] ) )
        : '';

    if ( $documento !== '' ) {
        $identificacao = identificar_participante_avaliacao( $vivencia_id, $documento );

        if ( is_wp_error( $identificacao ) ) {
            $erro_identificacao = $identificacao->get_error_message();
        } else {
            $participante = $identificacao;
        }
    }
}

$roteiro_id = get_post_meta( $vivencia_id, 'id_roteiro_inscricao', true );
$dataAgendamento = get_post_meta( $vivencia_id, 'data_reservada_para_o_roteiro', true );
if($dataAgendamento){
    $dataAgendamento = date('d/m/Y', strtotime($dataAgendamento));
}

/*
 * Processa eventual envio.
 */
$resultado = null;

if ( $participante && isset( $_POST['acao'] ) && $_POST['acao'] === 'avaliar_vivencia' ) {
    $resultado = processar_formulario_avaliacao_vivencia();
}


/*
 * Perguntas.
 */
$perguntas = get_perguntas_avaliacao_vivencia(
    'professor'
);

?>

    <div class="content-area">
        <main>

            <section class="page-header-verde container-fluid">
                <div class="container page-header__container d-flex justify-content-center">
                    <h1 class="page-header__title d-flex align-items-center">
                        <?php
                            if ( get_field( 'tipo_titulo_pagina' ) === 'composto' ):
                                $titulo_pagina = get_field( 'titulo_composto' ); ?>
                                    <span class="font-role-ve"><?php echo esc_html( $titulo_pagina['parte_1'] ); ?></span>&nbsp;<span class="font-role-vc"><?php echo esc_html( $titulo_pagina['parte_2'] ); ?></span>
                                <?php
                            else :
                                $titulo_pagina = get_field( 'titulo_simples' ) ?: get_the_title();
                                ?>
                                <h1 class="font-role-ve"><?php echo esc_html( $titulo_pagina ); ?></h1>
                                <?php
                            endif;
                            ?>
                    </h1>
                </div>
            </section>

            <section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
                <div class="row">                    
                    <div class="col-md-12 titulo d-flex flex-column flex-md-row align-items-start align-align-items-md-center justify-content-center mb-2">
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
                                $id_unidade = get_post_meta( $vivencia_id, 'codigo_eol_ue', true );
                                $nome_unidade = get_post_meta( $vivencia_id, 'nome_da_unidade_educacional', true );
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

            <div class="container avaliacao-vivencia mb-5">

                <h1>
                    Avaliação da Vivência
                </h1>

                <?php if ( is_wp_error( $resultado ) ) : ?>

                    <div class="alert alert-danger">
                        <?php echo esc_html(
                            $resultado->get_error_message()
                        ); ?>
                    </div>

                <?php elseif ( is_int( $resultado ) ) : ?>

                    <div class="alert alert-success">
                        Avaliação enviada com sucesso.
                    </div>

                <?php elseif ( ! $participante ) : ?>

                    <p>Para realizar a avaliação, informe seu RF ou CPF cadastrado na vivência.</p>

                    <?php if ( $erro_identificacao ) : ?>
                        <div class="alert alert-danger">
                            <?php echo esc_html( $erro_identificacao ); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <input type="hidden" name="acao" value="identificar_participante">

                        <div class="form-group">
                            <label for="documento">RF ou CPF</label>
                            <input type="text" class="form-control" id="documento" name="documento" required>
                        </div>

                        <button type="submit" class="btn btn-success shadow ml-0">
                            Continuar
                        </button>
                    </form>

                <?php else : ?>

                    <form method="post">

                        <?php
                        wp_nonce_field(
                            'avaliar_vivencia'
                        );
                        ?>

                        <input
                            type="hidden"
                            name="acao"
                            value="avaliar_vivencia"
                        >

                        <input
                            type="hidden"
                            name="vivencia_id"
                            value="<?php echo esc_attr(
                                $vivencia_id
                            ); ?>"
                        >

                        <input type="hidden" name="documento" value="<?php echo esc_attr( $participante['rf'] ); ?>">


                        <div class="card mb-4 border-0 bg-light">
                            <div class="card-body">
                                <small class="text-muted d-block mb-2">
                                    <i class="fa fa-user mr-1"></i> PARTICIPANTE IDENTIFICADO
                                </small>

                                <h5 class="mb-1">
                                    <?php echo esc_html( $participante['nome'] ); ?>
                                </h5>

                                <p class="text-muted mb-0">
                                    Você está respondendo à avaliação desta vivência.
                                </p>
                            </div>
                        </div>


                        <div class="mb-4">
                            <h4>Avalie sua experiência</h4>
                            <p class="text-muted">
                                Para cada pergunta, selecione uma nota de 0 a 5.
                            </p>
                        </div>

                        <?php $numero = 1; ?>
                        <?php foreach ( $perguntas as $chave => $configuracao ) : ?>

                            <div class="card pergunta-avaliacao mb-3">
                                <div class="card-body">

                                    <p class="font-weight-bold mb-3">
                                        <span class="numero-pergunta">
                                            <?php echo esc_html( sprintf( '%02d', $numero ) ); ?>
                                        </span>

                                        <?php echo esc_html( $configuracao['pergunta'] ); ?>
                                    </p>

                                    <div class="opcoes-avaliacao">
                                        <?php for ( $nota = 0; $nota <= 5; $nota++ ) : ?>

                                            <div class="form-check form-check-inline">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    name="respostas[<?php echo esc_attr( $chave ); ?>]"
                                                    id="<?php echo esc_attr( $chave . '_' . $nota ); ?>"
                                                    value="<?php echo esc_attr( $nota ); ?>"
                                                    required
                                                >

                                                <label
                                                    class="form-check-label"
                                                    for="<?php echo esc_attr( $chave . '_' . $nota ); ?>"
                                                >
                                                    <?php echo esc_html( $nota ); ?>
                                                </label>
                                            </div>

                                        <?php endfor; ?>
                                    </div>

                                </div>
                            </div>

                            <?php $numero++; ?>
                        <?php endforeach; ?>


                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="comentario" class="font-weight-bold">
                                    Comentários adicionais
                                </label>

                                <p class="text-muted small">
                                    Compartilhe sugestões, elogios ou observações sobre a vivência.
                                    Este campo é opcional.
                                </p>

                                <textarea
                                    class="form-control"
                                    id="comentario"
                                    name="comentario"
                                    rows="4"
                                    placeholder="Escreva seu comentário aqui..."
                                ></textarea>
                            </div>
                        </div>


                        <button
                            type="submit"
                            class="btn btn-success shadow ml-0"
                        >
                            Enviar avaliação
                        </button>

                    </form>

                <?php endif; ?>

            </div>

        </main>
    </div>

<?php

get_footer();