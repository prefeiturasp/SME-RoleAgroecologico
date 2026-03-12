<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rolê Agroecológico</title>
    <?php

 wp_head(); ?>
</head>
<body id="conteudo-body" <?php body_class(); ?>>
    <header>
        <section class="top-bar">
            <div class="container">
                <div class="row">
                    <div class="top-links col-md-9 col-9">
                        <?php 
                            wp_nav_menu( array(
                                'theme_location' => 'main_menu_top_left', // Substitua pelo nome do seu menu
                                'menu_class' => 'navbar navbar-expand-lg menu-top-bar', // Classe personalizada para a lista <ul>
                                'container' => false, // Remove o container <div>
                                'menu_id' => 'menu-top-bar', // ID personalizado para a lista <ul>
                            ) );
                         ?>
                    </div>
                    <div class="top-social-media-icons col-md-3 col-3 text-right">
                        <img src="<?= URL_IMG_THEME . '/icons/icone-facebook.png'; ?>" alt="">
                        <img src="<?= URL_IMG_THEME . '/icons/icone-insta.png'; ?>" alt="">
                        <img src="<?= URL_IMG_THEME . '/icons/icone-yt.png'; ?>" alt="">
                    </div>
                </div>
            </div>
        </section>
        <section class="menu-area">
            <div class="container">
                <div class="row">
                    <div class="col-sm-4 col-md-2 col-2">
                        <section class="logo col-md-2 col-12 text-center">
                            <img src="<?= URL_IMG_THEME . '/role-logo-100.png'; ?>" width="100">
                        </section>
                    </div>
                    <div class="col-sm-8 col-md-10 col-10">

                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col">
                                <nav id="menu-sup-principal" class="menu menu-sup-principal text-right">
                                    <?php 
                                        wp_nav_menu(
                                            array(
                                                'theme_location' => 'main_menu_role_top_right'
                                            )
                                        );
                                        ?>
                                </nav>
                            </div>
                        </div>
                        <div class="row sublinhado">
                            <div class="col-12 d-flex justify-content-end">
                                <nav class="navbar navbar-expand-lg navbar-light" role="navigation">
                                    <div class="container">
                                        <span class="navbar-brand"></span>
                                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#menu-principal" aria-controls="menu-principal" aria-expanded="false" aria-label="Toggle navigation">
                                            <span class="navbar-toggler-icon"></span>
                                        </button>
                                        <div class="collapse navbar-collapse menu-principal" id="menu-principal">
                                            <?php 
                                                $args = array(
                                                    'theme_location' => 'main_menu_role',
                                                    'container'      => 'nav',
                                                    'container_class' => '', //Adiciona style na tag <nav>
                                                    'menu_class'     => '', //Adiciona style na tag <ul>
                                                    'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                                                );
                                                wp_nav_menu( $args );
                                            ?>
                                        </div>
                                    </div>
                                </nav>

                            </div> 
                        </div>  

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </header>


    
  