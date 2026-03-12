<?php

if (is_user_logged_in()){

    if ( ! session_id() ) {
       session_start();
    }

    $dados_roteiro_disp_local = '';
    global $dados_roteiro_disp_local;

    add_shortcode('btn_aplicar_filtro_roteiros', 'addButtonAplicarFiltros');
    add_shortcode('conteudo_admin_roteiro', 'conteudoRoteiro');
    add_shortcode('conteudo_admin_disponibilidade_locais', 'conteudoDisponibilidadeLocais');

    add_filter('manage_edit-post_roteiro_columns', 'add_novas_colunas_roteiro');
    add_action('manage_post_roteiro_posts_custom_column', 'conteudo_novas_colunas', 10, 2);

    add_action('wp_ajax_filtrar_roteiros', 'processa_ajax_filtrar_roteiros');
    add_action('wp_ajax_roteiros_selecionado', 'processa_ajax_roteiros_selecionado');

    add_action( 'save_post', 'salva_post_roteiros' );
    
}

function addButtonAplicarFiltros() { ?>

    <div class="acf-actions">
        <a class="acf-button button" id="aplicar-filtro-roteiros" data-event="add-row">Aplicar Filtros</a>
        <div class="clear"></div>
        <input type="hidden" value="<?= get_the_ID();?>" id="postID">
    </div>

<?php }


function conteudoRoteiro() { 

    echo '<div class="conteudo-listagem-roteiros">
            <p id="msg-selecao-roteiro"></p>
                <table class="table table-sm table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col"></th>
                            <th scope="col">Tipo</th>
                            <th scope="col">Nome do Local</th>
                            <th scope="col">Região</th>
                            <th scope="col">Distrito</th>
                            <th scope="col">Dias e Períodos Disponíveis</th>
                            <th scope="col">Acessível</th>
                            <th scope="col">Apto p/ Almoço</th>
                        </tr>
                    </thead>
                    <tbody id="conteudo-tab-roteiros">
                        <tr>
                            <td class="text-center" colspan="8">Aguardando filtros...</td>
                        </tr>
                    </tbody>
                </table>
        </div>';
}

function conteudoDisponibilidadeLocais(){ 
    
    $ids_up = get_post_meta(get_the_ID(), 'ids_up_roteiro', true);
    $conteudoAba1 = '';
    $conteudoAba2 = '';
    if(isset($ids_up) && !empty($ids_up)){
        $strNomesUp = [];
        for($i=0; $i < count($ids_up); $i++){
            $arrPeriodo = '';
            $post = get_post($ids_up[$i]);
            $campos = get_field('dias_disponibilidade_visitas', $ids_up[$i]);
            foreach($campos as $campo){
                $diaSemana = $campo["dia_semana"]->name;
                $periodo = $campo["periodo"]->name;
                $arrPeriodo .= $diaSemana.' - '.$periodo.', ';
            }
            $arrPeriodo = substr($arrPeriodo,0,-2);
            array_push($strNomesUp, '<p class="nome-up-disp-locais"><a href="#">'.$post->post_title.'</a></p><p class="periodo-up-disp-locais">'.$arrPeriodo.'</p>');
        }

        if(count($strNomesUp) == 1) {
            $conteudoAba1 = $strNomesUp[0]; 
            $conteudoAba2 = '';
        } else if (count($strNomesUp) == 2){
            $conteudoAba1 = $strNomesUp[0]; 
            $conteudoAba2 = $strNomesUp[1];
        }
    } 
    
    ?>

    <div class="container">
        <div class="row">
            <div class="col-sm border p-3 mb-2" id="tab-conteudo-unico"><?php echo $conteudoAba1; ?></div>
            <div class="col-sm border p-3 mb-2" id="tab-conteudo-combo"><?php echo $conteudoAba2; ?></div>
        </div>
    </div>

<?php }

