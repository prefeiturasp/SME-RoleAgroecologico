<?php
/*
Template Name: Avaliação de Vivência
*/

// Select2 está registrado no tema, mas precisa ser enfileirado antes do header.
$av_tipo_pre = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : '';
if ( $av_tipo_pre === 'aluno' ) {
    nocache_headers();
    wp_enqueue_script( 'select2' );
    wp_enqueue_style( 'select2' ); // Ajustar o handle se o CSS tiver outro nome.
}
get_header();


$vivencia_id = isset( $_GET['vivencia'] ) ? absint( $_GET['vivencia'] ) : 0;
$vivencia = $vivencia_id ? get_post( $vivencia_id ) : null;

if ( ! $vivencia ) {
    echo '<div class="container my-5"><div class="alert alert-danger">Vivência não encontrada.</div></div>';
    get_footer();
    return;
}

if ( get_post_meta( $vivencia_id, 'status_inscricao', true ) !== 'realizado' ) {
    echo '<div class="container my-5"><div class="alert alert-warning">Esta vivência ainda não está disponível para avaliação.</div></div>';
    get_footer();
    return;
}

$tipo = isset( $_GET['tipo'] ) ? sanitize_key( wp_unslash( $_GET['tipo'] ) ) : '';
$tipo_valido = in_array( $tipo, [ '', 'professor', 'aluno', 'unidade' ], true );
$unidade_id = isset( $_GET['unidade'] ) ? absint( $_GET['unidade'] ) : 0;
$token = isset( $_GET['token'] ) && is_string( $_GET['token'] )
    ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';

$participante = null;
$erro_identificacao = '';
$convite_valido = false;
$avaliacao_realizada = false;
$resultado = null;
$codigo_aluno_selecionado = ( $tipo === 'aluno' && isset( $_GET['aluno'] ) ) ? absint( $_GET['aluno'] ) : 0;
$aluno_selecionado = null;
$erro_aluno = '';
if ( $tipo === 'aluno' && is_user_logged_in() && $codigo_aluno_selecionado ) {
    $aluno_selecionado = get_aluno_vivencia( $vivencia_id, $codigo_aluno_selecionado );
    if ( ! $aluno_selecionado ) {
        $erro_aluno = 'Estudante não encontrado nesta vivência.';
    } elseif ( avaliacao_aluno_ja_realizada( $vivencia_id, $codigo_aluno_selecionado ) ) {
        $erro_aluno = 'Este estudante já respondeu à avaliação.';
        $aluno_selecionado = null;
    }
}


if ( $tipo_valido && $tipo === 'unidade' ) {
    $convite_valido = $unidade_id
        && unidade_pertence_vivencia( $vivencia_id, $unidade_id )
        && validar_convite_avaliacao_unidade( $vivencia_id, $unidade_id, $token );

    if ( $convite_valido ) {
        $avaliacao_realizada = avaliacao_unidade_ja_realizada( $vivencia_id, $unidade_id );
    }
}

$acao = isset( $_POST['acao'] ) && is_string( $_POST['acao'] )
    ? sanitize_key( wp_unslash( $_POST['acao'] ) ) : '';

if ( $tipo_valido && $tipo === 'professor' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $documento = isset( $_POST['documento'] ) && is_string( $_POST['documento'] )
        ? sanitize_text_field( wp_unslash( $_POST['documento'] ) ) : '';

    if ( $documento !== '' ) {
        $identificacao = identificar_participante_avaliacao( $vivencia_id, $documento );
        if ( is_wp_error( $identificacao ) ) {
            $erro_identificacao = $identificacao->get_error_message();
        } else {
            $participante = $identificacao;
        }
    }
}

// Fluxo dos alunos separado para não interferir no processamento já existente.
if ( $tipo === 'aluno' && $acao === 'avaliar_aluno_vivencia' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    if ( ! is_user_logged_in() ) {
        $resultado = new WP_Error( 'acesso_negado', 'É necessário entrar no sistema.' );
    } elseif ( ! $aluno_selecionado ) {
        $resultado = new WP_Error( 'aluno_invalido', 'Selecione um estudante válido.' );
    } else {
        $resultado = processar_avaliacao_aluno_vivencia( $vivencia_id, $codigo_aluno_selecionado );
    }
}

if ( $tipo_valido && $acao === 'avaliar_vivencia' ) {
    if ( $tipo === 'professor' && $participante ) {
        $resultado = processar_formulario_avaliacao_vivencia();
    } elseif ( $tipo === 'unidade' && $convite_valido && ! $avaliacao_realizada ) {
        $resultado = processar_formulario_avaliacao_vivencia();
        if ( is_int( $resultado ) ) {
            $avaliacao_realizada = true;
        }
    }
}

