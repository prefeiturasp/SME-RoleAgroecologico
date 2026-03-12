<?php
// Remove a mascara do CPF antes de Salvar no BD
add_action('user_profile_update_errors', 'remover_mascara_cpf_login', 10, 3);
function remover_mascara_cpf_login($errors, $update, $user) {
    // Apenas no cadastro (não na edição)
    if (!$update && isset($_POST['user_login'])) {
        if ($_POST['tipo_login'] === 'cpfEntPar' || $_POST['tipo_login'] === 'cpfEmePfom'){
            // Captura o valor original do login
            $login_original = $_POST['user_login'];
            // Remove tudo que não for número
            $login_limpo = preg_replace('/\D/', '', $login_original);
            // Substitui o valor no objeto do usuário antes de salvar
            $user->user_login = $login_limpo;
            // (Opcional) Validação simples de tamanho
            if (strlen($login_limpo) !== 11) {
                $errors->add('cpf_invalido', '<strong>Erro:</strong> O CPF deve conter 11 dígitos.');
            }
        }

        if ($_POST['tipo_login'] === 'cpfEmePfom'){
            if(!$_POST['eol_usuario'] || !$_POST['ue_usuario'] || !$_POST['dre_usuario']){
                $errors->add('eol_necessario', '<strong>Erro:</strong> Os dados da Unidade Educacional devem ser preenchidos.');
            }
        } else {
            unset($_POST['eol_usuario']);
            unset($_POST['ue_usuario']);
            unset($_POST['dre_usuario']);
        }
    }
}

// Adicionar o hook
add_action('wp_insert_user', 'interceptar_formulario');

// Adiciona campos no formulário de criação/edição de usuário
add_action('user_new_form', 'custom_user_phone_field_dynamic');
add_action('show_user_profile', 'custom_user_phone_field_dynamic');
add_action('edit_user_profile', 'custom_user_phone_field_dynamic');

function custom_user_phone_field_dynamic($user) {
     
    wp_enqueue_script('admin-main');

    $telefone = '';
    $tipoLogin = '';
    if (is_object($user)) {
        $telefone = get_user_meta($user->ID, 'telefone_usuario', true);
        $tipoLogin = get_user_meta($user->ID, 'tipo_login', true);
    }
    echo '<input type="hidden" name="telUsu" id="telUsu" value="'.$telefone.'">';
    echo '<input type="hidden" name="tipLog" id="tipLog" value="'.$tipoLogin.'">';
}

// Salva os campos adicionais, após o usuário ser salvo
add_action('user_register', 'salva_campos_customizados');
add_action('profile_update', 'salva_campos_customizados');

function salva_campos_customizados($user_id) {
    // Obtenha os dados do usuário, por exemplo, através do $_POST
    if (isset($_POST['tipo_login'])) {
        update_user_meta($user_id, 'tipo_login', sanitize_text_field($_POST['tipo_login']));
    }
    if (isset($_POST['telefone_usuario'])) {
        update_user_meta($user_id, 'telefone_usuario', sanitize_text_field($_POST['telefone_usuario']));
    }
    // DADOS DA UNIDADE EDUCACIONAL
    if (isset($_POST['eol_usuario'])) {
        update_user_meta($user_id, 'cod_eol', sanitize_text_field($_POST['eol_usuario']));
    }
    if (isset($_POST['ue_usuario'])) {
        update_user_meta($user_id, 'nome_da_unidade_educacional', sanitize_text_field($_POST['ue_usuario']));
    }
    if (isset($_POST['dre_usuario'])) {
        update_user_meta($user_id, 'dre', sanitize_text_field($_POST['dre_usuario']));
    }

    if(isset($_POST['eol_usuario']) && isset($_POST['ue_usuario']) && isset($_POST['dre_usuario'])){

        $arrCargo = array("codCargo"=> "3360","nomeCargo"=> "DIRETOR DE ESCOLA");
        update_user_meta($user_id, 'cargo', $arrCargo);

        $unidade_locacao = array("codUnidade"=>$_POST['eol_usuario'], "nomeUnidade"=>$_POST['ue_usuario']);
        update_user_meta($user_id, 'unidade_locacao', $unidade_locacao);
        
        $dados_ue = get_transient('dados_eol_ue_emef_pfom_'.$_POST['eol_usuario']);
        if (isset($dados_ue)){
            update_user_meta($user_id, 'dados_ue', $dados_ue);
        }
    }
}

function retiraMascaraCpf($cpf){
    $cpf1 = explode('-', $cpf);
    $cpf2 = explode('.', $cpf1[0]);
    $cpfSemMascara = $cpf2[0].$cpf2[1].$cpf2[2].$cpf1[1];
    return $cpfSemMascara;
}
