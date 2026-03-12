<?php 

/*
 * Template Name: Layout Login duplo
 * Description: Modelo para Login duplo no CoreSSO
 */

use App\Classes\IntegracaoCoreSSO;

!isset($_SESSION['arrCargosUe']) ? wp_redirect(site_url("/login")) : '';

if(isset($_SESSION['usuCad']->ID)){
    IntegracaoCoreSSO::removeSessoesUsuario($user->ID);
}

if(isset($_POST["opc"])){
    if($_POST["opc"] == "1"){
        IntegracaoCoreSSO::atualizaEAcesssaWP($_SESSION['arrCargosUe'][0]);
    } else if($_POST["opc"] == "2"){
        IntegracaoCoreSSO::atualizaEAcesssaWP($_SESSION['arrCargosUe'][1]);
    }
} 
