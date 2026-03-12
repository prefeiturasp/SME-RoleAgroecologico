<?php 
/**
 * Template Name: Layout Administrar role
 */
get_header(); 
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
        <br>
        
        <?php get_template_part('src/Views/template-parts/admin-role', null, $args = array());  ?>

    </main>
</div>
<?php get_footer(); ?>