<?php extract( $args ); ?>

<?php if ( $itens_accordion = get_sub_field( 'itens_accordion' ) ) : ?>
    <div class="container-fluid bg-verde-7">
        <div class="row">
            <div class="container espaco-top-bototom">
                <div class="row mx-md-n5">
                    <div class="col titulo-passo-a-passo">
                        <p class="titulo-content-duvidas"><?php echo esc_html( get_sub_field( 'titulo' ) ); ?></p>
                        <?php
                        get_template_part( 'src/Views/template-parts/duvidas-frequentes', null, [
                            'itens_accordion' => $itens_accordion,
                            'index' => isset( $contador ) ? $contador : 0
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>