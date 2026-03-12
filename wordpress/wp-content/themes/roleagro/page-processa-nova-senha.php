<?php
/*
 * Template Name: Layout processa nova senha
 * Description: Modelo para redefinicao de senha
 */

use App\Classes\IntegracaoCoreSSO;

if(!isset($_POST["nv-senha1"])) {
    $link = site_url('/login');
    echo "<script>                    
            window.location.replace('".$link."');
        </script>"; 
} else {

    $senha = $_POST["nv-senha1"];
    $rf = $_POST["loginRF"];

    $str_hash = 'Role-'.$rf.'-agroecologico';
    $rf_encrypt = base64_encode($str_hash);
 
    $resp = IntegracaoCoreSSO::redefineSenhaCoreSSO($rf, $senha);

    if($resp['resp'] === 'A nova senha não pode ser uma das ultimas 5 anteriores'){
        $link = site_url("/nova-senha/?rp=".$rf_encrypt);
        $_SESSION['resp_redefinicao_senha'] = array("msg"=>"A nova senha não pode ser uma das ultimas 5 anteriores","rf"=>$rf);
        echo "<script>window.location.replace('".$link."');</script>";
    } else {
        $link = site_url('/login');
        $_SESSION['resp_redefinicao_senha'] = array("msg"=>"Senha alterada com sucesso!","rf"=>$rf);
        echo "<script>window.location.replace('".$link."');</script>";
    } 
    exit;
}