$roteiro_id = absint( get_post_meta( $vivencia_id, 'id_roteiro_inscricao', true ) );
$data_agendamento = get_post_meta( $vivencia_id, 'data_reservada_para_o_roteiro', true );
$data_formatada = $data_agendamento ? date_i18n( 'd/m/Y', strtotime( $data_agendamento ) ) : '';
$id_unidade_educacional = get_post_meta( $vivencia_id, 'codigo_eol_ue', true );
$nome_unidade_educacional = get_post_meta( $vivencia_id, 'nome_da_unidade_educacional', true );
$perguntas = in_array( $tipo, [ 'professor', 'unidade' ], true )
    ? get_perguntas_avaliacao_vivencia( $tipo )
    : [];
$mostrar_formulario = $tipo_valido
    && ! is_int( $resultado )
    && ( ( $tipo === 'professor' && $participante )
        || ( $tipo === 'unidade' && $convite_valido && ! $avaliacao_realizada ) );
?>

<div class="content-area">
    <main>
        <section class="page-header-verde container-fluid">
            <div class="container page-header__container d-flex justify-content-center">
                <div class="page-header__title d-flex align-items-center">
                    <?php if ( get_field( 'tipo_titulo_pagina' ) === 'composto' ) :
                        $titulo_pagina = get_field( 'titulo_composto' ); ?>
                        <span class="font-role-ve"><?php echo esc_html( $titulo_pagina['parte_1'] ?? '' ); ?></span>&nbsp;<span class="font-role-vc"><?php echo esc_html( $titulo_pagina['parte_2'] ?? '' ); ?></span>
                    <?php else :
                        $titulo_pagina = get_field( 'titulo_simples' ) ?: get_the_title(); ?>
                        <h1 class="font-role-ve"><?php echo esc_html( $titulo_pagina ); ?></h1>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
            <div class="row">
                <div class="col-md-12 titulo d-flex flex-column flex-md-row align-items-start justify-content-center mb-2">
                    <h1><span>Rolê:</span> <?php echo esc_html( get_the_title( $roteiro_id ) ); ?></h1>
                </div>
            </div>
        </section>

        <section>
            <div class="container my-5" id="info-lista-presenca">
                <div class="row">
                    <div class="col-md">
                        <p>
                            <strong>Data da vivência pedagógica:</strong>
                            <span class="destaque-data-vivencia">
                                <?php echo esc_html( $data_formatada ); ?> -
                                <?php echo esc_html( ! get_field( 'roteiro_de_tempo', $roteiro_id ) ? 'Período Integral' : 'Período Parcial' ); ?>
                            </span>
                        </p>
                        <p><strong>Local:</strong> <?php echo esc_html( get_the_title( $roteiro_id ) ); ?></p>
                        <p><strong>Unidade:</strong> <?php echo esc_html( $id_unidade_educacional . ' - ' . $nome_unidade_educacional ); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <div class="container avaliacao-vivencia mb-5">
            <?php if ( in_array( $tipo, [ 'professor', 'aluno' ], true ) ) : ?>
                <div class="mb-4">
                    <a href="<?php echo esc_url( get_link_avaliacao_vivencia( $vivencia_id ) ); ?>" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left mr-2" aria-hidden="true"></i>
                        Voltar para escolha do perfil
                    </a>
                </div>
            <?php endif; ?>
            <h1><?php echo esc_html( $tipo === 'unidade' ? 'Avaliação da Unidade Produtiva' : ( $tipo === 'aluno' ? 'Avaliação dos Alunos' : 'Avaliação da Vivência' ) ); ?></h1>

            <?php if ( ! $tipo_valido ) : ?>
                <div class="alert alert-danger">Tipo de avaliação inválido.</div>

            <?php elseif ( $tipo === '' ) : ?>
                <?php include __DIR__ . '/template-parts/avaliacao/escolher-tipo.php'; ?>

            <?php elseif ( $tipo === 'aluno' ) : ?>
                <?php if ( ! is_user_logged_in() ) : ?>
                    <div class="alert alert-warning">Para avaliar estudantes, entre com uma conta do sistema.</div>
                    <a class="btn btn-success" href="<?php echo esc_url( wp_login_url( get_link_avaliacao_vivencia( $vivencia_id, 'aluno' ) ) ); ?>">Entrar</a>
                <?php elseif ( is_int( $resultado ) ) : ?>
                    <div class="alert alert-success">Avaliação enviada com sucesso. Entregue o dispositivo ao monitor.</div>
                    <a class="btn btn-success" href="<?php echo esc_url( get_link_avaliacao_vivencia( $vivencia_id, 'aluno' ) ); ?>">Selecionar outro estudante</a>
                <?php elseif ( $codigo_aluno_selecionado && $aluno_selecionado ) : ?>
                    <?php include __DIR__ . '/template-parts/avaliacao/formulario-aluno.php'; ?>
                <?php else : ?>
                    <?php if ( $erro_aluno ) : ?><div class="alert alert-warning"><?php echo esc_html( $erro_aluno ); ?></div><?php endif; ?>
                    <?php include __DIR__ . '/template-parts/avaliacao/selecionar-aluno.php'; ?>
                <?php endif; ?>

            <?php elseif ( $tipo === 'unidade' && ! $convite_valido ) : ?>
                <div class="alert alert-danger">O link de avaliação é inválido ou não está autorizado.</div>

            <?php elseif ( is_int( $resultado ) ) : ?>
                <div class="alert alert-success">Avaliação enviada com sucesso.</div>

            <?php elseif ( $tipo === 'unidade' && $avaliacao_realizada ) : ?>
                <div class="alert alert-info">Esta Unidade Produtiva já realizou a avaliação desta vivência.</div>

            <?php else : ?>
                <?php if ( is_wp_error( $resultado ) ) : ?>
                    <div class="alert alert-danger"><?php echo esc_html( $resultado->get_error_message() ); ?></div>
                <?php endif; ?>

                <?php if ( $tipo === 'professor' && ! $participante ) : ?>
                    <p>Para realizar a avaliação, informe seu RF ou CPF cadastrado na vivência.</p>
                    <?php if ( $erro_identificacao ) : ?>
                        <div class="alert alert-danger"><?php echo esc_html( $erro_identificacao ); ?></div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="acao" value="identificar_participante">
                        <div class="form-group">
                            <label for="documento">RF ou CPF</label>
                            <input type="text" class="form-control" id="documento" name="documento" required>
                        </div>
                        <button type="submit" class="btn btn-success shadow ml-0">Continuar</button>
                    </form>
                <?php elseif ( $mostrar_formulario ) : ?>
                    <form method="post">
                        <?php wp_nonce_field( 'avaliar_vivencia' ); ?>
                        <input type="hidden" name="acao" value="avaliar_vivencia">
                        <input type="hidden" name="vivencia_id" value="<?php echo esc_attr( $vivencia_id ); ?>">
                        <input type="hidden" name="tipo" value="<?php echo esc_attr( $tipo ); ?>">

                        <?php if ( $tipo === 'professor' ) : ?>
                            <input type="hidden" name="documento" value="<?php echo esc_attr( $participante['rf'] ); ?>">
                        <?php else : ?>
                            <input type="hidden" name="unidade_id" value="<?php echo esc_attr( $unidade_id ); ?>">
                            <input type="hidden" name="token_avaliacao" value="<?php echo esc_attr( $token ); ?>">
                        <?php endif; ?>

                        <div class="card mb-4 border-0 bg-light">
                            <div class="card-body">
                                <small class="text-muted d-block mb-2">
                                    <i class="fa <?php echo $tipo === 'unidade' ? 'fa-map-marker' : 'fa-user'; ?> mr-1"></i>
                                    <?php echo $tipo === 'unidade' ? 'UNIDADE PRODUTIVA IDENTIFICADA' : 'PARTICIPANTE IDENTIFICADO'; ?>
                                </small>
                                <h5 class="mb-1">
                                    <?php echo esc_html( $tipo === 'unidade' ? get_the_title( $unidade_id ) : $participante['nome'] ); ?>
                                </h5>
                                <p class="text-muted mb-0">Você está respondendo à avaliação desta vivência.</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4>Avalie sua experiência</h4>
                            <p class="text-muted">Para cada pergunta, selecione uma nota de 0 a 5.</p>
                        </div>

                        <?php $numero = 1; ?>
                        <?php foreach ( $perguntas as $chave => $configuracao ) : ?>
                            <div class="card pergunta-avaliacao mb-3">
                                <div class="card-body">
                                    <p class="font-weight-bold mb-3">
                                        <span class="numero-pergunta"><?php echo esc_html( sprintf( '%02d', $numero ) ); ?></span>
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
                                                    <?php checked( isset( $_POST['respostas'][ $chave ] ) && (string) wp_unslash( $_POST['respostas'][ $chave ] ) === (string) $nota ); ?>
                                                    required
                                                >
                                                <label class="form-check-label" for="<?php echo esc_attr( $chave . '_' . $nota ); ?>">
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
                                <label for="comentario" class="font-weight-bold">Comentários adicionais</label>
                                <p class="text-muted small">
                                    Compartilhe sugestões, elogios ou observações sobre a vivência.
                                    Este campo é opcional.
                                </p>
                                <textarea class="form-control" id="comentario" name="comentario" rows="4" placeholder="Escreva seu comentário aqui..."><?php echo isset( $_POST['comentario'] ) && is_string( $_POST['comentario'] ) ? esc_textarea( wp_unslash( $_POST['comentario'] ) ) : ''; ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success shadow ml-0">Enviar avaliação</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php get_footer();