function processa_ajax_filtrar_roteiros(){

    global $wpdb;

    $acao = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
    if ($acao == 'filtrar_roteiros'){

        // $idPost = absint($_POST['postId']);
        $dados = $_POST['dados'];

        $query_args = array(
            'post_type' => 'post_up',
            'post_status' => 'publish',
            'orderby' => 'ID',
            'posts_per_page' => '100'
        );

        $args_meta["meta_query"] = array(
            'relation' => 'AND'
        );

        // VERIFICAÇÃO DOS TIPOS DE LOCAIS (Unidades Produtivas e Parques)
        if(isset($dados["tipo_local"]) && !empty($dados["tipo_local"])){
            $qtdLocal = count($dados["tipo_local"]);
            if($qtdLocal > 1){
                $valorLocal = array('unidade', 'parque');
            } else {
                switch($dados["tipo_local"][0]){
                    case 'up': 
                       $valorLocal = array('unidade');
                    break;
                    case 'pa':
                        $valorLocal = array('parque');
                    break;
                }
            }
        } else {
            $valorLocal = array('unidade', 'parque');
        }

        array_push($args_meta["meta_query"], array(
            'key' => 'tipo_cadastro',
            'value' => $valorLocal,
            'compare' => 'IN',
        ));

        // VERIFICAÇÃO DA ACESSIBILIDADE
        if(isset($dados["acessibilidade"]) ){
            array_push($args_meta["meta_query"], array(
                'key' => 'possui_acessibilidade',
                'value' => $dados["acessibilidade"],
                'compare' => '=',
            ));
        }
        // VERIFICAÇÃO DE OFERTA DE ALMOÇO
        if(isset($dados["almoco"]) ){
            array_push($args_meta["meta_query"], array(
                'key' => 'habilitado_almoço',
                'value' => $dados["almoco"],
                'compare' => '=',
            ));
        }

       $query_args = array_merge($query_args, $args_meta);

        $args_tax["tax_query"] = array(
            'relation' => 'AND'
        );
     
        // VERIFICAÇÃO DOS PERÍODOS(Integral, Manhã e Tarde)
        if(isset($dados["periodo"]) && count($dados["periodo"]) > 0){
            array_push($args_tax["tax_query"], array(
                'taxonomy' => 'tax_up_periodos-de-oferta', 
                'field'    => 'term_id', 
                'terms'    => $dados["periodo"], 
                'operator' => 'IN' 
            ));
        }

        // VERIFICAÇÃO DA REGIÃO (Zonas-sul, zona-norte, etc)
        if(isset($dados["regiao"]) && !empty($dados["regiao"])){
            array_push($args_tax["tax_query"], array(
                'taxonomy' => 'tax_up_regioes', 
                'field'    => 'term_id', 
                'terms'    => array($dados["regiao"]), 
                'operator' => 'IN' 
            ));
        }

        // VERIFICAÇÃO DOS DIAS DA SEMANA (Segunda-fera, Terça-feira, etc)
        if(isset($dados["dias_semana"]) && count($dados["dias_semana"]) > 0){
            array_push($args_tax["tax_query"], array(
                'taxonomy' => 'tax_up_disponibilidade-semana', 
                'field'    => 'term_id', 
                'terms'    => $dados["dias_semana"], 
                'operator' => 'IN'
            ));
        }

        count($args_tax["tax_query"]) > 1 ? $args = array_merge($query_args, $args_tax) : $args = $query_args;

        // QUERY
        $minhaQuery = new \WP_Query($args);

        $minhaQuery->have_posts() ? $arrPosts = $minhaQuery->posts : $arrPosts = [];
        wp_reset_postdata();

        $roteiros = [];

        foreach($arrPosts as $item){
            $idPost = $item->ID;
            // Tipo de UP
            $pmLocal = get_post_meta($idPost , 'tipo_cadastro', true);
            !empty($pmLocal) && $pmLocal == 'parque' ? $tipoLocal = ucfirst($pmLocal) : $tipoLocal = 'Unidade Produtiva';
            
            // Nome da UP
            $nomeUnidade = $item->post_title;

            // Região da UP
            $pmRegiaoID = get_post_meta($idPost , 'regiao', true);
            !empty($pmRegiaoID) ? $regiao = get_term_by('id', $pmRegiaoID, 'tax_up_regioes')->name : $regiao = '';

            // Distrito da UP
            $pmDistritoID = get_post_meta($idPost , 'distrito', true);
            !empty($pmDistritoID) ? $distrito = get_term_by('id', $pmDistritoID, 'tax_up_distritos')->name : $distrito = '';

            // Dias e Períodos da UP
            $strDiasPeriodos = '';
            $campos = get_field('dias_disponibilidade_visitas', $item->ID);
            $arrPeriodo = '';
            foreach($campos as $campo){
                $diaSemana = $campo["dia_semana"]->name;
                $periodo = $campo["periodo"]->name;
                if(isset($periodo) && isset($diaSemana)){
                    $arrPeriodo .= $diaSemana.' - '.$periodo.', ';
                }
            }
            // Remove o espaço e a virgula no fim da string
            $strDiasPeriodos = substr($arrPeriodo,0,-2);


            // Acessibilidade na UP
            $pmAcessibilidade = get_post_meta($idPost , 'possui_acessibilidade', true);
            !$pmAcessibilidade ? $acessibilidade = 'Não' : $acessibilidade = 'Sim';

            // Apto para Almoço na UP
            $pmAptAlmoco = get_post_meta($idPost , 'habilitado_almoço', true);
            !$pmAptAlmoco ? $almoco = 'Não' : $almoco = 'Sim';
            

            array_push($roteiros, array(
                "id" => $idPost,
                "tipo" => $tipoLocal,
                "nome" => $nomeUnidade,
                "regiao" => $regiao ? $regiao : '',
                "distrito" => $distrito ? $distrito : '',
                "dp" => $strDiasPeriodos ? $strDiasPeriodos : '',
                "acessibilidade" => $acessibilidade,
                "almoco" => $almoco
            ));
        }

        // GUARDA RESULTADO NA SESSÃO
        $_SESSION['roteiros_filtrados'] = $roteiros;

        wp_send_json(array(
			"res" => true,
            "dados" => $roteiros
		));
        
    }
    wp_die();
}


