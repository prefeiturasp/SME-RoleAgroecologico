<?php

$periodos = get_terms( ['taxonomy' => 'tax_up_periodos-de-oferta'] );
$dias = get_terms( ['taxonomy' => 'tax_up_disponibilidade-semana'] );
$regioes = get_terms( ['taxonomy' => 'tax_up_regioes'] );
$atrativos = get_terms( ['taxonomy' => 'tax_up_atrativos'] );
$particularidades = get_terms( ['taxonomy' => 'tax_up_aspectos-do-local'] );
$locais = get_posts([
    'post_type' => 'post_up',
    'post_status' => 'publish',
    'numberposts' => 100,
    'orderby' => 'name',
    'order' => 'ASC'
]);

?>

<article>
    <form class="container" action="<?php echo home_url(); ?>">
        <div class="row">
            <div class="col-md-2 img-encontre-um-role">
                <img src="<?php echo get_template_directory_uri() . '/src/Views/assets/img/encontre-um-role.png';?>" height="90">
            </div>
            <div class="col-md-10 mb-4">
                <div class="form-row">
                    <?php if ( $periodos ) : ?>
                        <div class="col-md">
                            <select class="form-control align-bottom" name="periodo">
                                <option value="">Filtre por período</option>
                                <?php foreach ( $periodos as $periodo ) : ?>
                                    <option
                                        value="<?php echo esc_html( $periodo->slug ); ?>"
                                        <?php selected( old( 'periodo' ), $periodo->slug ); ?>
                                        >
                                        <?php echo esc_html( $periodo->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-group">
                                <a class="btn" id="btn-mais-filtros">+filtros</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $dias ) : ?>
                        <div class="col-md">
                            <select class="form-control align-bottom" name="dia">
                                <option value="">Filtre por dias da semana</option>
                                <?php foreach ( $dias as $dia ) : ?>
                                    <option
                                        value="<?php echo esc_html( $dia->slug ); ?>"
                                        <?php selected( old( 'dia' ), $dia->slug ); ?>
                                        >
                                        <?php echo esc_html( $dia->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ( $atrativos ) : ?>
                        <div class="col-md">
                            <select class="form-control align-bottom" name="atrativo">
                                <option value="">Filtre por atrativos</option>
                                <?php foreach ( $atrativos as $atrativo ) : ?>
                                    <option
                                        value="<?php echo esc_html( $atrativo->slug ); ?>"
                                        <?php selected( old( 'atrativo' ), $atrativo->slug ); ?>
                                        >
                                        <?php echo esc_html( $atrativo->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="form-row mt-2" id="mais-filtros" style="display: none;">
                    <?php if ( $regioes ) : ?>
                        <div class="col-md-4">
                            <select class="form-control align-bottom" name="regiao">
                                <option value="">Filtre por regiões</option>
                                <?php foreach ( $regioes as $regiao ) : ?>
                                    <option
                                        value="<?php echo esc_html( $regiao->slug ); ?>"
                                        <?php selected( old( 'regiao' ), $regiao->slug ); ?>
                                        >
                                        <?php echo esc_html( $regiao->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ( $locais ) : ?>
                        <div class="col-md-4">
                            <select class="form-control align-bottom" name="local" id="select-locais">
                                <option value="">Filtre por locais</option>
                                <?php foreach ( $locais as $local ) : ?>
                                    <option
                                        value="<?php echo esc_html( $local->post_name ); ?>"
                                        <?php selected( old( 'local' ), $local->post_name ); ?>
                                        >
                                        <?php echo esc_html( $local->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ( $particularidades ) : ?>
                        <div class="col-md-4">
                            <select class="form-control align-bottom" name="particularidade">
                                <option value="">Filtre por particularidades</option>
                                <?php foreach ( $particularidades as $particularidade ) : ?>
                                    <option
                                        value="<?php echo esc_html( $particularidade->slug ); ?>"
                                        <?php selected( old( 'particularidade' ), $particularidade->slug ); ?>
                                        >
                                        <?php echo esc_html( $particularidade->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="col-md-6 mt-4">
                        <div class="botoes-filtro">
                            <input
                                type="checkbox"
                                id="acessibilidade"
                                name="acessibilidade"
                                value="1"
                                <?php checked( old( 'acessibilidade' ), '1' ); ?>
                                >
                            <label for="acessibilidade"><i class="fa fa-wheelchair" aria-hidden="true"></i>Acessibilidade</label>

                            <input
                                type="checkbox"
                                id="almoco"
                                name="almoco"
                                value="1"
                                <?php checked( old( 'almoco' ), '1' ); ?>
                                >
                            <label for="almoco"><i class="fa fa-cutlery" aria-hidden="true"></i>Com almoço</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container btn-filtros text-right">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline-success">Limpar Filtros</a>
            <button type="submit" class="btn btn-success">Buscar Rolês</button>
            <input type="hidden" id="input-mais-filtros" name="mais-filtros" value="">
        </div>
    </form>
</article>
<script>
    jQuery(function ($) {

        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);

        if (urlParams.get('mais-filtros') === 'ativo') {
            $('#mais-filtros').show();
            $('#btn-mais-filtros').hide();
        }

        $('#btn-mais-filtros').on('click', function () {
            $('#mais-filtros').slideDown(300);
            $('#input-mais-filtros').val('ativo');
            $(this).hide();
        })
		
		// $('#select-locais').select2({
        //     language: {
        //         noResults: function () {
        //             return "Nenhum resultado encontrado.";
        //         }
        //     }
        // });
    })
</script>