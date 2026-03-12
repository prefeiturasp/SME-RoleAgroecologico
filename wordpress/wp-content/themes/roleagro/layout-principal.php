<?php 
/**
 * Template Name: Layout Principal
 */
get_header(); 
?>
<img class="img-fluid" src="<?php header_image(); ?>" height="<?php get_custom_header()->height; ?>" width="<?php get_custom_header()->width; ?>" alt="">
<div class="content-area">
    <main>
        <section class="filtros">
            <div class="container-fluid bg-filter shadow-sm">
                <?php get_template_part('src/Views/template-parts/filtros'); ?>
            </div>
        </section>
        <section class="conteudo">
            <div class="container">
                <div class="layout-principal">
                    <?php
                        if( have_posts()):
                            while( have_posts()) : the_post();
                        
                                get_template_part('src/Views/template-parts/conteudo', get_post_format(), $args = array());

                            endwhile;
                        else:
                    ?>
                    <p>Não há posts</p>
                    <?php 
                        endif; 
                    ?>
                </div>

            </div>
        </section>
    </main>
</div>
<?php get_footer(); ?>