<?php

/**
 * Template Name: Página - Agendamento
 */
use App\Controllers\AgendamentoController;
use App\Controllers\RoteiroController;

wp_enqueue_script( 'alpine-mask' );
wp_enqueue_script( 'alpine' );
wp_enqueue_script( 'agendamento' );
wp_enqueue_script( 'moment-tz' );

if ( !is_user_logged_in() ) {
    wp_redirect( home_url() . '/login?redirect_to=%2Fagendamento%2F%3Frid%3D'.$_REQUEST['rid'] );
    exit;
} else {
    $acesso = null;
    $current_user = wp_get_current_user();
    foreach ( $current_user->allcaps as $key => $value ) {
        if ($key == 'publish_inscricaos') { 
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

if(isset($_REQUEST['rid'])){
    $post_ID = $_REQUEST['rid'];
}

if ( !verifica_usuario_logado_tem_ue() ) {
    wp_redirect_with_message(
        get_the_permalink( $post_ID ),
        'Seu usuário não tem permissão para realizar agendamentos',
        'warning'
    );
}

if ( !tem_disponibilidade_agendamento() ) {
    wp_redirect( get_the_permalink( $post_ID ) );
    exit;
}

$agendamento = new AgendamentoController( $post_ID );
$roteiro = new RoteiroController( $post_ID );

$atrativos = $roteiro->get_atrativos_local();
$aspectos = $roteiro->get_aspectos_local();
$datas_ofertadas = $roteiro->get_datas_ofertadas();
$datas_indisponiveis = $roteiro->get_datas_indisponiveis();

wp_localize_script('calendario', 'datas', [
    'disponiveis' => $datas_ofertadas,
    'indisponiveis' => $datas_indisponiveis
]);

get_header();
the_post();

?>

<div class="page-wrapper content-wrapper page-agendamento my-2" x-data="agendamentoForm()" x-init="carregarDados()">
    <?php
    get_template_part( 'src/Views/template-parts/page-header-verde', null, [
        'titulo_pagina' => 'Reserve seu Rolê'
    ] );
    ?>

    <section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
        <div class="row">
            <button class="col-md-2 mb-4 btn-voltar" id="btnVoltarAgendamento" x-on:click="acionaBtnVoltar()">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                Voltar
            </button>
            <div class="col-md-10 titulo d-flex flex-column flex-md-row align-items-start align-align-items-md-center justify-content-center mb-2">
                <h1><span>Rolê:</span> <?= esc_html( get_the_title($post_ID) ) ?></h1>
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
                <?php $qtdMax = get_post_meta($_REQUEST['rid'], 'capacidade_maxima_de_participantes', true); ?>
                <span>Capacidade deste roteiro: <?= $qtdMax ?> pessoas</span>
            </div>
        </div>
    </section>

    <section class="container justify-content-center mb-4 mt-5 p-0" id="agendamento-form">
        <div class="form-abas">
            <div class="row">
                <div class="col" :class="step === 1 ? 'ativo' : ''">
                    <span>1</span>
                    <p class="d-sm-inline d-none">Dados da UE, Datas e Turmas</p>
                </div>
                <div class="col" :class="step === 2 ? 'ativo' : ''">
                    <span>2</span>
                    <p class="d-sm-inline d-none">Estudantes, Educadores e Acompanhantes</p>
                </div>
                <div class="col" :class="step === 3 ? 'ativo' : ''">
                    <span>3</span>
                    <p class="d-sm-inline d-none">Confirmação e Autorizações</p>
                </div>
            </div>
        </div>
        <div class="form-conteudo">
            <div x-show="step === 1" class="step">
                <div class="container mt-4">
                    <?php
                        get_template_part( 'src/Views/template-parts/agendamento/dados-ue-datas-turmas', null, ['datas_ofertadas' => array_diff( $datas_ofertadas, $datas_indisponiveis )] );
                    ?>
                </div>
            </div>
            <div x-show="step === 2" class="step">
                <div class="container mt-4">
                    <?php
                        get_template_part('src/Views/template-parts/agendamento/estudantes-educadores-acompanhantes');
                    ?>
                </div>
            </div>
            <div x-show="step === 3" class="step">
                <div class="container mt-4">
                    <?php
                    get_template_part( 'src/Views/template-parts/agendamento/confirmacoes-autorizacoes', null, [
                        'roteiro_id' => $post_ID,
                        'atrativos' => $atrativos,
                        'aspectos' => $aspectos
                    ] );
                    ?>
                </div>
            </div>
        </div>
        <div class="form-footer">
            <div class="navegacao d-flex align-items-center justify-content-between">
                <button x-show="step > 1" @click="anterior()" class="btn btn-outline-success">Retornar</button>
                <button x-show="step < 3" @click="proximo()" class="btn btn-success align-self-end">Continuar</button>
                <button x-show="step == 3" @click="salvarAgendamento()" class="btn btn-success align-self-end">Confirmar Reserva</button>
            </div>
        </div>
    </section>

</div>


<?php get_footer(); ?>