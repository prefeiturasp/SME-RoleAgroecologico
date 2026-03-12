<?php

/**
 * Template Name: Modelo de página - Sobre 
*/

get_header();
the_post();

?>
    <article>
        <div class="container mb-4">
            <div class="row titulo-page-sobre">
                <div class="col text-center d-flex flex-column flex-lg-row justify-content-center align-items-center mt-4 pt-4">
                    <?php
                    if ( get_field( 'tipo_titulo_pagina' ) === 'composto' ) :
                        $titulo_pagina = get_field( 'titulo_composto' );
                        ?>
                        <font size="5" class="mr-2"><?php echo esc_html( $titulo_pagina['parte_1'] ); ?></font>
                        <h1 class="font-role"><?php echo esc_html( $titulo_pagina['parte_2'] ); ?></h1>
                        <?php
                    else :
                        $titulo_pagina = get_field( 'titulo_simples' ) ?: get_the_title();
                        ?>
                        <h1 class="font-role"><?php echo esc_html( $titulo_pagina ); ?></h1>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
            </div>
        </div>

        <?php if ( !get_field( 'ocultar_bloco_banner' ) ) : ?>
            <div class="container-fluid bg-banner-sobre">
                <div class="row">
                    <div class="container">
                        <div class="row">
                            <div class="col-12 col-lg-6 my-4">
                                <?php if ( $titulo_banner = get_field( 'titulo_banner' ) ) : ?>
                                    <p class="titulo-banner-sobre text-uppercase"><?php echo esc_html( $titulo_banner['parte_1'] ); ?></p>
                                    <p class="subtitulo-banner-sobre text-uppercase"><?php echo esc_html( $titulo_banner['parte_2'] ); ?></p>
                                <?php endif; ?>
                                <div class="txt-banner-sobre">
                                    <?php the_field( 'texto_banner' ); ?>
                                </div>
                            </div>
                            <?php if ( $imagem_banner = get_field( 'imagem_banner' ) ) : ?>
                                <div class="d-none d-lg-block col-lg-6">
                                    <img
                                        class="img-banner-sobre"
                                        src="<?php echo esc_url( $imagem_banner['url'] ); ?>"
                                        alt="<?php echo esc_html( $imagem_banner['alt'] ); ?>"
                                        >
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !get_field( 'ocultar_bloco_sobre' ) ) : ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="container">
                        <div class="row">
                            <div class="col text-center py-5 txt-content-sobre">
                                <?php the_field( 'conteudo_bloco_sobre' ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !get_field( 'ocultar_bloco_cards' ) && $card_itens = get_field( 'listagem_cards' ) ) : ?>
            <div class="container-fluid bg-faixas p-4 d-flex align-items-center justify-content-center">

                <div class="row h-100">
                    <div class="col d-flex flex-column justify-content-between pt-4">
                        <p class="titulo-faixas"><?php echo esc_html( get_field( 'titulo_bloco_cards' ) ); ?></p>
                        <p class="subtitulo-faixas text-uppercase m-0"><?php echo esc_html( get_field( 'subtitulo_bloco_cards' ) ); ?></p>
                    </div>
                </div>
            </div>

            <div class="container-fluid my-5" id="cards-container">
                <div class="row">
                    <div class="container">
                        <div class="row">
                            <?php foreach ( $card_itens as $item ) : ?>
                                <div class="col-12 col-sm-6 col-lg-4 text-center mt-4 d-flex align-content-center justify-content-center">
                                    <div class="card text-white card-sobre">
                                        <img src="<?php echo esc_url( $item['imagem'] ); ?>" class="card-img-sobre">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo esc_html( $item['titulo'] ); ?></h5>
                                            <p class="card-text">
                                                <?php echo esc_html( wp_trim_words( $item['conteudo'], 30, '[...]' ) ); ?>.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !get_field( 'ocultar_bloco_numeros' ) ) : ?>
            <div class="container-fluid bg-verde-2 position-relative">
                <div class="container">
                    <div class="row d-flex flex-col flex-lg-row justify-content-center align-items-center">
                        <div class="col-12 col-lg-7 text-center">
                            <p class="titulo-banner-bottom">
                                <img src="<?= URL_IMG_THEME . '/mark-icon.png';?>" class="d-none d-lg-inline-block img-fluid">
                                <?php echo esc_html( get_field( 'titulo_bloco_numeros' ) ); ?>
                            </p>
                        </div>
                        <div class="col-12 col-lg-5 d-none d-lg-block">
                            <p class="subtitulo-banner-bottom ml-5 pt-2"><?php echo get_field( 'subtitulo_bloco_numeros' ); ?></p>
                        </div>
                    </div>
                </div>
                <?php if ( $image_bloco_numeros = get_field( 'bloco_numeros_imagem' ) ) : ?>
                    <img src="<?php echo esc_url( $image_bloco_numeros ); ?>" class="img-bloco-numeros d-none d-lg-block">
                <?php endif; ?>
            </div>


            <div class="container-fluid">
                <div class="container">
                    <?php if ( $numeros_vertical = get_field( 'bloco_numeros_itens_vertical' ) ) : ?>
                        <div class="row zindex-sticky">
                            <div class="col-12">
                                <?php foreach ( $numeros_vertical as $item ) : ?>
                                    <div class="numero-item mt-5">
                                        <p class="text-uppercase titulos-sub-content"><?php echo esc_html( $item['titulo'] ); ?></p>
                                        <p class="text-uppercase subtitulos-sub-content">
                                            <?php echo esc_html( $item['descricao_item'] ); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ( $numeros_horizontal = get_field( 'bloco_numeros_itens_horizontal' ) ) : ?>
                        <div class="row flex-col flex-lg-row mb-5">
                            <?php foreach ( $numeros_horizontal as $item ) : ?>
                                <div class="col-12 col-lg-4 numero-item mt-5">
                                    <p class="text-uppercase titulos-sub-content"><?php echo esc_html( $item['titulo'] ); ?></p>
                                    <p class="text-uppercase subtitulos-sub-content">
                                        <?php echo esc_html( $item['descricao_item'] ); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !get_field( 'ocultar_bloco_video' ) ) : ?>
            <div class="container-fluid bg-video-sobre" id="bloco-video-container">
                <div class="row">
                    <div class="container">
                        <div class="row pb-5">
                            <div class="col text-center">
                                <p class="text-uppercase titulo-video-sobre"><?php echo esc_html( get_field( 'titulo_bloco_video' ) ); ?></p>
                                <p class="text-uppercase subtitulo-video-sobre"><?php echo esc_html( get_field( 'subtitulo_bloco_video' ) ); ?></p>
                                <div class="video-sobre">
                                    <?php if ( get_field( 'tipo_video' ) === 'link' ) : ?>
                                        <?php echo _theme_generate_youtube_iframe( get_field( 'link_video' ) ); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ( !get_field( 'ocultar_bloco_faq' ) && $itens_faq = get_field( 'faq_itens' ) ) : ?>
            <div class="container-fluid" id="bloco-perguntas-frequentes">
                <div class="row">
                    <div class="container">
                        <div class="row">
                            <div class="col p-4 txt-content-sobre">
                                <p class="text-uppercase titulo-pergunta-frequente-sobre">
                                    <?php echo esc_html( get_field( 'titulo_bloco_faq' ) ); ?>
                                </p> 
                                <?php
                                foreach ( $itens_faq as $faq ) :
                                    $categoria_id = sanitize_title( $faq['categoria'] );
                                    ?>
                                    <div class="accordion p-4" id="accordion-<?php echo esc_attr( $categoria_id ); ?>">

                                        <span class="text-uppercase titulo-acordeon">
                                            <?php echo esc_html( $faq['categoria'] ); ?>
                                            <?php if ( !empty( $faq['descricao_categoria'] ) ) : ?>
                                                <small><?php echo esc_html( $faq['descricao_categoria'] ); ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <hr class="separacao-perguntas"> 

                                        <?php
                                        foreach ( $faq['itens_categoria'] as $key => $faq_item ) :
                                            $pergunta_id = "{$categoria_id}-{$key}";
                                            $collapse_id = "collapse-{$pergunta_id}";
                                            ?>
                                            <div class="card card-pergunta-sobre">
                                                <div class="card-header head-card-pergunta" id="heading-<?php echo esc_attr( $pergunta_id ); ?>">
                                                    <div class="content">
                                                        <div class="row">
                                                            <div class="col subtitulo-acordeon">
                                                                <?php echo esc_html( $faq_item['titulo'] ); ?>
                                                            </div>
                                                            <div class="col-2 text-right">
                                                                <i
                                                                    class="fa fa-plus icon-collapsed"
                                                                    data-toggle="collapse"
                                                                    data-target="#<?php echo esc_attr( $collapse_id ); ?>"
                                                                    aria-expanded="false"
                                                                    aria-controls="<?php echo esc_attr( $collapse_id ); ?>">
                                                                </i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    id="<?php echo esc_attr( $collapse_id ); ?>"
                                                    class="collapse"
                                                    aria-labelledby="heading-<?php echo esc_attr( $pergunta_id ); ?>"
                                                    data-parent="#accordion-<?php echo esc_attr( $categoria_id ); ?>"
                                                    >
                                                    <div class="card-body conteudo-acordeon">
                                                        <?php echo esc_html( $faq_item['conteudo'] ); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        endforeach;
                                        ?>
                                    </div>
                                <?php
                                endforeach;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </article>

    <script>
        jQuery(function ($) {
            $('.collapse').on('show.bs.collapse', function () {
                $(this).prev('.card-header').find('.icon-collapsed').removeClass('fa-plus').addClass('fa-minus');
            });

            $('.collapse').on('hide.bs.collapse', function () {
                $(this).prev('.card-header').find('.icon-collapsed').removeClass('fa-minus').addClass('fa-plus');
            });
        });
    </script>
<?php get_footer( 'logos' ); ?>