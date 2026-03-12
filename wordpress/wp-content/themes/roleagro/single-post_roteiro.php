<?php

use App\Controllers\RoteiroController;

wp_enqueue_style( 'swiper' );
wp_enqueue_style( 'fancybox' );

wp_enqueue_script( 'swiper' );
wp_enqueue_script( 'fancybox' );
wp_enqueue_script( 'moment-tz' );

get_header();
the_post();

$roteiro = new RoteiroController( get_the_ID() );

$galeria_imagens = $roteiro->get_galeria_imagens();
$atrativos = $roteiro->get_atrativos_local();
$aspectos = $roteiro->get_aspectos_local();
$unidades_info = $roteiro->get_unidades_info();
$datas_ofertadas = $roteiro->get_datas_ofertadas();
$datas_indisponiveis = $roteiro->get_datas_indisponiveis();

wp_localize_script('calendario', 'datas', [
    'disponiveis' => $datas_ofertadas,
    'indisponiveis' => $datas_indisponiveis
]);

?>

<?php if ( can_realizar_agendamentos() && !tem_disponibilidade_agendamento() ) : ?>
    <div class="alert alert-warning container mt-2 text-center" role="alert">
        <strong>
            <i class="fa fa-exclamation-triangle" aria-hidden="true"></i>
            Sua unidade escolar já atingiu o limite de agendamentos permitidos para este ciclo.    
        </strong>
    </div>
<?php endif; ?>

