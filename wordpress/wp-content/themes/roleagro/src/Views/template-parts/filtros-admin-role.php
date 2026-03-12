<?php

wp_enqueue_script( 'moment-tz' );
wp_enqueue_style( 'ranger-pick' );
wp_enqueue_script( 'ranger-pick' );

$roteiros = get_posts([
    'post_type' => 'post_roteiro',
    'post_status' => 'publish',
    'numberposts' => 100,
    'orderby' => 'name',
    'order' => 'ASC'
]);

$mesAtual =  date('m');
$anoAtual =  date('Y');

$url_link_post = '/agendamento-lista-presenca/';

?>

<article>
    <form class="container" action="<?php echo site_url($url_link_post); ?>">
        <div class="row">
            <div class="col-md mb-4">
                <h2 class="txt-green">Filtros</h2>
                <div class="form-row">
                   
                    <div class="col-md-4">
                        <label>Unidade Educacional:</label>
                        <input type="text" class="form-control" name="ue" placeholder="EOL ou nome da unidade">
                    </div>
                    
                    <div class="col-md-4">
                        <label>Roteiro:</label>
                        <select class="form-control" name="roteiro" style="margin-top: 0px;">
                            <option value="">Selecione o roteiro</option>
                            <?php foreach ( $roteiros as $roteiro ) : ?>
                                <option value="<?php echo esc_html( $roteiro->post_name ); ?>" <?php selected( old( 'roteiro' ), $roteiro->post_name ); ?>>
                                    <?php echo esc_html( $roteiro->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label>Data do Roteiro:</label>
                        <input type="text" id="data_vivencia" class="form-control" name="data_vivencia" value="01/01/<?= $anoAtual; ?> - 31/12/<?= $anoAtual; ?>">
                    </div>
                   
                </div>
            </div>
        </div>
        <br>
        <div class="container btn-filtros text-right">
            <a href="<?php echo esc_url( site_url( $url_link_post ) ); ?>" class="btn btn-outline-success">Limpar Filtros</a>
            <button type="submit" class="btn btn-success">Filtrar</button>
        </div>
    </form>
</article>
