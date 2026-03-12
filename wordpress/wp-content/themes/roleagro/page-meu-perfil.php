<?php

/** Template Name: Meu Perfil */

if ( !is_user_logged_in() ) {
    // header('Location: '.site_url('/login'));
    wp_redirect( site_url('/login?redirect_to=%2Fmeu-perfil') );
    exit;
} else {
    get_header();
    require_once get_template_directory(). '/config/perfil.php';

    $telefone = strlen($dados_ue->telefone) <= 8 ? '11'.$dados_ue->telefone : $dados_ue->telefone;

?>
    <article>
        <div class="container">
            <div class="row titulo-page-sobre">
                <div class="col-sm text-center">
                    <?php
                    if ( get_field( 'tipo_titulo_pagina' ) === 'composto' ) :
                        $titulo_pagina = get_field( 'titulo_composto' );
                        ?>
                        <font size="6"><?php echo esc_html( $titulo_pagina['parte_1'] ); ?></font>
                        <h1 class="font-role-2 m-0"><?php echo esc_html( $titulo_pagina['parte_2'] ); ?></h1>
                        <?php
                    else :
                        $titulo_pagina = get_field( 'titulo_simples' ) ?: get_the_title();
                        ?>
                        <h1 class="font-role-ve m-0"><?php echo esc_html( strtoupper($titulo_pagina) ); ?></h1>
                        <?php
                    endif;
                    ?>
                </div>
            </div>

            <div class="row">
                <div class="col">&nbsp;</div>
            </div> 

            <?php if($msg && !empty($msg)):?>

                <div class="alert alert-<?= $tp; ?> alert-dismissible fade show" role="alert">
                    <?= $msg; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

            <?php endif; ?>

            <form action="<?= site_url('/meu-perfil'); ?>" method="post">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="nomePerfil"><strong>Nome <span class="text-danger">*</span></strong></label>
                        <input type="text" class="form-control form-control-lg" name="nomePerfil" id="nomePerfil" value="<?= $user_info->display_name; ?>" placeholder=" Informe seu nome" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="telefoneContato"><strong>Telefone para Contato</strong></label>
                        <input type="tel" class="form-control form-control-lg" name="telefoneContato" id="telefoneContato" value="<?= $telefoneContato; ?>" maxlength="15" placeholder="(11) 9999-9999">
                    </div>    
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="emailPerfil"><strong>E-mail do Servidor <span class="text-danger">*</span></strong></label>
                        <input type="email" class="form-control form-control-lg" name="emailPerfil" id="emailPerfil" placeholder="seuemail@sme.prefeitura.sp.gov.br" value="<?= $user_info->user_email; ?>">
                        <span id="emailHelpServ" class="form-text text-muted"><i class="fa fa-info-circle" aria-hidden="true"></i> Verifique se seu e-mail está preenchido corretamente. Caso não esteja, digite atentamente o e-mail correto e clique em salvar alterações.</span>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cargoPerfil"><strong>Cargo <span class="text-danger">*</span></strong></label>
                        <input type="text" class="form-control form-control-lg" name="cargoPerfil" id="cargoPerfil" value="<?= $cargo['nomeCargo']; ?>" placeholder="Informe seu cargo" readonly>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="rfPerfil"><strong>RF/Usuário</strong></label>
                        <input type="text" class="form-control form-control-lg" id="rfPerfil" value="<?= $user_info->user_login; ?>" readonly>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="drePerfil"><strong>DRE</strong></label>
                        <input type="text" class="form-control form-control-lg" id="drePerfil" value="<?= $dados_ue->siglaDRE; ?>" readonly>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="uePerfil"><strong>Nome da Unidade Educacional</strong></label>
                        <input type="text" class="form-control form-control-lg" id="uePerfil" value="<?= $unidade_locacao['nomeUnidade'];?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="telefoneUEPerfil"><strong>Telefone da Unidade Educacional <span class="text-danger">*</span></strong></label>
                        <input type="tel" class="form-control form-control-lg" name="telefoneUEPerfil" id="telefoneUEPerfil" value="<?= $telefone; ?>" maxlength="15" placeholder="(11) 9999-9999" required>
                        <span id="telefoneHelpUe" class="form-text text-muted"><i class="fa fa-info-circle" aria-hidden="true"></i> Verifique se o telefone está preenchido corretamente. Caso não esteja, digite o telefone de contato correto, ele é utilizado para confirmações de agendamento.</span>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="emailUEPerfil"><strong>E-mail da Unidade Educacional <span class="text-danger">*</span></strong></label>
                        <input type="email" class="form-control form-control-lg" name="emailUEPerfil" id="emailUEPerfil" value="<?= $dados_ue->email; ?>" placeholder="emailue@sme.prefeitura.sp.gov.br" required>
                        <span id="emailHelpUe" class="form-text text-muted"><i class="fa fa-info-circle" aria-hidden="true"></i> Verifique se seu e-mail está preenchido corretamente. Caso não esteja, digite o e-mail correto, ele é utilizado para confirmações de agendamento.</span>
                    </div>
                </div>

                <div class="form-group">&nbsp;</div>
                
                <div class="row justify-content-end">
                    <div class="col-2">
                        <button type="submit" id="btn-salvar-info-perfil" class="btn btn-success">Salvar Alterações</button>
                    </div>
                </div>
            </form>
          
            <div class="row">
                <div class="col">&nbsp;</div>
            </div> 

            <div class="row">
                <div class="col">&nbsp;</div>
            </div> 
        </div>
    </article>

<?php get_footer('simples'); ?>

<?php }