<div class="page-wrapper content-wrapper page-detalhe-roteiro my-2">
    <?php
    get_template_part( 'src/Views/template-parts/page-header-verde', null, [
        'titulo_pagina' => 'Descubra este roteiro'
    ] );
    ?>

    <section class="container justify-content-center mb-4 mt-4" id="detalhe-roteiro-topo">
        <div class="row">
            <a href="<?php echo get_previous_page_url(); ?>" class="col-md-2 mb-4 btn-voltar">
                <i class="fa fa-arrow-left" aria-hidden="true"></i>
                Voltar
            </a>
            <div class="col-md-10 titulo d-flex flex-column flex-md-row align-items-start align-align-items-md-center justify-content-center mb-2">
                <h1><span>Rolê:</span> <?php echo esc_html( get_the_title() ); ?></h1>
            </div>
        </div>
    </section>

    <div class="container" id="detalhe-roteiro-sobre">
        <div class="row">
            <?php if ( !empty( $galeria_imagens ) ) : ?>
                <div class="col col-12 col-sm-7 order-1 order-sm-0 borda-divisao" id="detalhe-roteiro__galeria">
                    <div class="swiper gallery-top mb-3">
                        <div class="swiper-wrapper">
                            <?php foreach ( $galeria_imagens as $imagem ) : ?>
                                <div class="swiper-slide">
                                    <a href="<?php echo esc_url( $imagem['url']); ?>" data-fancybox="gallery">
                                        <img
                                            src="<?php echo esc_url( $imagem['sizes']['slider-size'] ); ?>"
                                            alt="<?php echo esc_url( $imagem['caption'] ); ?>"
                                            class="img-fluid img-galeria-roteiro"
                                            >
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Thumbs -->
                    <div class="swiper gallery-thumbs mb-4">
                        <div class="swiper-wrapper">
                            <?php foreach ( $galeria_imagens as $imagem ) : ?>
                                <div class="swiper-slide">
                                    <img
                                        src="<?php echo esc_url( $imagem['sizes']['thumbnail'] ); ?>"
                                        alt="<?php echo esc_url( $imagem['caption'] ); ?>"
                                        class="img-fluid"
                                        >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="col col-12 col-sm-5 mt-4 mt-md-0 order-0  order-sm-1" id="detalhe-roteiro__conteudo">
                <?php if ( !empty( $unidades_info ) ) : ?>
                    <h5>Sobre este roteiro</h5>
                    <?php foreach ( $unidades_info as $unidade_info ) : ?>
                        <p><?php echo esc_html( $unidade_info['desc_resumida'] ); ?></p>
                    <?php endforeach; ?>
                    
                <?php endif; ?>

                <?php if ( $tags = wp_get_post_tags( get_the_ID() ) ) : ?>
                    <div class="row" id="detalhe-roteiro__tags">
                        <?php foreach ( $tags as $tag ) : ?>
                            <span class="tag col-auto">
                                <?php if ( $imagem_tag = get_field( 'icone-tax', $tag ) ) : ?>
                                    <img src="<?php echo esc_url( $imagem_tag ); ?>" class="mr-2">
                                <?php endif; ?>
                                <?php echo esc_html( $tag->name ); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-7">
                <?php if ( !empty( $atrativos ) ) : ?>
                    <section class="mt-4 borda-divisao" id="detalhe-roteiro__atrativos">
                        <h6>Atrativos encontrados neste local</h6>
                        <div class="row mb-4" id="listagem-atrativos">
                            <?php foreach ( $atrativos as $atrativo ) : ?>
                                <span class="atrativo-item col-6">
                                    <?php if ( $icone_atrativo = get_field( 'icone-tax', $atrativo ) ) : ?>
                                        <img src="<?php echo esc_url( $icone_atrativo ); ?>" class="mr-2">
                                    <?php endif; ?>
                                    <?php echo esc_html( $atrativo->name ); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
                
                <?php if ( !empty( $aspectos ) ) : ?>
                    <section class="mt-4 borda-divisao" id="detalhe-roteiro__atrativos">
                        <h6>Particularidades deste roteiro</h6>
                        <div class="row mb-4" id="listagem-atrativos">
                            <?php foreach ( $aspectos as $aspecto ) : ?>
                                <span class="atrativo-item col-6">
                                    <?php if ( $icone_aspecto = get_field( 'icone-tax', $aspecto ) ) : ?>
                                        <img src="<?php echo esc_url( $icone_aspecto ); ?>" class="mr-2">
                                    <?php endif; ?>
                                    <?php echo esc_html( $aspecto->name ); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( $mais_informacoes = get_field( 'mais_informacoes_do_roteiro' ) ) : ?>
                    <section class="mt-4" id="detalhe-roteiro__info">
                        <h6>Mais informações sobre este roteiro</h6>
                        <div class="mt-4" id="info-conteudo">
                            <?php echo wp_kses_post( $mais_informacoes ); ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
            <div class="col-12 col-sm-5">
                <section class="mt-4" id="detalhe-roteiro__calendario">
                    <h6>Datas disponíveis para agendamento</h6>
                    <span><?php echo esc_html( get_field( 'dias_ofertados' ) ); ?></span>

                    <?php get_template_part( 'src/Views/template-parts/calendario' ); ?>

                    <div class="row mt-5 ml-2" id="calendario-legendas">
                      <div class="col">
                        <i class="fa fa-square fa-lg disponivel" aria-hidden="true"></i>
                        <span>Datas disponíveis</span>
                        <?php $roteiro->get_datas_ofertadas(); ?>
                      </div>
                      <div class="col">
                        <i class="fa fa-square fa-lg indisponivel" aria-hidden="true"></i>
                        <span>Datas indisponíveis</span>
                      </div>
                    </div>
                    <div class="row mt-5">
                        <?php if ( can_realizar_agendamentos() && !tem_disponibilidade_agendamento() ) : ?>
                            <div class="col d-flex justify-content-center text-info bolder">
                                <em>
                                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                                    <?php $limite_agendamentos = intval( get_field( 'quantidade_agendamentos_permitidos', 'options' ) );  ?>
                                    Sua unidade escolar já atingiu o limite de agendamentos permitidos.
                                    Neste ciclo, só é possível realizar até 
                                    <?php echo esc_html( $limite_agendamentos ); ?>
                                    <?php echo _n( 'agendamento.', 'agendamentos.', $limite_agendamentos ); ?>
                                </em> 
                            </div>
                        <?php else : ?>
                            <div class="col d-flex justify-content-center">
                                <a href="<?= site_url('/agendamento/?rid='.get_the_ID())?>" id="btn-agendamento">Realizar Agendamento</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <?php if ( !empty( $unidades_info ) ) : ?>
        <section class="container mt-5 mb-5" id="detalhe-roteiro__unidades">
            <?php foreach ( $unidades_info as $unidade_info ) : ?>
                <div class="unidades__item mt-4">
                    <div class="row">
                        <div class="col-12 col-sm-7 unidades-item__descricao">
                            <strong><?php echo esc_html( "{$unidade_info['titulo']}" ); ?></strong>
                            <?php echo wp_kses_post( $unidade_info['desc_completa'] ); ?>
                        </div>
                        <?php if ( isset( $unidade_info['link_mapa'] ) && !empty( $unidade_info['link_mapa'] ) ) : ?>
                            <div class="col-12 col-sm-3 unidades-item__mapa">
                                <h6>Localização do Roteiro</h6>
                                <?php resolve_google_maps_url( $unidade_info['link_mapa'], 450, 350 ); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
    // Swiper Thumbs
    const galleryThumbs = new Swiper('.gallery-thumbs', {
        spaceBetween: 10,
        slidesPerView: 6,
        freeMode: true,
        watchSlidesProgress: true,
        breakpoints: {
            768: { slidesPerView: 6 },
            576: { slidesPerView: 4 },
            320: { slidesPerView: 3 }
        }
    });

    // Swiper Principal
    const galleryTop = new Swiper('.gallery-top', {
        loop: true,
        spaceBetween: 10,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        thumbs: {
            swiper: galleryThumbs
        }
    });

    // Fancybox
    Fancybox.bind("[data-fancybox='gallery']", {});
});

</script>

<?php get_footer(); ?>