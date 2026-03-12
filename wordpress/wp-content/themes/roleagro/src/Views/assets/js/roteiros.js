var tipoUnicoCombo = '';

// CHECKBOX DE SELEÇÃO DOS ROTEIROS
jQuery(document).on('click', 'input[type="checkbox"][name="selecao-roteiros[]"]', function() {

    let dadosLista = getCheckboxListagem();

    if(tipoUnicoCombo == 'unico'){
        if(dadosLista.idsChecked.length == 1){
            guardaIdsRoteiro(retornaIdRoteiroSelecionado(dadosLista.idsChecked));
            exibeInfoRoteirosSelecionados(dadosLista.selecionados);
            if (dadosLista.idsUnChecked.length > 0){
                jQuery.each(dadosLista.idsUnChecked, function(index, item) {
                    jQuery("#"+item).prop("disabled", true);
                });
            }
        } else {
            if (dadosLista.idsUnChecked.length > 0){
                jQuery.each(dadosLista.idsUnChecked, function(index, item) {
                    jQuery("#"+item).prop("disabled", false);
                });
            }
        }
    } else {

        if(dadosLista.idsChecked.length == 2){
        guardaIdsRoteiro(retornaIdRoteiroSelecionado(dadosLista.idsChecked));
        exibeInfoRoteirosSelecionados(dadosLista.selecionados);
           if (dadosLista.idsUnChecked.length > 0){
                jQuery.each(dadosLista.idsUnChecked, function(index, item) {
                    jQuery("#"+item).prop("disabled", true);
                });
            }
        } else {
            if (dadosLista.idsUnChecked.length > 0){
                jQuery.each(dadosLista.idsUnChecked, function(index, item) {
                    jQuery("#"+item).prop("disabled", false);
                });
            }
        }
    }
});

function retornaIdRoteiroSelecionado(idCheckbox){
    let arrIds = [];
    jQuery.each(idCheckbox, function(index, item) {
        let arr = item.split('-');
        arrIds.push(arr[2]);
    });
    return arrIds;
}

function exibeInfoRoteirosSelecionados(dadosSelecionados){

    if(dadosSelecionados.length >= 1){
        let strInfo = JSON.stringify(dadosSelecionados);
        localStorage.setItem('dados_roteiro_selecionado_tabs', strInfo);
    }

}

function guardaIdsRoteiro(ids){
    let postID = jQuery('#postID').val();
    var ajaxscript = { url : window.location.origin+'/wp-json/info-temp/idPost/'+postID }
    jQuery.post({
        url : ajaxscript.url,
        data: {
            ids: ids
        },
        method : 'POST', //Post method
        success : function( response ){ 
            console.log(response) 
        },
        error : function(error){ 
            console.log(error) 
        }
    });
}

function getCheckboxListagem(){
    let qtdCheckBoxRoteiros = 0;
    let arrIdsChecked = [];
    let arrIdsUnChecked = [];
    let roteirosSelecionados = [];
    document.querySelectorAll('input[type="checkbox"][name="selecao-roteiros[]').forEach(checkbox => {
        if (checkbox.checked) {
            arrIdsChecked.push(checkbox.id);
            roteirosSelecionados.push(checkbox.value);
        } else {
            arrIdsUnChecked.push(checkbox.id);
        }
        qtdCheckBoxRoteiros++;
    });
    return {idsChecked:arrIdsChecked, idsUnChecked:arrIdsUnChecked, tamCheck:qtdCheckBoxRoteiros, selecionados:roteirosSelecionados}
}

