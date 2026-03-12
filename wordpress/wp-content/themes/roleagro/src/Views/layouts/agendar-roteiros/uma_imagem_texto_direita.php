<?php

extract( $args );

$paleta_cores = get_sub_field( 'paleta_cores' );
$cor_background = isset( $paleta_cores['cor_background'] ) ? $paleta_cores['cor_background'] : null;
$cor_titulo = isset( $paleta_cores['cor_titulo'] ) ? $paleta_cores['cor_titulo'] : null;

?>
<div class="container-fluid" style="<?php echo esc_html( !empty( $cor_background ) ? "background-color:{$cor_background}" : '' ); ?>">
    <div class="row">
        <div class="container espaco-top-bototom">
            <div class="row mx-md-n5">
                <div class="col-sm titulo-passo-a-passo">
                    <img src="<?= URL_IMG_THEME . '/svg-path-verde.png'; ?>" class="img-num">
                    <div class="conteudo-titulo">
                        <span class="num-content"><?php echo esc_html( $contador . '. ' ); ?></span> 
                        <span class="txt-titulo-content" style="<?php echo esc_html( !empty( $cor_titulo ) ? "color:{$cor_titulo}" : '' ); ?>">
                            <?php echo esc_html( get_sub_field( 'titulo' ) ); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="row">
                <?php if ( $imagem_destacada = get_sub_field( 'imagem_destacada' ) ) : ?>
                    <div class="col-sm-5 p-2">
                        <img src="<?php echo esc_url( $imagem_destacada['url'] ); ?>" class="img-conteudo-unico" alt="...">
                    </div>
                <?php endif; ?>
                <div class="col-sm-7 p-2 txt-content-verde">
                    <?php the_sub_field( 'conteudo' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>