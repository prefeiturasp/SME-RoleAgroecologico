jQuery(document).ready(function($) {

    const urlLocal = window.location.pathname;

    if(urlLocal == '/lista-de-presenca/'){

        let idPost = $("#post_id").val();
        let qtdTurmas = $("#qtd_turma").val();
        let arrCheckAlunos = [];
        for(let i=1; qtdTurmas >= i;i++){
            let nomeCampo = 'ckb-alunos-t'+i;
            verificaChkAlunos(i);
            $c=0;
            $('input[type="checkbox"][name="'+nomeCampo+'"]').each(function(){
                let alunosTurma = getCkbAlunosTurmaLP(i, nomeCampo);
            });
        }

        $('#salva-lista-presenca').submit(function(e) {
            e.preventDefault(); // Impede o envio tradicional do formulário

            let idPost = $("#post_id").val();
            let obsRole = $("#obs-role").val();

            var fileInput = $('#arquivo-input-lista-presenca')[0]; // Pega o elemento DOM real do input
            var file = fileInput.files[0]; // Pega o arquivo selecionado

            const formData = new FormData();
            formData.append('action', 'set_lista_presenca'); // Nome da ação no PHP
            formData.append('post_id', idPost);
            formData.append('arquivo', file);
            formData.append('obsRole', obsRole);

            fetch(ajax_params.admin_ajax, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
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
        });

    }

});

function verificaChkAlunos(ordemTurma){
    let nomeCampo = 'ckb-alunos-t'+ordemTurma;
    jQuery(document).on('click', 'input[type="checkbox"][name="'+nomeCampo+'"]', function() {
        let alunosTurma = getCkbAlunosTurmaLP(ordemTurma, nomeCampo);
        salvaItemLocal('alunos-selecionados-turma-'+(ordemTurma), alunosTurma['alunosSelecionados']);
    });
}

function getCkbAlunosTurmaLP(opc, nomeCampo){
    let qtdCheckBoxAlunos = 0;
    let arrIdsChecked = [];
    let arrIdsUnChecked = [];
    let alunosSelecionados = [];
    document.querySelectorAll('input[type="checkbox"][name="'+nomeCampo+'"]').forEach(checkbox => {
        if (checkbox.checked) {
            arrIdsChecked.push(checkbox.id);
            alunosSelecionados.push(checkbox.value);
        } else {
            arrIdsUnChecked.push(checkbox.id);
        }
        qtdCheckBoxAlunos++;
    });
    return {
        idsMarcados:arrIdsChecked, 
        idsDesmarcados:arrIdsUnChecked, 
        qtdAlunos:qtdCheckBoxAlunos, 
        alunosSelecionados:alunosSelecionados
    }
}

function salvaItemLocal(nome, dados){
    localStorage.setItem(nome, dados);
}

function salvaCheckAlunoTurma(elemento){

    let idPost = jQuery("#post_id").val();
    let idElemento = elemento.id;
    let idAluno = elemento.value;

    let arrEle = idElemento.split('-');
    let idTurma = arrEle[3];
    let checado = false;

    if(elemento.checked){
        checado = true;
    } 

    let dados = {
        idPost: idPost,
        idTurma: idTurma,
        idAluno: idAluno,
        check: checado,
        opcao: 'aluno-lista'
    }

    fetch("/wp-json/agendamento/lista-presenca", {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dados)
    }).then(response => response.json()).then(data => {
        if(data.success){
            if(data.data){
                toastr.success('Aluno(a) atualizado(a) com sucesso!');
            }
        }
    });
}

function salvaCheckAcoEdu(elemento){

    let idPost = jQuery("#post_id").val();
    let idAco = elemento.value;

    let arrEle = idAco.split('-');
    let idEduc = arrEle[0];
    let tipo = arrEle[1];
    let checado = false;

    if(elemento.checked){
        checado = true;
    } 

    let dados = {
        idPost: idPost,
        idEduc: idEduc,
        tipo: tipo,
        check: checado,
        opcao: 'acompanhante-lista'
    }

    fetch("/wp-json/agendamento/lista-presenca", {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dados)
    }).then(response => response.json()).then(data => {
        if(data.success){
            if(data.data){
                toastr.success('Acompanhante atualizado com sucesso!');
            }
        }
    });
}

