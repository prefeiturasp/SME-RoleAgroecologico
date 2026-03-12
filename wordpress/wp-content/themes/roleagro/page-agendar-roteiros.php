<?php

/** Template Name: Agendar roteiros */

get_header();
the_post();

$uri = get_template_directory_uri();

?>
    <article>
        <div class="container">
            <div class="row titulo-page-sobre">
                <div class="col-sm text-center">
                    <?php
                    if ( get_field( 'tipo_titulo_pagina' ) === 'composto' ) :
                        $titulo_pagina = get_field( 'titulo_composto' );
                        ?>
                        <font size="6"><?php echo esc_html( $titulo_pagina['parte_1'] ); ?></font>
                        <h1 class="font-role-2 m-0"><?php echo esc_html( $titulo_pagina['parte_2'] ); ?></h1>
                        <?php
                    else :
                        $titulo_pagina = get_field( 'titulo_simples' ) ?: get_the_title();
                        ?>
                        <h1 class="font-role-2 m-0"><?php echo esc_html( $titulo_pagina ); ?></h1>
                        <?php
                    endif;
                    ?>
                </div>
            </div>
            </div>
        </div>

        <?php if ( have_rows( 'blocos_pagina' ) ) : ?>
            <?php
            $contador = 0;
            while ( have_rows( 'blocos_pagina' ) ) {
                the_row();
                switch ( get_row_layout() ) {
                    case 'imagem_texto_direita':
                        $contador++;
                        get_template_part( 'src/Views/layouts/agendar-roteiros/uma_imagem_texto_direita', null, ['contador' => $contador] );
                        break;
                    case 'duas_imagens_texto_esquerda':
                        $contador++;
                        get_template_part( 'src/Views/layouts/agendar-roteiros/duas_imagens_texto_esquerda', null, ['contador' => $contador] );
                        break;
                    case 'accordion':
                        get_template_part( 'src/Views/layouts/agendar-roteiros/accordion', null, ['contador' => $contador] );
                        break;
                    
                }
            }
            ?>

        <?php endif; ?>
    </article>

<?php get_footer('secundario'); ?>