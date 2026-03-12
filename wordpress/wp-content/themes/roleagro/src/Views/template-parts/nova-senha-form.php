<?php 

if($_SESSION['resp_redefinicao_senha']){
    $divAlert = '<div class="alert alert-warning" role="alert">'.$_SESSION['resp_redefinicao_senha']['msg'].'</div>';
    unset($_SESSION['resp_redefinicao_senha']);
}

//Valida Link
$link_check = false;
$rf = '';

$dados_link = $_REQUEST['rp'];

if(!$dados_link){
    echo "<script>                    
            window.location.replace('".site_url('/login')."');
        </script>";
    exit;
} else {

    $dados = base64_decode($dados_link);
    $arrDados = explode("-",$dados);
    $rf = $arrDados[1];

    $link_check = get_transient( 'password_reset_' . $rf );
}


// if (!$link_check){
//     delete_transient( 'password_reset_' . $rf );
//     echo "<script>                    
//             window.location.replace('".site_url('/login')."');
//         </script>";
//     exit;
// }

?>

<div class="form-box-recupera-senha align-items-end">
    <?php if ($link_check): ?>
    <div id="conteudo-recupera">

        <?php 
            if($divAlert){
                echo $divAlert;
            } 
        ?>

        <h2>Nova senha?</h2><hr>			
        <p class="txt-nova-senha">
            Fique atento! A mudança de senha aqui, também acarretará 
            automaticamente na mudança de senha do SGP, intranet, SIGPAE, 
            Plateia e outros Sistemas. Caso a senha do SGP esteja salva em 
            seus dispositivos, lembre-se de usar a nova senha em seus próximos
            acessos.
        </p>
        
        <form action="<?= site_url('/processa-nova-senha/'); ?>" method="post" id="form-nova-senha">
            <input type="hidden" name="loginRF" id="loginRF" value="<?= $rf ?>">
            <label for="rf-recupera">Informe a nova senha</label>
            <input type="text" name="nv-senha1" id="nv-senha1" class="input" placeholder="Informe a nova senha" maxlength="12" required>
            <p>&nbsp;</p>
            <label for="rf-recupera">Repita a nova senha</label>
            <input type="text" name="nv-senha2" id="nv-senha2" class="input" placeholder="Informe a nova senha" maxlength="12" required>
            <p>&nbsp;</p>
            <div class="container">
                <div id="itens-validacao" class="row">
                    <div class="col-6">
                        <div id="sp-qtd" class="alert alert-danger alert-validacao" role="alert">
                            Entre 8 e 12 caracteres
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="sp-maius" class="alert alert-danger alert-validacao" role="alert">
                            1 letra maiúscula
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="sp-minus" class="alert alert-danger alert-validacao" role="alert">
                            1 letra minúscula
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="sp-num" class="alert alert-danger alert-validacao" role="alert">
                            1 número
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="sp-acento" class="alert alert-danger alert-validacao" role="alert">
                            Sem acento
                        </div>
                    </div>
                    <div class="col-6">
                        <div id="sp-char" class="alert alert-danger alert-validacao" role="alert">
                            1 caractere ($*&@#)
                        </div>
                    </div> 
                </div>
                <div class="row">
                    <div class="col-4">&nbsp;</div>
                    <div class="col-4 alinhado-direita">
                        <input type="buttom" id="btn-cancela-rec-senha" class="btn btn-sm btn-block button" value="Cancelar">
                    </div>
                    <div class="col-4 alinhado-direita">
                        <input type="submit" id="btn-continua-rec-senha" class="btn btn-sm button" value="Continuar">
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php else : ?>
    <div id="info-recupera">
        <br><br>
        <center>
            <img src="<?= URL_IMG_THEME.'/icons/alerta.png'; ?>" width="100" class="img-success">
            <h2 class="swal2-title center" id="swal2-title">Este link expirou!</h2>
            <p class="txt-success">Clique em continuar para solicitar <br>um novo link de recuperação de senha.</p>
            <a href="<?= site_url('/recuperar-senha/') ?>" id="btn-voltar-rec-senha" class="btn btn-lg button">Continuar</a>
        </center>
        
    </div>
    <?php endif; ?>
</div>
