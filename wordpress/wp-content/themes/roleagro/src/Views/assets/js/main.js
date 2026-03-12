jQuery(document).ready(function($) {
    $('.dropdown-toggle').dropdown();

    $('.carousel').carousel({
        interval: 5000
    });

    const urlLocal = window.location.pathname;

    if(urlLocal == '/wp-admin/post.php'){
        // Verifica o CEP digitado
        var eleCepGaragem = getIDInputACF('cep_garagem');
        if(eleCepGaragem){
            $("#acf-"+eleCepGaragem).mask('99999-999');
            jQuery("#acf-"+eleCepGaragem).on('input', function() {
                // Captura o valor digitado
                let valorDigitado = jQuery(this).val();
                if(valorDigitado.length == 9){
                    Swal.fire({
                        position: "center",
                        title: '<small>Buscando informações deste CEP...<?small>',
                        html: 'Aguarde um instante, por gentileza!',
                        showConfirmButton: false,
                        imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                        imageWidth: 100
                    });
                    valorDigitado = valorDigitado.replace("-", "");
                    buscaDadosCep(valorDigitado);
                }
            });
        }
    }

    // SCRIPT PÁGINA "MEU-PERFIL"
    if(urlLocal == '/meu-perfil/'){
        // Mascara dupla para telefone
        $("#telefoneUEPerfil, #telefoneContato").mask('(99) 99999-9999');
        $('#telefoneUEPerfil, #telefoneContato').blur(function(event) {
            if($(this).val().length == 15){ // Celular com 9 dígitos + 2 dígitos DDD e 4 da máscara
                $('#telefoneUEPerfil, #telefoneContato').mask('(99) 99999-9999');
            } else {
                $('#telefoneUEPerfil, #telefoneContato').mask('(99) 9999-9999');
            }
        });

        $("#btn-salvar-info-perfil").click(function(){

            setTimeout(function(){
                $("#btn-salvar-info-perfil").prop("disabled", true);
            }, 500);

            let telUe = $("#telefoneUEPerfil").val();
            let emailPerfil = $("#emailPerfil").val();
            let emailUEPerfil = $("#emailUEPerfil").val();

            if(!validarEmail(emailPerfil)){
                Swal.fire({icon: "info",html:'<b>Preencha corretamente o E-mail do Servidor, por gentileza!</b>',showConfirmButton: false,timer: 3000});
                setTimeout(function(){
                    $("#btn-salvar-info-perfil").prop("disabled", false);
                }, 500);
                return false;
            }

            if(telUe.length < 14){
                Swal.fire({icon: "info",html:'<b>Preencha corretamente o telefone da Unidade Educacional, por gentileza!</b>',showConfirmButton: false,timer: 3000});
                setTimeout(function(){
                    $("#btn-salvar-info-perfil").prop("disabled", false);
                }, 500);
                return false;
            }

            if(!validarEmail(emailUEPerfil)){
                Swal.fire({icon: "info",html:'<b>Preencha o E-mail da Unidade Educacional, por gentileza!</b>',showConfirmButton: false,timer: 3000});
                setTimeout(function(){
                    $("#btn-salvar-info-perfil").prop("disabled", false);
                }, 500);
                return false;
            }

            Swal.fire({
                position: "center",
                title: "<small>Salvando as informações...</small>",
                html: "Aguarde um instante, por gentileza.",
                showConfirmButton: false,
                imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                imageWidth: 100
            });


        });

        setTimeout(function(){
            $(".alert").alert('close');
        },5000);


    }

    // Inclui ranger de datas no filtro dos agendamentos
    if(urlLocal == '/agendamento-lista-presenca/'){
        $('#data_vivencia').daterangepicker({
            // Opções de configuração:
            opens: 'left', // Onde o calendário abre
            locale: {
                format: 'DD/MM/YYYY', // Formato da data
                applyLabel: 'Aplicar',
                cancelLabel: 'Cancelar',
                daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
            },
            ranges: { // Sugestões de intervalos
            'Hoje': [moment(), moment()],
            'Últimos 7 Dias': [moment().subtract(6, 'days'), moment()],
            'Este Mês': [moment().startOf('month'), moment().endOf('month')],
            'Mês Passado': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, function(start, end, label) {
            console.log("Início: " + start.format('DD/MM/YYYY') + " Fim: " + end.format('DD/MM/YYYY'));
            // Aqui você pode pegar os valores 'start' e 'end'
        });
    }


    //LISTA DE PRESENÇA
     $('#file-input-lista-presenca').on('change', function(event) {
        // 1. Pega o elemento input file
        var input = event.target;
        let idPost = $("#post_ID").val();

        // 2. Verifica se algum arquivo foi selecionado
        if (input.files.length > 0) {
            var arquivo = input.files[0]; // Pega o primeiro arquivo
            var tamArquivo = (arquivo.size / 1024 / 1024).toFixed(1); // MB
            var extensao = arquivo.name.split('.').pop().toLowerCase();
            var tamArquivoPermitido = 1;// MB
            
            if(tamArquivo > tamArquivoPermitido ){
                setTimeout(function(){
                    $('.remove-file').trigger('click');
                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text: "O tamanho do arquivo, não poderá ser maior que 1 MB."
                    });
                },100);
                return;
            }

            if(extensao != 'pdf'){
                setTimeout(function(){
                    $('#file-input-lista-presenca').val('');
                    Swal.fire({
                        icon: "warning",
                        title: "Atenção",
                        text: "Permitido apenas arquivos PDF."
                    });
                },100);
                return;
            }

            const formData = new FormData();
            formData.append('action', 'salva_arquivo_lista_presenca'); // Nome da ação no PHP
            formData.append('post_id', idPost);
            formData.append('arquivo', arquivo);

            fetch(ajax_params.admin_ajax, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                console.log(data)
                if(data.success){
                    if(data.data){
                        Swal.fire({
                            icon: "success",
                            position: "center",
                            html: '<b>'+data.data.msg+'</b>',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    }
                }
            }).catch(error => {
                console.log('Erro na requisição:', error);
            });

        } 
    });

    // testeRequestAdminAjax('testeApiReq');

});

function testeRequestAdminAjax(action){
    const formData = new FormData();
    formData.append('action', action); // Nome da ação no PHP

    fetch(ajax_params.admin_ajax, {
        method: 'POST',
        body: formData
    }).then(response => response.json()).then(data => {
        console.log(data);
        if(data.success){
                if(data.data){
                    console.log(JSON.parse(data.data))
                }
            }
    }).catch(error => {
        console.log('Erro na requisição:', error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const usuarioMenu = document.querySelector('.menu-item-usuario > a.menu-usuario-link');
    if (usuarioMenu) {
        usuarioMenu.addEventListener('click', function(e) {
            e.preventDefault();
            const li = this.parentElement;
            li.classList.toggle('ativo');
        });

        // Fecha o menu ao clicar fora
        document.addEventListener('click', function(e) {
            const aberto = document.querySelector('.menu-item-usuario.ativo');
            if (aberto && !aberto.contains(e.target)) {
                aberto.classList.remove('ativo');
            }
        });
    }
});

// Adiciona a classe ativa ao item de menu acessado
const menuItens = document.querySelectorAll('.menu-item a'); // Seleciona todos os links do menu
const currentPage = window.location.origin; // Obtém o caminho base da URL atual
const urlAtual = currentPage + window.location.pathname; // Obtém o caminho da URL atual
menuItens.forEach(item => {
    if (item.getAttribute('href') === urlAtual) {
        item.classList.add('menu-ativo'); // Adiciona a classe ao item correspondente
    }
});

function validarEmail(email) {
  const regex = /\S+@\S+\.\S+/; // 
  return regex.test(email);
}

function getIDInputACF(nome_campo){
    let field = acf.getFields({name: nome_campo})[0];
    if(field){
        return field.data.key;
    }
}

function buscaDadosCep(cep){

    let urlViaCep = "https://viacep.com.br/ws/"+cep+"/json/";

    jQuery.get(urlViaCep, function(data, status) {
        if(status == 'success'){
            jQuery("#acf-"+getIDInputACF('logradouro_garagem')).val(data.logradouro);
            jQuery("#acf-"+getIDInputACF('bairro_garagem')).val(data.bairro);
            jQuery("#acf-"+getIDInputACF('cidade_garagem')).val(data.localidade);
            jQuery("#acf-"+getIDInputACF('estado_garagem')).val(data.estado);
            Swal.close();
            toastr.success('Endereço da garagem carregado com sucesso!');
        } else {
            Swal.close();
            toastr.error('Erro: Não foi possível obter o endereço da garagem.');
        }
    });

}

function buscaAlunosComDietaUe(codUe){
    if(codUe){
        fetch("/wp-json/dietas-ue/idUe", {
            method: 'POST', 
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({codUe: codUe})
        }).then(response => response.json()).then(data => {
            
            if(data.success){
                console.log(data.data);
            }
            //     let arrAlunosDieta = [];
            //     let arrAlunos = JSON.parse(data.data);
            //     jQuery.each(arrAlunos.solicitacoes, function(index, item) {
            //         let turmaAluno = item.serie[0];
            //         if(turmaAluno == '6' && item.classificacao_dieta_ativa !== null ){
            //             arrAlunosDieta.push(item);
            //         }
            //     });
            //     if(arrAlunosDieta.length > 0){
            //         localStorage.setItem("alunos-com-dieta", JSON.stringify(arrAlunosDieta));
            //     }
            // }
        }).catch(() => {
            console.log('Erro ao carregar as dietas da UE');
        });
    }
}