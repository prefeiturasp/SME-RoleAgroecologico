<?php

use App\Controllers\RoteiroController;
use App\Controllers\AgendamentoController;
use App\Controllers\RoteiroQueryController;

$posts = [];
$msg = '';

if(isset($_GET['ue']) || isset($_GET['roteiro']) || isset($_GET['data_vivencia'])){

    $filtros = [
        // Taxonomias
        'ue'   => !empty($_GET['ue']) ? sanitize_title($_GET['ue']) : null,
        'roteiro' => !empty($_GET['roteiro']) ? sanitize_title($_GET['roteiro']) : null,
        'periodo' => !empty($_GET['data_vivencia']) ? sanitize_title($_GET['data_vivencia']) : null
    ];

    $resultado = RoteiroQueryController::buscar_agendamentos($filtros);
    $arrPosts = [];
    while ( $resultado->have_posts() ) {
        $resultado->the_post();
        $idPost = get_the_ID();
        $titulo = get_the_title();
        $arrPosts[] = array("id_post"=>$idPost,"titulo"=>$titulo);
    }
    wp_reset_postdata();
    $posts = RoteiroQueryController::retorna_inscricao($arrPosts);
} else {
    $posts = AgendamentoController::getAgendamentoListaPresenca();
}

// Seções que vão ser renderizadas na página
$secoes = array('Rolê Agendados', 'Rolês Realizados', 'Rolês Cancelados');

if(is_array($posts) && count($posts) > 0):
    for($s=0;count($posts)>$s;$s++):
?> 
        <article class="mb-5">
            <div class="row">
                <div class="col-4 col-sm-8 col-md-9">
                    <h4 class="mb-4 titulo-carrossel"><?= $secoes[$s] ?></h4>
                </div>
                <div class="col-4 col-sm-4 col-md-3 btn-carrossel text-right">
                    <a type="button" class="btn btn-outline-secondary" href="#carrossel-role-<?= $s ?>" role="button" data-slide="prev">
                        <
                    </a>
                    &nbsp;
                    <a type="button" class="btn btn-outline-secondary" href="#carrossel-role-<?= $s ?>" role="button" data-slide="next">
                        >
                    </a> 
                </div>
            </div>
            <hr class="separacao-roles">
            
            <div id="carrossel-role-<?= $s ?>" class="carousel slide" data-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $i = 0;
                    foreach ( $posts[$s] as $inscricao ) :
                        $roteiro_id = get_post_meta( $inscricao['ID'], 'id_roteiro_inscricao', true );
                        $rot = new RoteiroController( $roteiro_id );
                        // Abrir novo slide a cada 4 itens
                        if ( $i % 4 === 0 ) :
                            if ( $i > 0 ) echo '</div></div>'; // fecha slide anterior
                            ?>
                            <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                                <div class="row">
                        <?php endif; ?>
                        
                        <div class="col-sm-6 col-md-3 col-6 mb-4 espaco-cards">
                            <div class="card card-role">
                                <?php if($secoes[$s] != 'Rolês Cancelados'): ?>
                                <span class="data-agendamento"><?= $inscricao['data_agendamento']; ?></span>
                                <?php endif; ?>
                                <img class="card-img-top" src="<?= $inscricao['thumbnail']; ?>">
                                <div class="card-body">
                                    <?php if ( $inscricao['tipo_roteiro'] == 'combo' ) : ?>
                                        <span class="badge badge-combo">Combo</span>
                                    <?php endif; ?>
                                    <?php if ( $inscricao['regiao'] ) : ?>
                                        <span class="badge badge-local"><?php echo esc_html( $inscricao['regiao'] ); ?></span>
                                    <?php endif; ?>

                                    <p class="titulo-role-card" data-toggle="tooltip" data-placement="bottom" title="<?=$inscricao['post_title'];?>" data-custom-class="titulo-tooltip"><?php echo retornaTextoReduzido(esc_html($inscricao['post_title']), 60) ; ?></p>
                                    
                                    <?php if ( !empty( $rot->get_atrativos_local() ) ) : ?>
                                        <div class="itens-atrativos">
                                        <?php foreach ( array_slice( $rot->get_atrativos_local(), 0, 3 ) as $atrativo ) : ?>
                                            <p class="txt-tipo">
                                                <img class="card-icon" src="<?php echo esc_url( get_field( 'icone-tax', $atrativo ) ); ?>">
                                                <?php echo esc_html( $atrativo->name ); ?>
                                            </p>
                                        <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-2">
                                            <?php if ( $inscricao['acessibilidade'] ) : ?>
                                                <img class="card-icon" src="<?= URL_IMG_THEME . '/icons/icone-cadeira.png'; ?>">
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-2">
                                            <?php if ( $inscricao['almoco'] ) : ?>
                                                <img class="card-icon" src="<?= URL_IMG_THEME . '/icons/icone-talheres.png'; ?>">
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-8">
                                            <a href="<?= site_url('/lista-de-presenca/?iid='.$inscricao['ID'].'&rid='.$inscricao['id_roteiro']);?>" target="_blank" class="btn btn-card-role btn-sm shadow-sm">Lista de Presença</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php
                        $i++;
                    endforeach;
                    ?>
                </div>
            </div>
        </article>
<?php 
    endfor; 
endif; ?>
