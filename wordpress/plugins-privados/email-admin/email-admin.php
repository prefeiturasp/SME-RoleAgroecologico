<?php

/*
Plugin Name: Emails de Inscrição Rolê
Plugin URI: http://educacao.sme.prefeitura.sp.gov.br
Description: Envio de emails para inscricoes nos ecentos.
Version: 1.0
Author: AMcom
Author URI: https://www.amcom.com.br
*/

function post_unpublished( $new_status, $old_status, $post ) {
    if ( $new_status == 'pending' ) {
        
        //Link para editar
        $link = get_edit_post_link( $post->ID );
        $link = str_replace('&amp;' , '&', $link);
        
        if ( ! $post_type = get_post_type_object( $post->post_type ) )
        return;

        // Inscricoes - Nova Inscricao
       
        if($post_type->labels->singular_name == 'Inscrições' ){
            // Assunto do email"
            $subject = $_POST['user_inscri'] . ", sua solicitação de inscrição foi enviada!";
        } 

        if($post_type->labels->singular_name == 'Inscrições' ){
            // Corpo do email
            $message = "Prezado(a) " . $_POST['user_inscri'] . ",<br><br>";

            $message .= "Recebemos a sua inscrição para o evento <strong>" . get_the_title($post->ID) . "</strong>, com solicitação de visita a ser realizada em <strong>" . $_POST['data_hora'] . "</strong> com <strong>" . $_POST['estudantes'] . "</strong> estudantes. A solicitação já foi enviada para a área responsável e, em breve, enviaremos o retorno.<br><br>";

            $message .= "<strong>Importante: esse não é um e-mail de confirmação da Visita. Aguarde o contato de DICEU para esta confirmação.</strong><br><br>";

            $message .= "Atenciosamente,<br>";
            $message .= "Equipe Rolê Agroecológico<br><br>";

            $message .= "<img src='https://hom-roleagroecologico.sme.prefeitura.sp.gov.br/wp-content/uploads/2022/07/logo-roleagroecologico.png' alt='Logo Rolê Agroecológico'>";

            //$message = "Email: " . $_POST['user_inscri'] . "<br>";
            //$message .= 'A publicação "' . get_the_title($post->ID) . '"' . " foi adicionada ao portal.<br>Para visualizar a publicação acesse: " . get_permalink( $post->ID ) . "<br>Para publicar no portal acesse: " . $link;
            
            $emailto = $_POST['email_resp'];
            $content_type = function() { return 'text/html'; };
            add_filter( 'wp_mail_content_type', $content_type );
            wp_mail( $emailto, $subject, $message );
            remove_filter( 'wp_mail_content_type', $content_type );
        }

        // Parceiros
        if($post_type->labels->singular_name == 'Parceiros' ){
            // Assunto do email"
            $subject = "[Rolê Agroecológico] Há um novo cadastro de Parceiros";
        } 

        if($post_type->labels->singular_name == 'Parceiros' ){
            
            // Usuarios do tipo Admin
            $emailto = array();
            $adminUsers = get_users('role=Administrator'); // Uuarios do tipo admin         
            foreach ($adminUsers as $user) {
                $emailto[] = $user->user_email;
            }
            
            // Corpo do email
            $message = 'O parceiro "' . get_the_title($post->ID) . '"' . " fez sua inscrição no portal.\nPara visualizar a inscrição acesse: " . get_permalink( $post->ID ) . "\nPara publicar no portal acesse: " . $link;
            wp_mail( $emailto, $subject, $message );
            
        }
        
    }
    
}
add_action( 'transition_post_status', 'post_unpublished', 10, 3 );


