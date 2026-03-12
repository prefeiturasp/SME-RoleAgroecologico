<?php

use App\Controllers\RoteiroController;
use App\Controllers\RoteiroQueryController;

$filtros = [
    // Taxonomias
    'periodo' => !empty($_GET['periodo']) ? sanitize_title($_GET['periodo']) : null,
    'dia'     => !empty($_GET['dia']) ? sanitize_title($_GET['dia']) : null,
    'regiao'  => !empty($_GET['regiao']) ? sanitize_title($_GET['regiao']) : null,

    // Metas (IDs de posts salvos no meta)
    'atrativo'        => !empty($_GET['atrativo']) ? sanitize_title($_GET['atrativo']) : null,
    'particularidade' => !empty($_GET['particularidade']) ? sanitize_title($_GET['particularidade']) : null,
    'local'           => !empty($_GET['local']) ? sanitize_title($_GET['local']) : null,

    'acessibilidade' => !empty($_GET['acessibilidade']) ? sanitize_text_field($_GET['acessibilidade']) : null,
    'almoco' => !empty($_GET['almoco']) ? sanitize_text_field($_GET['almoco']) : null,
];

// Seções que vão ser renderizadas na página
$secoes = ['regiao', 'acessibilidade', 'integral', 'parcial-sem-almoco', 'parcial-almoco'];

?>

<?php
foreach ( $secoes as $secao_id ) :
    $secao = RoteiroQueryController::get_secao_info( $secao_id );
    $query = RoteiroQueryController::listar( $secao_id, $filtros );
    
    if ( $secao && $query->have_posts() ) : ?>
        <article class="mb-5">
            <div class="row">
                <div class="col-4 col-sm-8 col-md-9">
                    <h4 class="mb-4 titulo-carrossel"><?php echo esc_html( $secao['titulo'] ); ?></h4>
                </div>
                <div class="col-4 col-sm-4 col-md-3 btn-carrossel text-right">
                    <a type="button" class="btn btn-outline-secondary" href="#carrossel-role-<?php echo esc_html( sanitize_title( $secao_id ) ); ?>" role="button" data-slide="prev">
                        <
                    </a>
                    &nbsp;
                    <a type="button" class="btn btn-outline-secondary" href="#carrossel-role-<?php echo esc_html( sanitize_title( $secao_id ) ); ?>" role="button" data-slide="next">
                        >
                    </a> 
                </div>
            </div>
            <hr class="separacao-roles">

            <div id="carrossel-role-<?php echo esc_html( sanitize_title( $secao_id ) ); ?>" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $i = 0;
                    while ( $query->have_posts() ) :
                        $query->the_post();

                        // Abrir novo slide a cada 4 itens
                        if ( $i % 4 === 0 ) :
                            if ( $i > 0 ) echo '</div></div>'; // fecha slide anterior
                            ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <div class="row">
                        <?php endif; ?>
                        
                        <div class="col-sm-6 col-md-3 col-6 mb-4 espaco-cards">
                            <div class="card card-role">
                                <img class="card-img-top" src="<?php echo esc_url( _theme_get_thumbnail( get_the_ID() ) ); ?>">
                                <div class="card-body">
                                    <?php if ( get_field( 'tipo_de_roteiro' ) == 'combo' ) : ?>
                                        <span class="badge badge-combo">Combo</span>
                                    <?php endif; ?>
                                    <?php if ( $regiao = get_field( 'regiao_tag_roteiro' ) ) : ?>
                                        <span class="badge badge-local"><?php echo esc_html( $regiao->name ); ?></span>
                                    <?php endif; ?>

                                    <p class="titulo-role-card" data-toggle="tooltip" data-placement="bottom" title="<?=esc_html(get_the_title());?>" data-custom-class="titulo-tooltip"><?php echo retornaTextoReduzido(esc_html(get_the_title()), 60) ; ?></p>

                                    <?php $roteiro = new RoteiroController( get_the_ID() ); ?>
                                    <?php if ($roteiro->get_atrativos_local() && !empty( $roteiro->get_atrativos_local() ) ) : ?>
                                        <div class="itens-atrativos">
                                        <?php foreach ( array_slice( $roteiro->get_atrativos_local(), 0, 3 ) as $atrativo ) : ?>
                                            <p class="txt-tipo">
                                                <img class="card-icon" src="<?php echo esc_url( get_field( 'icone-tax', $atrativo ) ); ?>">
                                                <?php echo esc_html( $atrativo->name ); ?>
                                            </p>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <?php if ( get_field( 'roteiro_com_acessibilidade' ) ) : ?>
                                            <div class="col-2"><img class="card-icon" src="<?= URL_IMG_THEME . '/icons/icone-cadeira.png'; ?>"></div>
                                        <?php endif; ?>

                                        <?php if ( get_field( 'roteiro_com_oferta_de_almoco' ) ) : ?>
                                            <div class="col-2"><img class="card-icon" src="<?= URL_IMG_THEME . '/icons/icone-talheres.png'; ?>"></div>
                                        <?php endif; ?>

                                        <div class="col-8">
                                            <a href="<?php echo esc_url( get_the_permalink() ); ?>">
                                                <img class="img-mais-info" src="<?= URL_IMG_THEME . '/btn-mais-info.png'; ?>">
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $i++;
                    endwhile;

                    if ( $i > 0 ) echo '</div></div>'; // fecha o último slide
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </article>
    <?php endif; ?>
<?php endforeach; ?>

<script>
jQuery(function($){
    const totalItens = $('.carousel-inner').length;
    if (totalItens === 0) {
        $('.container .layout-principal').append(`<div class="sem-resultados flex-column d-flex align-items-center justify-content-center mb-5">
            <img src="<?php echo esc_url(  URL_IMG_THEME . '/nada-encontrado.svg' ); ?>" alt="Nenhum resultado encontrado." class="w-50 mb-4">
            <h5 class="text-success">Nenhum resultado corresponde a sua busca.</h5>
        </div>`);
    }
});
</script>