jQuery(document).ready(function($) {

    const ulElement = document.querySelector('.acf-tab-group'); //Nome da classe do grupo de abas dos roteiros
    if (ulElement) {
        ulElement.addEventListener('click', function(event) {
            if (event.target.dataset.key == 'field_689cc7a48f2a1'){
                let arrInfo = localStorage.getItem('dados_roteiro_selecionado_tabs');
                if(arrInfo){
                    let arr = JSON.parse(arrInfo);
             
                    if(arr.length == 1){
                        arrtab1 = JSON.parse(arr[0]);
                        $("#tab-conteudo-unico").html(arrtab1.nome);
                    } else if(arr.length == 2){
                        let arrTab1 = JSON.parse(arr[0]);
                        let arrTab2 = JSON.parse(arr[1]);

                        let nomeTab1 = arrTab1.nome;
                        let periodoTab1 = arrTab1.periodo;

                        let nomeTab2 = arrTab2.nome;
                        let periodoTab2 = arrTab2.periodo;

                        let htmlTab1 = '<p class="nome-up-disp-locais"><a href="#">'+nomeTab1+'</a></p><p class="periodo-up-disp-locais">'+periodoTab1+'</p>';
                        let htmlTab2 = '<p class="nome-up-disp-locais"><a href="#">'+nomeTab2+'</a></p><p class="periodo-up-disp-locais">'+periodoTab2+'</p>';

                        $("#tab-conteudo-unico").html(htmlTab1);
                        $("#tab-conteudo-combo").html(htmlTab2);
                    }
                    
                }
                
            }
        });
    }
    
    //Pega as informações referente a apenas um roteiro ou um combo
    function getTipoRoteiro(){
        $('[name="acf[field_689cc3ba89cd8]"]:checked').each(function(){
            let tipo = $(this).val();
            if(tipo == 'unico'){
                tipoUnicoCombo = 'unico';
                $("#msg-selecao-roteiro").html("Habilitada a opção de seleção única para este roteiro");
            } else {
                tipoUnicoCombo = 'combo';
                $("#msg-selecao-roteiro").html("Habilitada a opção de seleção de dois locais para este roteiro");
            }
        });
    }
    getTipoRoteiro();

    // RADIO DE SELEÇÃO DOS TIPO DE ROTEIRO(UNICO ou COMBO)
    $('input[type="radio"][name="acf[field_689cc3ba89cd8]"]').change(function() {

        // DESATIVA OS CAMPOS BLOQUEADOS E DESMARCA OS CHECKBOXs
        let dadosLista = getCheckboxListagem();
        if (dadosLista.idsUnChecked.length > 0){
            jQuery.each(dadosLista.idsUnChecked, function(index, item) {
                jQuery("#"+item).prop("disabled", false);
            });
        }
        if (dadosLista.idsChecked.length > 0){
            jQuery.each(dadosLista.idsChecked, function(index, item) {
                jQuery("#"+item).prop("checked", false);
            });
        }
        // BUSCA A INFORMAÇÃO DO TIPO DE ROTEIRO DESEJADO (Unico ou Combo)
        getTipoRoteiro();
    });
    

    $("#aplicar-filtro-roteiros").on('click', function() {

        const baseUrl = window.location.origin;

        let dados = getInformacoesFiltros();
        // console.log(dados);
        if(dados.tipo_local.length < 1 && dados.periodo.length < 1 && dados.dias_semana.length < 1 && !dados.acessibilidade && !dados.almoco && !dados.regiao){
            Swal.fire({
                position: "center",
                title: 'Selecione ao menos um item do filtro',
                showConfirmButton: false
            });
        } else {
            // Exibe Modal
            Swal.fire({
                position: "center",
                title: 'Aguarde um instante',
                text: "Estamos buscando locais de acordo com os filtros.",
                showConfirmButton: false
            });

            let postId = $('#postID').val();
            let data = {
                action: 'filtrar_roteiros',
                postId: postId,
                dados: dados
            }; 
            $.post(ajaxurl, data, function(response){ 
                var html = '';
                
                if(response.res){
                    if(response.dados.length > 0){
                        $.each(response.dados, function(index, item) {
                            let valor = JSON.stringify({nome: item.nome, periodo: item.dp });
                            html += "<tr>";
                            html += "<td><input type='checkbox' name='selecao-roteiros[]' id='selecao-up-"+item.id+"' value='"+valor+"'></td>";
                            html += "<td>"+item.tipo+"</td>";
                            html += '<td><a href="'+baseUrl+'/wp-admin/post.php?post='+item.id+'&action=edit" target="_blank">'+item.nome+'</a></td>';
                            html += "<td>"+item.regiao+"</td>";
                            html += "<td>"+item.distrito+"</td>";
                            html += "<td>"+item.dp+"</td>";
                            html += "<td>"+item.acessibilidade+"</td>";
                            html += "<td>"+item.almoco+"</td>";
                            html += "<tr>";
                        });
                    } else {
                        html = '<tr><td class="text-center" colspan="8">Nenhum resultado encontrado!</td></tr>';
                    }
                    Swal.close();
                }
                $("#conteudo-tab-roteiros").html(html);
            });
        }
    });

    function getInformacoesFiltros() {
        const dados = {};
        let tiposLocais = [];

        let nomeCampoLocal = 'acf[field_689cc85af022b][field_689cc880f022c][]';
        let nomeCampoRegiao = 'acf[field_689cc85af022b][field_689cc9a705cab]';
        let nomeCampoDiasSemana = 'acf[field_689cc85af022b][field_689cca0905cac][]';
        let nomeCampoPeriodo = 'acf[field_689cc85af022b][field_689cd975c6ca5][]';
        let nomeCampoAcessibilidade = 'acf[field_689cc85af022b][field_689cdd8e7c629]';
        let nomeCampoAlmoco = 'acf[field_689cc85af022b][field_689cddb17c62a]';

        //Pega as informações do campo 'Tipo de Local'
        $('[name="'+nomeCampoLocal+'"]:checked').each(function(){
            let tipo = $(this).val();
            tiposLocais.push(tipo);
        });
        dados.tipo_local = tiposLocais;
        //Pega a informação do campo 'Regiões'
        let regiao = $('select[name="'+nomeCampoRegiao+'"]').val();
        dados.regiao = regiao;
        //Pega a informação do campo 'Dias da Semana'
        let dias_semana = $('select[name="'+nomeCampoDiasSemana+'"]').val();
        dados.dias_semana = dias_semana;
        //Pega a informação do campo 'Períodos'
        let periodo = $('select[name="'+nomeCampoPeriodo+'"]').val();
        dados.periodo = periodo;
        //Pega a informação do campo 'Locais com acessibilidade'
        let acessibilidade = $('[name="'+nomeCampoAcessibilidade+'"]:checked').val();
        dados.acessibilidade = acessibilidade;
        //Pega a informação do campo 'Locais que fornecem almoço'
        let almoco = $('[name="'+nomeCampoAlmoco+'"]:checked').val();
        dados.almoco = almoco;

        return dados;
    }

    $("#publish").on('click', function() {
        localStorage.removeItem('dados_roteiro_selecionado_tabs');
    });

});


