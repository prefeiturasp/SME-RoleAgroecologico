<?php

use App\Classes\IntegracaoCoreSSO;

$uri = get_template_directory_uri();

$user_info = wp_get_current_user();
$unidade_locacao = get_user_meta( $user_info->ID, 'unidade_locacao', true );
$dados_ue = get_user_meta( $user_info->ID, 'dados_ue', true );
$cargo = get_user_meta( $user_info->ID, 'cargo', true );
$telefoneContato = get_user_meta( $user_info->ID, 'telefone_usuario', true );

if(!$dados_ue){
    $instancia = new IntegracaoCoreSSO();
    $dados_ue = $instancia->buscaDadosUnidadeEducacional( $unidade_locacao['codUnidade'] );
}

$msg = '';
$tp = '';

if(isset($_POST['telefoneUEPerfil']) && strlen($_POST['telefoneUEPerfil']) < 14){
    $tp = 'warning';
    $msg = '<strong>Atenção!</strong> Verifique se o número de telefone da UE está informado corretamente.';
} else {
    
    if(isset($_POST['nomePerfil']) && isset($_POST['emailPerfil']) && isset($_POST['cargoPerfil']) && isset($_POST['telefoneUEPerfil']) && isset($_POST['emailUEPerfil'])){

        // Chamada da função para atualizar o usuário
        $usu = wp_update_user(array(
            'ID' => get_current_user_id(),
            'user_email' => sanitize_email($_POST['emailPerfil']),
            'display_name' => sanitize_text_field($_POST['nomePerfil'])
        ));

        // Verificação do resultado da função
        if (is_wp_error($usu)) {
            $tp = 'warning';
            $msg = '<strong>Atenção!</strong> '. $usu->get_error_message();
        } else {
            
            $cargo['nomeCargo'] = sanitize_text_field($_POST['cargoPerfil']);

            update_user_meta( get_current_user_id(), 'cargo', $cargo );

            $dados_ue->telefone = $_POST['telefoneUEPerfil'];
            $dados_ue->email = sanitize_email($_POST['emailUEPerfil']);
            $telefoneContato = $_POST['telefoneContato'];
            
            update_user_meta( get_current_user_id(), 'dados_ue', $dados_ue );
            update_user_meta( get_current_user_id(), 'telefone_usuario', $telefoneContato );

            $tp = 'success';
            $msg = '<strong>Sucesso!</strong> Informações do usuário atualizadas com sucesso!';

            unset($_POST);
        }
    }
}
