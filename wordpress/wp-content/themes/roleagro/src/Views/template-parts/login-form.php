
<div class="form-box align-items-end">
    <h2>Bem-vindo ao Rolê Agroecológico</h2>			
    <?php
        // Mensagem de erro exibida na tela
        $page_showing = basename($_SERVER['REQUEST_URI']);
        if(isset($_REQUEST['msg'])) {
            $msg = sanitize_text_field($_REQUEST['msg']);
        } 
        
        if (strpos($page_showing, 'failed') !== false) {
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" id="alerta">
                    <strong>ERRO:</strong> Usuário e/ou senha inválidos.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
        } else if (strpos($page_showing, 'blank') !== false ) {						
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" id="alerta">
                    <strong>ERRO:</strong> Usuário e/ou senha estão vazios.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
        } else if(isset($_SESSION['info-msg']) && !empty($_SESSION['info-msg'])){
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" id="alerta">'.sanitize_text_field($_SESSION['info-msg']).'
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>';

                unset($_SESSION['info-msg']);
        }

        if(isset($_SESSION['resp_redefinicao_senha']) && !empty($_SESSION['resp_redefinicao_senha'])){
            echo '<div class="alert alert-warning alert-dismissible fade show" role="alert" id="alerta">'.$_SESSION['resp_redefinicao_senha']['msg'].'
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
                $rf = $_SESSION['resp_redefinicao_senha']['rf'];
                unset($_SESSION['resp_redefinicao_senha']);
                delete_transient( 'password_reset_' . $rf );
        }

        if ( !is_user_logged_in() ) {
            // Se o usuário não estiver logado, exibe o formulário

            ?>
            <form id="loginform-custom" action="<?php echo wp_login_url(); ?>" method="post">
                <label for="user_login">Usuário</label>
                <input type="text" name="log" id="user_login" class="input" size="20" required>

                <label for="user_pass">Senha</label>
                <input type="password" name="pwd" id="user_pass" class="input" size="20" required>

                <p class="info-text">
                Na senha, digite a mesma senha do Sistema de Gestão Pedagógica (SGP) e Intranet.<br/>
                Caso esqueça sua senha e necessite redefinir, o mesmo será aplicado para os outros acessos.
                </p>
                <p><a href="<?= site_url('/recuperar-senha'); ?>" class="link-senha">Esqueceu sua senha?</a></p>

                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="Acessar">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url() ); ?>">
            </form>
            <p class="contato">
                Em caso de dúvidas, entre em contato com:<br/>
                <a href="mailto:roleagroecologico@sme.prefeitura.sp.gov.br">roleagroecologico@sme.prefeitura.sp.gov.br</a>
            </p>
            <?php
        } else {
            // Se o usuário estiver logado, exibe um link de sair e o nome do usuário
            $user_info = wp_get_current_user();
            echo "<p>Olá, " . $user_info->display_name . "! <a href='" . wp_logout_url() . "'>Sair</a></p>";
        }
        
    ?>
</div>