add_action('acf/save_post', 'my_acf_save_post_new', 5);
function my_acf_save_post_new( $post_id ) {

    // Get previous values.
    $prev_values = get_fields( $post_id );

    // Get submitted values.
    $values = $_POST['acf'];
    
    $post_type = get_post_type( $post_id );

    $liberar_edicao = true;

    if($prev_values['status']['value'] == 'negado' && $values['field_63209d17c6acf'] == 'confirmada' ){

        $edit_post_url = get_edit_post_link($post_id);

        $evento = get_field('evento', $post_id);
		$dt_liberar = get_field('data_horario', $post_id);
		$dh_select = explode(']', (explode('[', $dt_liberar)[1]))[0];
        $valor_status = get_post_meta($evento, 'agenda_' . $dh_select . '_status', true);
        
        if($valor_status == 'Esgotado'){
            // Se o valor não estiver correto, exiba uma mensagem de erro e pare o processo de salvamento
            wp_die('<h1 style="text-align: center;">Atenção</h1> <br><strong>A inscrição não pode ter o Status alterado para Confirmada</strong> pois o dia e horário não estão mais disponíveis, será necessário fazer uma nova inscrição. <a href="' . $edit_post_url . '">Voltar para o editor da inscrição</a>.', 'Erro de Validação');
            $liberar_edicao = false;
        } else {
            update_post_meta($evento, 'agenda_' . $dh_select . '_status', 'Esgotado');
        }

    } elseif($liberar_edicao) {

        // Incricao Negada
        
        if( $post_type == 'agendamento' && $prev_values['status']['value'] != 'negado' && $values['field_63209d17c6acf'] == 'negado' ){
            // Assunto do email"
            $subject = "[Rolê Agroecológico] " . $values['field_631f2592a8d06'] . ", sua Visita não poderá ser realizada";
        } 

        if( $post_type == 'agendamento' && $prev_values['status']['value'] != 'negado' && $values['field_63209d17c6acf'] == 'negado' ){
            // Corpo do email
            $message = "Prezado(a) " . $values['field_631f2592a8d06'] . ",<br><br>";

            $message .= "Infelizmente, <strong>não conseguiremos operacionalizar a sua visita para o evento " . get_the_title($post_id) . "</strong>. Aguarde para futuras inscrições. Qualquer dúvida, entre em contato com a DICEU.<br><br>";
            
            $message .= "Atenciosamente,<br>";
            $message .= "Equipe Rolê Agroecológico<br><br>";

            $message .= "<img src='https://hom-roleagroecologico.sme.prefeitura.sp.gov.br/wp-content/uploads/2022/07/logo-roleagroecologico.png' alt='Logo Rolê Agroecológico'>";

            //$message = "Email: " . $_POST['user_inscri'] . "<br>";
            //$message .= 'A publicação "' . get_the_title($post->ID) . '"' . " foi adicionada ao portal.<br>Para visualizar a publicação acesse: " . get_permalink( $post->ID ) . "<br>Para publicar no portal acesse: " . $link;
            
            $emailto = $_POST['email_resp'];
            $content_type = function() { return 'text/html'; };
            add_filter( 'wp_mail_content_type', $content_type );
            wp_mail( $emailto, $subject, $message );
            remove_filter( 'wp_mail_content_type', $content_type );

        }

        // Atualizacao de campos
        $atualizacao = 0;
        if($prev_values['saida_onibus'] != substr($values['field_631b8f40cfaa0'], 0, 5)){
            $atualizacao++;
        }

        if($prev_values['endereco_ue'] != $values['field_631b9732b6324']){
            $atualizacao++;
        }

        if($prev_values['ponto_referencia'] != $values['field_631b9747b6325']){
            $atualizacao++;
        }

        if($prev_values['end_destino'] != $values['field_63876b7d72837']){
            $atualizacao++;
        }

        if($prev_values['retorno_ue'] != substr($values['field_631b8f73cfaa1'], 0, 5)){
            $atualizacao++;
        }

        if($prev_values['nome_da_empresa'] != $values['field_63876bb372839']){
            $atualizacao++;
        }

        if($prev_values['placa_onibus'] != $values['field_63876bd57283a']){
            $atualizacao++;
        }

        if($prev_values['nome_motorista'] != $values['field_63876bfe7283b']){
            $atualizacao++;
        }

        if($prev_values['contato_motorista'] != $values['field_63876c2b7283c']){
            $atualizacao++;
        }

        // Inscricao Confirmada
        
        if( $post_type == 'agendamento' && ( ($prev_values['status']['value'] != 'confirmada' && $values['field_63209d17c6acf'] == 'confirmada') || ($values['field_63209d17c6acf'] == 'confirmada'  &&  $atualizacao > 0) ) ){
            // Assunto do email"
            $subject = "[Rolê Agroecológico] " . $values['field_631f2592a8d06'] . ", sua Visita foi confirmada!";
        } 

        if( $post_type == 'agendamento' && ( ($prev_values['status']['value'] != 'confirmada' && $values['field_63209d17c6acf'] == 'confirmada') || ($values['field_63209d17c6acf'] == 'confirmada'  &&  $atualizacao > 0) ) ){
            // Corpo do email
            $message = "Prezado(a) " . $values['field_631f2592a8d06'] . ",<br><br>";       
            

            $message .= "A sua inscrição para o evento <strong>" . get_the_title($post_id) . "</strong>, foi confirmada! A visita vai acontecer em " . substr($values['field_631b8b3898ec4'], 0, 16) . " com " . $values['field_631f3f84a9289'] . " inscritos. Confira abaixo as informações sobre seu transporte:<br><br>";

            // Chegada Onibus
            $chegada_onibus = $values['field_631b8f40cfaa0'];
            if($chegada_onibus && $chegada_onibus != ''){
                $message .= "Horário de chegada do ônibus: " . substr($chegada_onibus, 0, 5) . "<br>";
            }

            // Endereco UE
            $endereco_ue = $values['field_631b9732b6324'];
            if($endereco_ue && $endereco_ue != ''){
                $message .= "Endereço da UE: " . $endereco_ue . "<br>";
            }

            // Ponto de referencia
            $ponto_referencia = $values['field_631b9747b6325'];
            if($ponto_referencia && $ponto_referencia != ''){
                $message .= "Ponto de referência da UE: " . $ponto_referencia . "<br>";
            }

            // Local desembarque
            $local_desembarque = $values['field_63876b7d72837'];
            if($local_desembarque && $local_desembarque != ''){
                $message .= "Local de desembarque no evento: " . $local_desembarque . "<br>";
            }

            // Retorno UE
            $hora_retorno = $values['field_631b8f73cfaa1'];
            if($hora_retorno && $hora_retorno != ''){
                $message .= "Horário de retorno à UE: " . substr($hora_retorno, 0, 5) . "<br>";            
            }

            // Empresa / Onibus
            $empresa = $values['field_63876bb372839'];
            if($empresa && $empresa != ''){
                $message .= "Nome da empresa: " . get_the_title($empresa) . "<br>";
            }

            // Placa / Onibus
            $placa = $values['field_63876bd57283a'];
            if($placa && $placa != ''){
                $message .= "Placa do ônibus: " . $placa . "<br>";
            }

            // Nome motorista / Onibus
            $nome_motorista = $values['field_63876bfe7283b'];
            if($nome_motorista && $nome_motorista != ''){
                $message .= "Nome do motorista: " . $nome_motorista . "<br>";
            }

            // Tel motorista / Onibus
            $placa = $values['field_63876c2b7283c'];
            if($placa && $placa != ''){
                $message .= "Telefone de contato do motorista: " . $placa . "<br>";
            }

            $message .= "<br>Qualquer divergência nos dados, por favor entrar em contato com DICEU.<br><br>";
            
            $message .= "Atenciosamente,<br>";
            $message .= "Equipe Rolê Agroecológico<br><br>";

            $message .= "<img src='https://hom-roleagroecologico.sme.prefeitura.sp.gov.br/wp-content/uploads/2022/07/logo-roleagroecologico.png' alt='Logo Rolê Agroecológico'>";

            //$message = "Email: " . $_POST['user_inscri'] . "<br>";
            //$message .= 'A publicação "' . get_the_title($post->ID) . '"' . " foi adicionada ao portal.<br>Para visualizar a publicação acesse: " . get_permalink( $post->ID ) . "<br>Para publicar no portal acesse: " . $link;
            
            $emailto = $values['field_631f25b2a8d08'];
            $content_type = function() { return 'text/html'; };
            add_filter( 'wp_mail_content_type', $content_type );
            wp_mail( $emailto, $subject, $message );
            remove_filter( 'wp_mail_content_type', $content_type );

        }

        // Inscricao Confirmada - Parceiro
        //if( $post_type == 'agendamento' && ( ($prev_values['status']['value'] != 'confirmada' && $values['field_63209d17c6acf'] == 'confirmada') || ($values['field_63209d17c6acf'] == 'confirmada'  &&  $atualizacao > 0) ) ){
            // Assunto do email"
            $parceiro = $values['field_63f6653def215'];
            $nome_resp = get_field('nome_responsavel_parceiro', $parceiro);

            $subject = "[Rolê Agroecológico] " . $nome_resp . ", você tem uma Visita confirmada!";
        //}

        //if( $post_type == 'agendamento' && ( ($prev_values['status']['value'] != 'confirmada' && $values['field_63209d17c6acf'] == 'confirmada') || ($values['field_63209d17c6acf'] == 'confirmada'  &&  $atualizacao > 0) ) ){
            // Corpo do email
            $message = "Prezado(a) " . $nome_resp . ",<br><br>"; 
            
            $message .= "Você tem uma nova Visita Monitorada confirmada para o evento <strong>" . get_the_title($post_id) . "</strong>. A visita vai acontecer em " . substr($values['field_631b8b3898ec4'], 0, 16) . " com " . $values['field_631f3f84a9289'] . " inscritos. Confira mais informações abaixo:<br><br>";       

            $transporte = $values['field_631b8e0d5a10c']; // Precisa de Transporte
            $tipo_transporte = $values['field_6356d16b4c6d2']; // Tipo de Transporte

            if($transporte){
                if($tipo_transporte == 'parceiro'){
                    $tipo_transporte = "Parceiro";
                } elseif($tipo_transporte == 'dre'){
                    $tipo_transporte = "DRE";
                }
                $message .= "Tipo de Transporte: " . $tipo_transporte . " <br>";
            } else {
                $message .= "Tipo de Transporte: Próprio UE<br>";
            }
        

            // Chegada Onibus
            $chegada_onibus = $values['field_631b8f40cfaa0'];
            if($chegada_onibus && $chegada_onibus != ''){
                $message .= "Horário de chegada do ônibus: " . substr($chegada_onibus, 0, 5) . "<br>";
            }

            if($transporte && $tipo_transporte == 'Parceiro'){
                // Endereco UE
                $endereco_ue = $values['field_631b9732b6324'];
                if($endereco_ue && $endereco_ue != ''){
                    $message .= "Endereço da UE: " . $endereco_ue . "<br>";
                }

                // Ponto de referencia
                $ponto_referencia = $values['field_631b9747b6325'];
                if($ponto_referencia && $ponto_referencia != ''){
                    $message .= "Ponto de referência da UE: " . $ponto_referencia . "<br>";
                }

                // Local desembarque
                $local_desembarque = $values['field_63876b7d72837'];
                if($local_desembarque && $local_desembarque != ''){
                    $message .= "Local de desembarque no evento: " . $local_desembarque . "<br>";
                }

                // Retorno UE
                $hora_retorno = $values['field_631b8f73cfaa1'];
                if($hora_retorno && $hora_retorno != ''){
                    $message .= "Horário de retorno à UE: " . substr($hora_retorno, 0, 5) . "<br>";            
                }
            }

            $message .= "<br>Qualquer dúvida, entre em contato com COCEU pelo e-mail smecoceu@sme.prefeitura.sp.gov.br.<br><br>";
            
            $message .= "Atenciosamente,<br>";
            $message .= "Equipe Rolê Agroecológico<br><br>";

            $message .= "<img src='https://hom-roleagroecologico.sme.prefeitura.sp.gov.br/wp-content/uploads/2022/07/logo-roleagroecologico.png' alt='Logo Rolê Agroecológico'>";
            
            $emailto = get_field('email_responsavel_parceiro', $parceiro); // Email do responsavel
            $content_type = function() { return 'text/html'; };
            add_filter( 'wp_mail_content_type', $content_type );
            wp_mail( $emailto, $subject, $message );
            remove_filter( 'wp_mail_content_type', $content_type );

        //}

    }
}