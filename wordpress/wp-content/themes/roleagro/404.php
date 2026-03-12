<?php

get_header();
the_post();
?>

<div class="page-wrapper content-wrapper my-5">
    <section class="container page-content mt-5">
        <div class="page-content__description mb-4 d-flex flex-column justify-content-center align-items-center">
            <img src="<?php echo esc_url(  URL_IMG_THEME . '/nada-encontrado.svg' ); ?>" alt="Nenhum resultado encontrado." class="w-50 mb-4">
            <h4>Não encontramos a página que você tentou acessar.</h4>
            <h5>Verifique o link digitado e tente novamente.</h5>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn-outline-success mt-4">Voltar ao início</a>
        </div>
    </section>
</div>

<?php get_footer(); ?>