function add_novas_colunas_roteiro($columns) {
  $new_columns = array(
    'cb' => '<input type="checkbox" />', // Checkbox para seleção
    'title' => __('Roteiro'),             // Coluna do título
    'up' => __('Unidade Produtiva / Parque'), // Sua nova coluna
    'tag' => __('Tags'),            // Coluna do autor
    // 'author' => __('Author'),
    'date' => __('Data')               // Coluna da data
  );
  return $new_columns;
}

//Aqui você insere o código para exibir o conteúdo da sua coluna.
function conteudo_novas_colunas($column, $post_id) {
  switch ($column) {
    case 'up':
        if(get_post_meta($post_id, 'ids_up_roteiro', true)){
            $ids_up = get_post_meta($post_id, 'ids_up_roteiro', true);
            $strNomesUp = '';
            for($i=0; $i < count($ids_up); $i++){
                $post = get_post($ids_up[$i]);
                $strNomesUp .= $post->post_title.', ';
            }
            $strNomesUp = substr($strNomesUp, 0, -2);
            echo $strNomesUp;
        } else {
            echo '';
        }
    break;
    case 'tag':
        $strTags = '';
        $mv_tipo_roteiro = get_post_meta($post_id, 'tipo_de_roteiro', true);
        isset($mv_tipo_roteiro) && $mv_tipo_roteiro == 'combo' ? $strTags .= 'Combo, ' : '';
        $mv_acessibilidade = get_post_meta($post_id, '_roteiro_com_acessibilidade', true);
        isset($mv_acessibilidade) && $mv_acessibilidade == '1' ? $strTags .= 'Com acessibilidade, ' : '';
        $mv_almoco = get_post_meta($post_id, '_roteiro_com_oferta_de_almoco', true);
        isset($mv_almoco) && $mv_almoco == '1' ? $strTags .= 'Com almoço, ' : '';
        $mv_regiao = get_post_meta($post_id, '_regiao_tag_roteiro', true);
        isset($mv_regiao) ? $strTags .= $mv_regiao : '';
        echo $strTags;
    break;
  }
}

add_action( 'wp_ajax_nopriv_roteiros_selecionado', 'processa_ajax_roteiros_selecionado' );
function processa_ajax_roteiros_selecionado(){
   
    $acao = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';

    if ($acao == 'guarda_roteiros'){
        wp_send_json(array(
            "res"=>"Guardou Ids"
        ));
    }   
}

function salva_post_roteiros($post_id){
    //  Verifica se o post é do tipo (ex: post_roteiro )
    if ( get_post_type( $post_id ) != 'post_roteiro' ) {
        return;
    }
    //  Verifica se a ação é um salvamento automático ou atualização.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    //  Verifica se o usuário tem permissão para editar o post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( $id_unidades_post = get_post_meta( $post_id, 'ids_up_roteiro', true ) ) {
        salvar_dados_adicionais_roteiro( $post_id, $id_unidades_post );
    }

    if(get_transient('ids_temp_selecao_roteiros_'.$post_id)){

        $id_unidades = get_transient('ids_temp_selecao_roteiros_'.$post_id);

        salvar_dados_adicionais_roteiro( $post_id, $id_unidades );  
        update_post_meta( $post_id, 'ids_up_roteiro', $id_unidades );
        delete_transient('ids_temp_selecao_roteiros_'.$post_id);
    }

}

function salvar_dados_adicionais_roteiro(int $roteiro_id, array $id_unidades ) {

    if ( empty( $id_unidades ) || !is_array( $id_unidades ) ) {
        return;
    }

    $particularidades = [];
    $atrativos = [];
    foreach ($id_unidades as $id) {

        $unidade_particularidades = wp_list_pluck( get_the_terms( $id, 'tax_up_aspectos-do-local' ), 'term_id' );
        $unidade_atrativos = wp_list_pluck( get_the_terms( $id, 'tax_up_atrativos' ), 'term_id' );

        $particularidades = array_unique( array_merge( $unidade_particularidades, $particularidades ) );
        $atrativos = array_unique( array_merge( $unidade_atrativos, $atrativos ) );
    }

    update_post_meta( $roteiro_id, 'roteiro_particularidades', $particularidades );
    update_post_meta( $roteiro_id, 'roteiro_atrativos', $atrativos );

}

