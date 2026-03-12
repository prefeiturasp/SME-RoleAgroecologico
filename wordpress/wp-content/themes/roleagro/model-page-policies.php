<?php

/**
 * Template Name: Modelo de página - Plano de contingência
 */

get_header();
the_post();

?>

<div class="page-wrapper content-wrapper page-accordion my-5">
    <?php
    get_template_part( 'src/Views/template-parts/page-header', null, [
        'titulo_pagina' => get_field( 'titulo_personalizado' ) ?: get_the_title()
    ] );
    ?>

    <section class="container page-content mt-5">
        <div class="page-content__description mb-4">
            <?php the_content(); ?>
        </div>

        <?php if ( $accordion_itens = get_field( 'lista_itens_accordion' ) ) : ?>
            <div class="page-content__accordion">
                <div class="accordion" id="accordion-items">
                    <?php foreach ( $accordion_itens as $key => $item ) :
                        $i = $key + 1;
                    ?>
                        <div class="card mb-4">
                            <div class="card-header p-0" id="heading-<?php echo esc_attr( $i ); ?>">
                                <h2 class="mb-0">
                                    <button 
                                        class="btn btn-link d-flex justify-content-between align-items-center w-100 text-left collapsed"
                                        type="button" 
                                        data-toggle="collapse" 
                                        data-target="#item-<?php echo esc_attr( $i ); ?>" 
                                        aria-expanded="false" 
                                        aria-controls="item-<?php echo esc_attr( $i ); ?>">
                                        
                                        <span><?php echo esc_html( "{$i}. {$item['titulo']}" ); ?></span>
                                        <i class="fa fa-chevron-down ml-2 accordion-icon"></i>
                                    </button>
                                </h2>
                            </div>

                            <div 
                                id="item-<?php echo esc_attr( $i ); ?>" 
                                class="collapse" 
                                aria-labelledby="heading-<?php echo esc_attr( $i ); ?>" 
                                data-parent="#accordion-items">
                                <div class="card-body p-4 rich-content">
                                    <?php echo _theme_formatar_conteudo_texto( $item['conteudo'] ); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php get_footer(); ?>