<?php extract( $args ); ?>

<?php if ( !isset( $autorizacoes ) || empty( $autorizacoes ) ) : ?>
    <p>Nenhum arquivo recebido até o momento.</p>
<?php else : ?>
<ul class="list-group list-group-flush">
    <?php
    foreach ( $autorizacoes as $autorizacao ) :
        $url_arquivo = get_template_directory_uri() . "/storage/{$autorizacao['caminho']}/{$autorizacao['nome_arquivo']}";
        ?>
        <li class="list-group-item border-white">
            <a href="<?php echo esc_url( $url_arquivo ); ?>" download>
                <?php echo esc_html( $autorizacao['nome_arquivo'] ) ?>
            </a>
            - Enviado em: <?php echo esc_html( date( 'd/m/Y à\s\ H:i', strtotime( $autorizacao['data_recebimento'] ) ) ); ?>
        </li>
        <?php
    endforeach;
    ?>
</ul>
<?php endif; ?>