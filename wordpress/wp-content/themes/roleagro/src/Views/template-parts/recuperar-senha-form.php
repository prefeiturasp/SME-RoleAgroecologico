<?php 
$exibeMsg = false;

use EnviaEmail\classes\Envia_Emails;

if(isset($_POST['rf']) && strlen($_POST['rf']) == 7){
    $exibeMsg = true;

    // Verifica se o usuario ja esta cadastrado no WordPress
    $userobj = new \WP_User();
    $user_wp = $userobj->get_data_by( 'login', $_POST['rf'] );
    $nome = $user_wp->display_name;
    $rf = $user_wp->user_login;
    $email = $user_wp->user_email;

    if(is_object($user_wp) && isset($email)){
        
        $rf_encrypt = base64_encode($rf);
        $horas_validade = DAY_IN_SECONDS/4; //6 horas
      	
        set_transient( 'password_reset_'.$rf, $rf_encrypt, $horas_validade );
      
        $exibeMsg = Envia_Emails::redefine_senha($nome, $rf, $email);
    }
}

?>
<div class="form-box-recupera-senha align-items-end">
<?php if(!$exibeMsg): ?>
    <div id="conteudo-recupera">
        <h2>Esqueceu sua senha?</h2><hr>			
            <p class="txt-recupera-senha">
                Fique atento! A mudança de senha aqui, também acarretará 
                automaticamente na mudança de senha do SGP, intranet, SIGPAE, 
                Plateia e outros Sistemas. Caso a senha do SGP esteja salva em 
                seus dispositivos, lembre-se de usar a nova senha em seus próximos
                acessos.
            </p>
            <p class="txt-recupera-senha">
                As orientações para redefinição da sua senha serão enviadas para o seu e-mail.
            </p>
            
            <form action="#" method="post">
                <label for="rf-recupera">Usuário</label>
                <input type="text" name="rf" id="rf-recupera" class="input" maxlength="7" placeholder="Informe seu RF" pattern="\d*" required>
                <p>&nbsp;</p>
                <div class="container">
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
    <?php else :?>
    <div id="info-recupera">
            <br><br>
            <center>
                <img src="<?= URL_IMG_THEME.'/icons/check.png'; ?>" width="100" class="img-success">
                <h2 class="swal2-title center" id="swal2-title">E-mail enviado com sucesso!</h2>
                <p class="txt-success">Confira se recebeu o e-mail para gerar uma nova senha de<br> acesso em sua caixa de entrada ou spam.</p>
                <a href="<?= site_url('/login') ?>" id="btn-voltar-rec-senha" class="btn btn-lg button">Voltar ao Login</a>
            </center>
        
    </div>
    <?php endif; ?>
</div>
