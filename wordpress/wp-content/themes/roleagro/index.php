<?php get_header(); ?>
<div class="content-area">
    <main>
        <section class="conteudo">
            <div class="container">
                <div class="row">
                    <?php
                        if( have_posts()):
                            while( have_posts()) : the_post();

                                get_template_part('src/Views/template-parts/conteudo', get_post_format(), $args = array() );

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