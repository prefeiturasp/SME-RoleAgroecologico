<?php

extract( $args );

$paleta_cores = get_sub_field( 'paleta_cores' );
$cor_background = isset( $paleta_cores['cor_background'] ) ? $paleta_cores['cor_background'] : null;
$cor_titulo = isset( $paleta_cores['cor_titulo'] ) ? $paleta_cores['cor_titulo'] : null;

?>
<div class="container-fluid bg-verde-claro-1" style="<?php echo esc_html( !empty( $cor_background ) ? "background-color:{$cor_background}" : '' ); ?>">
    <div class="row">
        <div class="container espaco-top-bototom">
            <div class="row">
                <div class="col titulo-passo-a-passo-2">
                    <div class="container">
                        <div class="row">
                            <div class="col">
                                <img src="<?= URL_IMG_THEME . '/svg-path-amarelo.png'; ?>" class="img-num">
                                <div class="conteudo-titulo">
                                    <span class="num-content"><?php echo esc_html( $contador . '. ' ); ?></span> 
                                    <span class="txt-titulo-content" style="<?php echo esc_html( !empty( $cor_titulo ) ? "color:{$cor_titulo}" : '' ); ?>">
                                        <?php echo esc_html( get_sub_field( 'titulo' ) ); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-6 p-2 txt-content-verde">
                <?php the_sub_field( 'conteudo' ); ?>
                </div>

                <?php if ( $galeria_imagens = get_sub_field( 'galeria_imagens' ) ) : ?>
                    <div class="col-sm-6 p-2">
                        <div class="row">
                            <div class="col">
                                <?php foreach ( $galeria_imagens as $key => $imagem ) : ?>
                                <div class="<?php echo esc_html( $key == 0 ? 'img-one' : 'img-two' ); ?>">
                                    <img
                                        src="<?php echo esc_url( $imagem['url'] ); ?>"
                                        class="img-thumbnail <?php echo esc_html( $key == 0 ? 'float-right img-conteudo-duplo-1' : 'float-left img-conteudo-duplo-2' ); ?>"
                                    >
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>