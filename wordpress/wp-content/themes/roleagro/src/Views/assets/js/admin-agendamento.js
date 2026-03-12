const urlBase = window.location.origin;
const path = window.location.pathname;
const urlBaseImg = urlBase+'/wp-content/themes/roleagro/src/Views/assets/img';

jQuery(document).ready(function($) {

   if (path === '/administrar-role/') {
        const fileInput = document.getElementById('file-input-meus-agendamentos');
        const filePreview = document.getElementById('filePreview');
        jQuery("#filePreview").hide();              
        fileInput.addEventListener('change', function () {
            filePreview.innerHTML = ''; // limpa preview anterior

            if (this.files.length > 0) {

                jQuery("#enviaArquivo").hide();
                jQuery("#filePreview").show(); 

                const file = this.files[0];
                const fileSize = (file.size / 1024 / 1024).toFixed(1); // MB

                // ícone genérico (pode trocar conforme extensão)
                let icon = 'https://img.icons8.com/fluency/96/000000/zip.png';

                // cria o card
                const preview = document.createElement('div');
                preview.classList.add('file-preview');
                preview.innerHTML = `<img src="${icon}" alt="file icon">
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <span class="tamArquivo">${fileSize} MB</span>
                </div>
                <span class="remove-file">&times;</span>`;

                // botão remover
                preview.querySelector('.remove-file').addEventListener('click', () => {
                jQuery("#enviaArquivo").show();
                jQuery("#filePreview").hide();  
                fileInput.value = '';
                filePreview.innerHTML = '';
                });

                filePreview.appendChild(preview);
            }
        });
   
        // ########## Botão para adicionar e remover participantes
        // const adcRemParticipantes = document.getElementById("adcRemParticipantes");
        // adcRemParticipantes.addEventListener("click", function() {
        //     let idRid = document.getElementById("idRid").value;
        //     let idIid = document.getElementById("idIid").value;

        //     Swal.fire({
        //         icon: "warning",
        //         title: "Fique atento!",
        //         html: "Só é possível realizar alterações até 7 dias antes da data do passeio.",
        //         showDenyButton: true,
        //         showCancelButton: false,
        //         denyButtonText: 'Continuar',
        //         confirmButtonText: "Retornar"
        //     }).then((result) => {
        //         if (result.isConfirmed) {
        //             Swal.close();
        //         } else if (result.isDenied) {
        //             window.location.href = '/edita-agendamento/?iid='+idIid+'&rid='+idRid;
        //         }
        //     });
        // });
    }


    if(path === '/edita-agendamento/'){
        
        alunosTurmasSelecionadasEdit();

        let step = localStorage.getItem('etapa_atual_edit');
        if (step) {
            this.step = parseInt(step);
        }

        let qtdTurmas = document.getElementById("qtdTurmas").value;
        for (let i = 0; i < qtdTurmas; i++) {
            let ids = document.getElementById("alunos-selecionados-turma-"+(i+1)+"-edit").value;
            localStorage.setItem('alunos-selecionados-turma-'+(i+1)+"-edit", ids);
        }

        let qtdMax = document.getElementById("qtdParticipantesMax").value;
        let qtdMin = document.getElementById("qtdParticipantesMin").value;
        let dataAgendamento = document.getElementById("dataAgendamento").value;
        let userId = document.getElementById("idUserEdit").value;
        localStorage.setItem('idUserEdit', userId);
        localStorage.setItem('qtdMaxParticipantes', qtdMax);
        localStorage.setItem('qtdMinParticipantes', qtdMin);
        localStorage.setItem('agendamentoEdit', dataAgendamento);

        // CONTA A QUANTIDADE DE CARACTERES DO RF PARA SOLICITAR DADOS NA API
        buscaEducadorPorRFEdit('idRFEdit1', 'nomeEducadorEdit1', 0);
        buscaEducadorPorRFEdit('idRFEdit2', 'nomeEducadorEdit2', 1);

    } 

});

function retornaEducadoresEditLocalStorageEdit() {
    let arrEducadores = localStorage.getItem('educadoresEdit');
    let educadores = [];
    if(arrEducadores){
        educadores = JSON.parse(arrEducadores);
    }
    return educadores;
}

function retornaAcompanhantesLocalStorageEdit() {
    let arrAcompanhantes = localStorage.getItem('acompanhantesEdit');
    let acompanhantes = [];
    if(arrAcompanhantes){
        acompanhantes = JSON.parse(arrAcompanhantes);
    }
    return acompanhantes;
}

function retornaAlunosTurmaSelecionadasLocalStoragEdit() {
    let arrAlunoTurma = localStorage.getItem('alunos-turmas-selecionadas-edit');
    let alunosTurma = [];
    if(arrAlunoTurma){
        alunosTurma = JSON.parse(arrAlunoTurma);
    }
    return alunosTurma;
}

function adicionaAcoesCheckboxTurmaEdit(){
    
    let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadasEdit');

    if(turmasSelecionadas){
        let arrTurmas = turmasSelecionadas.split(',');
        jQuery.each(arrTurmas, function(index, item) {

            verificarCheckBox(index+1);
        
            // SALVA OS VALORES DAS CHECKBOXs DE SELEÇÃO DOS ALUNOS DAS TURMAS
            jQuery(document).on('click', 'input[type="checkbox"][name="ckb-alunos-t'+(index+1)+'[]"]', function() {
                let alunosTurma = getCkbAlunosTurmaEdit(index+1);
                localStorage.setItem('alunos-selecionados-turma-'+(index+1)+"-edit", alunosTurma['alunosSelecionados']);
                exibeQtdTotalParticipantes();
            });

            // SALVA OS VALORES DAS CHECKBOXs DE SELEÇÃO DOS ALUNOS DAS TURMAS
            jQuery(document).on('click', 'input[type="checkbox"][name="ckb-turma-'+(index+1)+'"]', function() {
                if (this.checked) {
                    let alunosTurma = getCkbAlunosTurmaEdit(index+1);
                    if (alunosTurma.idsDesmarcados.length > 0){
                        jQuery.each(alunosTurma.idsDesmarcados, function(index, item) {
                            jQuery("#"+item).prop("checked", true);
                        });
                        let alunos = getCkbAlunosTurmaEdit(index+1);
                        localStorage.setItem('alunos-selecionados-turma-'+(index+1)+"-edit", alunos['alunosSelecionados']);
                    }
                } else {
                    let alunosTurma = getCkbAlunosTurmaEdit(index+1);
                    if (alunosTurma.idsMarcados.length > 0){
                        jQuery.each(alunosTurma.idsMarcados, function(index, item) {
                            jQuery("#"+item).prop("checked", false);
                        });
                        let alunos = getCkbAlunosTurmaEdit(index+1);
                        localStorage.setItem('alunos-selecionados-turma-'+(index+1)+"-edit", alunos['alunosSelecionados']);
                    }
                }
            });

        });
    }
}

setTimeout(function() {
    adicionaAcoesCheckboxTurmaEdit();
    exibeQtdTotalParticipantes();
}, 1000);

function editaAgendamentoForm() { 

    if(!this.step){
        this.step = 2;
        localStorage.setItem('etapa_atual_edit', this.step);
    }

    localStorage.setItem('idsTurmasSelecionadasEdit', document.getElementById('idsTurmasSelecionadasEdit').value);

    const idUser = jQuery('#idUserEdit').val();
    if(idUser){
        let alunosDietas = localStorage.getItem("alunos-com-dieta-edit");
        if(!alunosDietas){
            const formData = new FormData();
            formData.append('action', 'dietas_por_iduser'); // Nome da ação no PHP
            formData.append('nonce', ajax_params.nonce); // O nonce gerado
            formData.append('idUser', idUser);

            fetch(ajax_params.admin_ajax, {
                method: 'POST',
                body: formData
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    let arrAlunosDieta = [];
                        let arrAlunos = JSON.parse(data.data);
                        jQuery.each(arrAlunos.results, function(index, item) {
                            arrAlunosDieta.push(item);
                        });
                        if(arrAlunosDieta.length > 0){
                            localStorage.setItem("alunos-com-dieta-edit", JSON.stringify(arrAlunosDieta));
                        }
                } else {
                    console.log('Erro:', data.data);
                }
            }).catch(error => {
                console.log('Erro na requisição:', error);
            });
        }
    } 

    return {
        step: 2,
        stepHistoryEdit: 2,
        loading: false,
        alunos: [],
        alunosSelecionados: [],
        educadoresEdit: [
            { rf: '', nome: '', data_nascimento: '', celular: '', dieta: '', necessidades: '', tipo: 'Educador' },
            { rf: '', nome: '', data_nascimento: '', celular: '', dieta: '', necessidades: '', tipo: 'Educador' }
        ],
        acompanhantesEdit: [],
        dados: {
        },
        get progresso() {
            return (this.step / 4) * 100;
        },
        salvarDadosEdit() {
            let educadores = retornaEducadoresEditLocalStorageEdit();
            let i = 0;
            educadores.forEach(element => {
                this.educadoresEdit[i].rf = element.rf; 
                this.educadoresEdit[i].nome = element.nome;
                i++;
            });
            localStorage.setItem('educadoresEdit', JSON.stringify(this.educadoresEdit));
        },
		adicionarAcompanhanteEdit() {
            this.acompanhantesEdit.push({
                rf: '',
                nome: '',
                data_nascimento: '',
                dieta: '',
                necessidades: '',
                tipo: 'Acompanhante',
                justificativa: ''
            });
        },
        removerAcompanhanteEdit(index) {
            this.acompanhantesEdit.splice(index, 1);
        },
        proximoEdit() {
            if (this.validarEdit()) { 

                this.salvarDadosEdit();
 
                if (this.step < 4) {
                    this.step++;
                }

                if(this.step === 3){

                    localStorage.setItem('etapa_atual_edit', this.step)

                    let qtdMin = parseInt(jQuery('#qtdParticipantesMin').val());
                    let qtdMax = parseInt(jQuery('#qtdParticipantesMax').val());
            
                    let educadores = retornaEducadoresEditLocalStorageEdit();
                    let acompanhantes = retornaAcompanhantesLocalStorageEdit();

                    let arrAlunosTurmas = retornaAlunosTurmaSelecionadasLocalStoragEdit();
        
                    let idsTurmas = localStorage.getItem('idsTurmasSelecionadasEdit');
                    let arrIds = idsTurmas.split(',');
                    let qtdAlunos = 0;

                    let qtdAlunosComDeficiencia = 0;
                    let qtdAlunosComDieta = 0;

                    for(let i=0; arrIds.length > i; i++){

                        let alunosTurma = localStorage.getItem('alunos-selecionados-turma-'+(i+1)+"-edit");
                        let arrIdsAluno = alunosTurma.split(',');
                        qtdAlunos = qtdAlunos + arrIdsAluno.length;

                        arrAlunosTurmas[i]['alunosTurma'].forEach(element => {
                            for(let c=0; arrIdsAluno.length > c; c++){
                                if(element['codigoAluno'] == arrIdsAluno[c]){
                                    if(element['possuiDeficiencia'] == 1){
                                        qtdAlunosComDeficiencia++;
                                    }
                                    if(element['possuiDieta'] == 1){
                                        qtdAlunosComDieta++;
                                    }
                                }
                            }
                        });
                    }
                    let qtdEdu = educadores.length;
                    let qtdAcomp = acompanhantes.length;

                    let qtdGeral = qtdEdu + qtdAcomp + qtdAlunos;
                   
                    if(qtdGeral >= qtdMin && qtdGeral <= qtdMax){

                        localStorage.setItem('qtdAlunos', qtdAlunos);
                        localStorage.setItem('qtdAlunosComDeficiencia', qtdAlunosComDeficiencia);
                        localStorage.setItem('qtdAlunosComDieta', qtdAlunosComDieta);

                        localStorage.setItem('qtdEducadores', qtdEdu);
                        localStorage.setItem('qtdAcompanhantes', qtdAcomp);
                        localStorage.setItem('qtdParticipantes', qtdGeral);

                        this.step = 3;
                        // CARREGA AS INFORMAÇÕES DA 3 TABELA
                        renderizaDadosTabConfEdit();
                    } else if(qtdGeral < qtdMin){
                        Swal.fire({
                            icon: "info",
                            position: "center",
                            html: '<b>Atenção! A inscrição não atinge o mínimo de '+qtdMin+' participantes para realizar o agendamento!</b>',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        this.step = 2;
                        anterior();
                    } else if(qtdGeral > qtdMax){
                        Swal.fire({
                            icon: "info",
                            position: "center",
                            html: '<b>Atenção! A inscrição ultrapassou o máximo de '+qtdMax+' participantes para realizar o agendamento!</b>',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        this.step = 2;
                        anterior();
                    }
                    localStorage.setItem('etapa_atual_edit', this.step);
                }
            }
        },
        anteriorEdit() {
            if (this.step > 2) {
                this.step--;
                localStorage.setItem('etapa_atual_edit', this.step);
                exibeQtdTotalParticipantes();
            } else if (this.step == 2) {
                this.stepHistoryEdit--;
            }
        },
        acionaBtnVoltarEdit(){
            this.anteriorEdit();
            
            if (this.stepHistoryEdit <= 2) {
                let protocolo = window.location.protocol;
                let parametros = window.location.search;
                let baseUrl = window.location.host;
                window.location.href = protocolo+'//'+baseUrl+'/administrar-role/'+parametros;
            }
        },
        salvaLocalStorageEdit(nome, dados){
            localStorage.setItem(nome, JSON.stringify(dados));
        },
        validarEdit() {

            let educadores = retornaEducadoresEditLocalStorageEdit();

            let rfEdu1 = jQuery("#idRFEdit1").val();
            if (this.step === 2 && !rfEdu1 || rfEdu1.length != 7) {
                Swal.fire({icon: "info", html:'<b>Preencha o RF do primeiro educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }
            let nomeEdu1 = educadores[0].nome;
            if (this.step === 2 && !nomeEdu1) {
                let nomeDigitado1 = jQuery("#nomeEducadorEdit1").val();
                if(nomeDigitado1){
                    educadores[0].nome = nomeDigitado1;
                    localStorage.setItem('educadoresEdit', JSON.stringify(educadores));
                } else {
                    Swal.fire({icon: "info",html:'<b>Preencha o nome do primeiro educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }
            }
            let rfEdu2 = jQuery("#idRFEdit2").val();
            if (this.step === 2 && !rfEdu2 || rfEdu2.length != 7) {
                Swal.fire({icon: "info", html:'<b>Preencha o RF do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }
            let nomeEdu2 = educadores[1].nome;
            if (this.step === 2 && !nomeEdu2) {
                let nomeDigitado2 = jQuery("#nomeEducadorEdit2").val();
                if(nomeDigitado2){
                    educadores[1].nome = nomeDigitado2;
                    localStorage.setItem('educadoresEdit', JSON.stringify(educadores));
                } else {
                    Swal.fire({icon: "info",html:'<b>Preencha o nome do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }
            }
            
            if (this.step === 2 && !this.educadoresEdit[0].data_nascimento ) {
                Swal.fire({icon: "info",html:'<b>Preencha corretamente a data de nascimento do primero educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }

            if (this.step === 2 && (!this.educadoresEdit[0].celular || this.educadoresEdit[0].celular.length < 14 )) {
                Swal.fire({icon: "info",html:'<b>Preencha o telefone do primero educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }

            if (this.step === 2 && !this.educadoresEdit[0].data_nascimento && !this.educadoresEdit[1].data_nascimento) {
                Swal.fire({icon: "info", html:'<b>Preencha corretamente a data de nascimento do educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }

            if (this.step === 2 && !this.educadoresEdit[1].data_nascimento ) {
                Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }

            if (this.step === 2 && (!this.educadoresEdit[1].celular || this.educadoresEdit[1].celular.length < 14 )) {
                Swal.fire({icon: "info",html:'<b>Preencha o telefone do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                return false;
            }


            let idRFCpf1 = document.getElementById("idRFCpfEdit-1");
                if(idRFCpf1){

                    let arrAcompanhantes1 = retornaAcompanhantesLocalStorageEdit();

                    if(!idRFCpf1.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].rf = idRFCpf1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes1);
                    }

                    let nomeAcompanhante1 = document.getElementById("nomeAcompanhanteEdit-1");
                    if(!nomeAcompanhante1.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].nome = nomeAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes1);
                    }

                    let dataNascimentoAcompanhante1 = document.getElementById("dataNascimentoAcompanhanteEdit-1");
                    if (!dataNascimentoAcompanhante1.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].data_nascimento = dataNascimentoAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes1);
                    }

                    let celAcompanhante1 = document.getElementById("celAcompanhanteEdit-1");
                    if (!celAcompanhante1.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].celular = celAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes1);
                    }

                    let dietaAcompanhante1 = document.getElementById("dietaAcompanhanteEdit-1");
                    if (dietaAcompanhante1.value) {
                        arrAcompanhantes1[0].dieta = dietaAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes1);
                    }

                    let necessidadesAcompanhante1 = document.getElementById("necessidadesAcompanhanteEdit-1");
                    if (necessidadesAcompanhante1.value) {
                        arrAcompanhantes1[0].dieta = necessidadesAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes1);
                    }

                    let justificativaAcompanhante1 = document.getElementById("justificativaAcompanhanteEdit-1");
                    if (!justificativaAcompanhante1.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].justificativa = justificativaAcompanhante1.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes1);
                    }
                } 

                let idRFCpf2 = document.getElementById("idRFCpfEdit-2");
                if(idRFCpf2){
                   
                    let arrAcompanhantes2 = retornaAcompanhantesLocalStorageEdit();
                    let tamArr2 = arrAcompanhantes2.length;
                    if(tamArr2 == 1){
                        arrAcompanhantes2.push({"rf":"","nome":"","data_nascimento":"","celular":"","dieta":"","necessidades":"","tipo":"Acompanhante","justificativa":""});
                    }

                    if(!idRFCpf2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].rf = idRFCpf2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes2);
                    }

                    let nomeAcompanhante2 = document.getElementById("nomeAcompanhanteEdit-2");
                    if(!nomeAcompanhante2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].nome = nomeAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes2);
                    }

                    let dataNascimentoAcompanhante2 = document.getElementById("dataNascimentoAcompanhanteEdit-2");
                    if (!dataNascimentoAcompanhante2.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].data_nascimento = dataNascimentoAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes2);
                    }

                    let celAcompanhante2 = document.getElementById("celAcompanhanteEdit-2");
                    if (!celAcompanhante2.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].celular = celAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes2);
                    }

                    let dietaAcompanhante2 = document.getElementById("dietaAcompanhanteEdit-2");
                    if (dietaAcompanhante2.value) {
                        arrAcompanhantes2[1].dieta = dietaAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes2);
                    }

                    let necessidadesAcompanhante2 = document.getElementById("necessidadesAcompanhanteEdit-2");
                    if (necessidadesAcompanhante2.value) {
                        arrAcompanhantes2[1].dieta = necessidadesAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes2);
                    }

                    let justificativaAcompanhante2 = document.getElementById("justificativaAcompanhanteEdit-2");
                    if (!justificativaAcompanhante2.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].justificativa = justificativaAcompanhante2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes2);
                    }
                } 

                let idRFCpf3 = document.getElementById("idRFCpfEdit-3");
                if(idRFCpf3){

                    let arrAcompanhantes3 = retornaAcompanhantesLocalStorageEdit();
                    let tamArr3 = arrAcompanhantes3.length;
                    if(tamArr3 == 2){
                        arrAcompanhantes3.push({"rf":"","nome":"","data_nascimento":"","celular":"","dieta":"","necessidades":"","tipo":"Acompanhante","justificativa":""});
                    }

                    if(!idRFCpf2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].rf = idRFCpf2.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes3);
                    }

                    let nomeAcompanhante3 = document.getElementById("nomeAcompanhanteEdit-3");
                    if(!nomeAcompanhante3.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].nome = nomeAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes3);
                    }

                    let dataNascimentoAcompanhante3 = document.getElementById("dataNascimentoAcompanhanteEdit-3");
                    if (!dataNascimentoAcompanhante3.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].data_nascimento = dataNascimentoAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes3);
                    }

                    let celAcompanhante3 = document.getElementById("celAcompanhanteEdit-3");
                    if (!celAcompanhante3.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].celular = celAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes3);
                    }

                    let dietaAcompanhante3 = document.getElementById("dietaAcompanhanteEdit-3");
                    if (dietaAcompanhante3.value) {
                        arrAcompanhantes3[2].dieta = dietaAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes3);
                    }

                    let necessidadesAcompanhante3 = document.getElementById("necessidadesAcompanhanteEdit-3");
                    if (necessidadesAcompanhante3.value) {
                        arrAcompanhantes3[2].dieta = necessidadesAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit', arrAcompanhantes3);
                    }

                    let justificativaAcompanhante3 = document.getElementById("justificativaAcompanhanteEdit-3");
                    if (!justificativaAcompanhante3.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].justificativa = justificativaAcompanhante3.value;
                        this.salvaLocalStorageEdit('acompanhantesEdit',arrAcompanhantes3);
                    }
                }

                // Valida os alunos
                let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadasEdit');
                if(turmasSelecionadas){
                    let alunosSelecionados = localStorage.getItem('alunos-selecionados-turma-1-edit');
                    if(!alunosSelecionados){
                        Swal.fire({icon: "info",html:'<b>Não existe alunos selecionados!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    }
                }

          
            return true;
        },
        carregaAcompanhantesEdit(){
            let acompanhantesEdit = JSON.parse(localStorage.getItem('acompanhatesEdit'));

            if (acompanhantesEdit) {
                for(let i=0; acompanhantesEdit.length > i; i++){
                    this.acompanhantesEdit[i].rf = acompanhantesEdit[i].rf;
                    this.acompanhantesEdit[i].nome = acompanhantesEdit[i].nome;
                    this.acompanhantesEdit[i].data_nascimento = acompanhantesEdit[i].data_nascimento;
                    this.acompanhantesEdit[i].justificativa = acompanhantesEdit[i].justificativa;
                }
            } else {
                fetch("/wp-json/get-acompanhantes/idPost", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_inscricao: document.getElementById('idPost').value })
                }).then(response => response.json()).then(data => {
                    if(data['success']){
                        this.acompanhantesEdit = data.data;
                        localStorage.setItem('acompanhantesEdit', JSON.stringify(data.data));
                    }
                }).catch(() => {
                    console.log('Erro ao carregar acompanhantes');
                });
            }
            
        },
        carregaEducadoresEdit(){
            let educadoresEdit = JSON.parse(localStorage.getItem('educadoresEdit'));

            if (educadoresEdit) {
                for(let i=0; educadoresEdit.length > i; i++){
                    this.educadoresEdit[i].rf = educadoresEdit[i].rf;
                    this.educadoresEdit[i].nome = educadoresEdit[i].nome;
                    this.educadoresEdit[i].data_nascimento = educadoresEdit[i].data_nascimento;
                }
            } else {
                fetch("/wp-json/get-educadores/idPost", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id_inscricao: document.getElementById('idPost').value })
                }).then(response => response.json()).then(data => {
                    if(data['success']){
                        this.educadoresEdit = data.data;
                        localStorage.setItem('educadoresEdit', JSON.stringify(data.data));
                    }
                }).catch(() => {
                    console.log('Erro ao carregar acompanhantes');
                });
            }
            
        },
        atualizarAgendamento(){

            Swal.fire({
                position: "center",
                title: '<small>Aguarde um momento...</small>',
                html: 'As informações estão sendo salvas.',
                showConfirmButton: false,
                imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                imageWidth: 100
            });

            let arrAlunosTurmas = retornaAlunosTurmaSelecionadasLocalStoragEdit();
            let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadasEdit');
            let arrayTurmas = turmasSelecionadas.split(',');
            let arrTurmas = [];
            let i = 0;
            arrayTurmas.forEach(element => {
                let Turma = element.split(' - ');
                let idTurma = Turma[3];
                let nomeTurma = Turma[0]+' - '+Turma[1]+' - '+Turma[2];

                let strIdsAlunos = localStorage.getItem('alunos-selecionados-turma-'+(i+1)+"-edit");
                let arrIdsAluno = strIdsAlunos.split(',');
                let alunosTurma = [];

                arrAlunosTurmas[i]['alunosTurma'].forEach(element => {
                    for(let c=0; arrIdsAluno.length > c; c++){
                        if(element['codigoAluno'] == arrIdsAluno[c]){
                            alunosTurma.push(element);
                        }
                    }
                });

                arrTurmas.push({idTurma:idTurma, nomeTurma:nomeTurma, alunosTurma:alunosTurma});
                i++;
            });

            let idInscricao = document.getElementById('idPost').value;
            let idUserEdit = localStorage.getItem('idUserEdit');
            let educadores = retornaEducadoresEditLocalStorageEdit();
            let acompanhantes = retornaAcompanhantesLocalStorageEdit();

            let dados = {
                idUser: idUserEdit,
                idInscricao: idInscricao,
                dadosTurmas: arrTurmas,
                dadosEducadores: educadores,
                dadosAcompanhantes: acompanhantes
            }

            fetch("/wp-json/agendamento/atualizar", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dados)
            }).then(response => response.json()).then(data => {
                if(data['success']){
                    Swal.close();
                    Swal.fire({icon: "success",html:'<b>Inscrição Atualizada com sucesso!</b>',showConfirmButton: false});
                    removeItemLocalStorageEdit();
                    setTimeout(() => {
                        window.location.href = '/meus-agendamentos';
                    },3000);
                }
            }).catch(() => {
                console.log('Erro ao carregar pessoas');
            });
        },
        verificaDataEdit(valor){
            if (typeof valor !== 'string') {
                return false
            } else if (!(/^(\d{2})\/(\d{2})\/(\d{4})$/.exec(valor))) {
                return false
            } else {

                // Divide a data para o objeto "data"
                const partesData = valor.split('/')
                const data = { 
                    dia: partesData[0], 
                    mes: partesData[1], 
                    ano: partesData[2] 
                }
                
                // Converte strings em número
                const dia = parseInt(data.dia)
                const mes = parseInt(data.mes)
                const ano = parseInt(data.ano)

                if(ano < 1890){
                    return false
                }
                
                // Dias de cada mês, incluindo ajuste para ano bissexto
                const diasNoMes = [ 0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ]

                // Atualiza os dias do mês de fevereiro para ano bisexto
                if (ano % 400 === 0 || ano % 4 === 0 && ano % 100 !== 0) {
                    diasNoMes[2] = 29
                }
                
                // Regras de validação:
                // Mês deve estar entre 1 e 12, e o dia deve ser maior que zero
                if (mes < 1 || mes > 12 || dia < 1) {
                    return false
                }
                // Valida número de dias do mês
                else if (dia > diasNoMes[mes]) {
                    return false
                }
                
                // Passou nas validações
                return true
            }
        }
    }
}

function removeItemLocalStorageEdit(){
    // Limpa localStorage dos participantes
    for(let i=0; 5 > i; i++){
        localStorage.removeItem('alunos-selecionados-turma-'+(i+1)+"-edit");
    }
    localStorage.removeItem('agendamentoEdit');
    localStorage.removeItem('idsTurmasSelecionadasEdit');
    localStorage.removeItem('alunos-turmas-selecionadas-edit');
    localStorage.removeItem('educadoresEdit');
    localStorage.removeItem('acompanhantesEdit');
    localStorage.removeItem('idUserEdit');
    localStorage.removeItem('idRoteiro');
    localStorage.removeItem('nomeRoteiro');
    localStorage.removeItem('etapa_atual_edit');
    localStorage.removeItem('alunos-com-dieta-edit');

    // Limpa localStorage de quantidades
    localStorage.removeItem('qtdAlunos');
    localStorage.removeItem('qtdAcompanhantes');
    localStorage.removeItem('qtdEducadores');
    localStorage.removeItem('qtdParticipantes');
    localStorage.removeItem('qtdAlunosComDeficiencia');
    localStorage.removeItem('qtdAlunosComDieta');
    localStorage.removeItem('qtdMaxParticipantes');
    localStorage.removeItem('qtdMinParticipantes');
}

function renderizaTurmasEdit(){
    console.log('Renderizou');
    let arrTurmas = JSON.parse(localStorage.getItem("alunos-turmas-selecionadas-edit"));
    let alunosDieta = JSON.parse(localStorage.getItem("alunos-com-dieta"));
    if(!alunosDieta){
        alunosDieta = false;
    }
    let html = '';

    if (!arrTurmas) {
        return;
    } else {
        if(arrTurmas && arrTurmas.length > 0){
            let qtd = arrTurmas.length;
            for(let i=0; qtd > i; i++){
                let nomeTurma = arrTurmas[i]['turma']['nomeTurma'];
                let codTurma = arrTurmas[i]['turma']['codTurma'];
                html += '<div class="col-md-6">'+ 
                            '<h6>Estudantes da Turma: <span class="text-dark">'+nomeTurma+'</span></h6>'+
                            '<div class="box-border">'+

                                '<div class="checkbox-label">'+
                                    '<label><input type="checkbox" name="ckb-turma-'+(i+1)+'" id="turma'+(i+1)+'" value="turma'+(i+1)+'"> <strong class="txt-label-green">Cód. EOL e Nome do Estudante</strong></label>'+
                                '</div>';

                        html += '<table class="table">';

                            for(let c=0; arrTurmas[i]['alunosTurma'].length > c; c++){
                                let codAluno = arrTurmas[i]['alunosTurma'][c]['codigoAluno'];
                                let nomeAluno = arrTurmas[i]['alunosTurma'][c]['nomeAluno'];
                                let acessibilidade = arrTurmas[i]['alunosTurma'][c]['possuiDeficiencia'];
                                arrTurmas[i]['alunosTurma'][c]['possuiDieta'] = 0;

                                html += '<tr>'+
                                            '<td><input name="ckb-alunos-t'+(i+1)+'[]" id="at'+(i+1)+'-'+codAluno+'" type="checkbox" value="'+codAluno+'"></td>'+
                                            '<td class="txt-green"><label for="at'+(i+1)+'-'+codAluno+'">'+codAluno+' - '+nomeAluno+'</label></td>';

                                            html += '<td>';
                                                if (acessibilidade == 1){
                                                    html += '<img style="width:20px" src="'+url_base_img+'/icons/acessibilidade.png">';
                                                } 
                                            html += '</td>';

                                            html += '<td>';
                                                if(alunosDieta){
                                                    jQuery.each(alunosDieta, function(index, item) {
                                                        if (codAluno == item.cod_eol_aluno){
                                                            arrTurmas[i]['alunosTurma'][c]['possuiDieta'] = 1;
                                                            arrTurmas[i]['alunosTurma'][c]['classificacaoDieta'] = item.classificacao.nome;
                                                            arrTurmas[i]['alunosTurma'][c]['uuid_dieta'] = item.uuid;
                                                            html += '<img style="width:20px" src="'+url_base_img+'/icons/dieta.png" x-on:click="renderToasts('+codAluno+')">';
                                                        }
                                                    });
                                                }
                                            html += '</td>';
                                            
                                html += '</tr>'; 

                            }

                        html += '</table>';
            
                html += '</div></div>';

            }
            localStorage.setItem("alunos-turmas-selecionadas-edit", JSON.stringify(arrTurmas));
            jQuery('#tab-turmas').html(html);
        } 

        adicionaAcoesCheckboxTurmaEdit();

        for(let i=0; arrTurmas.length > i; i++){
            let idsAlunoTurma = '';
            let alunosChecadosTurma = localStorage.getItem('alunos-selecionados-turma-'+(i+1));
            // Marca todos os alunos da turma 1, anteriormente marcados
            if(alunosChecadosTurma){
                idsAlunoTurma = alunosChecadosTurma.split(',');
                jQuery.each(idsAlunoTurma, function(index, item) {
                    jQuery("#at"+(i+1)+"-"+item).prop("checked", true);
                });
            } 
        }
    }

}


function retornaDataBrEdit(data){
    let arrData = data.split('-');
    return arrData[2] + '/' + arrData[1] + '/' + arrData[0];
}

function getCkbAlunosTurmaEdit(opc){
    let qtdCheckBoxAlunos = 0;
    let arrIdsChecked = [];
    let arrIdsUnChecked = [];
    let alunosSelecionados = [];
    document.querySelectorAll('input[type="checkbox"][name="ckb-alunos-t'+opc+'[]"').forEach(checkbox => {
        if (checkbox.checked) {
            arrIdsChecked.push(checkbox.id);
            alunosSelecionados.push(checkbox.value);
        } else {
            arrIdsUnChecked.push(checkbox.id);
        }
        qtdCheckBoxAlunos++;
    });
    return {idsMarcados:arrIdsChecked, idsDesmarcados:arrIdsUnChecked, qtdAlunos:qtdCheckBoxAlunos, alunosSelecionados:alunosSelecionados}
}

function verificarCheckBox(numTurma) {

    let idsAlunosTurma = jQuery('#alunos-selecionados-turma-'+numTurma+'-edit').val();
    
    if(idsAlunosTurma){
        let arrIdsAlunos = idsAlunosTurma.split(',');
        jQuery.each(arrIdsAlunos, function(index, item) {
            jQuery("#at"+numTurma+"-"+item).prop("checked", true);
        });
    }

}

function alunosTurmasSelecionadasEdit(){
    let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadasEdit');
    let arrTurmasSelecionadas = [];
    let idsTurma = [];
    let turmas = [];
    if(turmasSelecionadas){
        arrTurmasSelecionadas = turmasSelecionadas.split(',');
        for(let i=0; arrTurmasSelecionadas.length > i; i++){
            let arrDadosTurma = arrTurmasSelecionadas[i].split(' - ');
            let nome = arrDadosTurma[0]+' - '+arrDadosTurma[1]+' - '+arrDadosTurma[2];
            idsTurma.push(arrDadosTurma[3]);
            turmas.push({nomeTurma:nome, idTurma:arrDadosTurma[3]});
        }
    } 

    let dados = {
        arrIdsTurma: idsTurma,
        arrTurmas: turmas
    }

    fetch("/wp-json/agendamento/alunos-turma", {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(dados)
    }).then(response => response.json()).then(data => {
        if(data.success && data.data.length > 0){
            let dados = data.data;
            for(let i=0; dados.length > i; i++){
                localStorage.setItem('alunos-selecionados-turma-'+(i+1), '');
            }
            localStorage.setItem("alunos-turmas-selecionadas-edit", JSON.stringify(dados));
            renderizaTurmasEdit();
        } else {
            jQuery('#tab-turmas').html('<div class="col text-center">'+data[0]+'</div>');
        }
    }).catch(() => {
        console.log('Erro ao carregar pessoas');
    });
}

function renderizaDadosTabConfEdit(){

    let arrTurmas = retornaAlunosTurmaSelecionadasLocalStoragEdit();
    let html = '';

    if(arrTurmas && arrTurmas.length > 0){
        for(let i=0; arrTurmas.length > i; i++){
            let arrTurma = arrTurmas[i]['turma'];
            let nomeTurma = arrTurma['nomeTurma'];

            html += '<div class="col-md-6 mb-3">'+
                    '<p>Estudantes da Turma: <strong>'+nomeTurma+'</strong></p>'+
                    '<div class="card card-custom">'+
                    '<div class="card-header">Cód. EOL e Nome do Estudante</div>'+
                    '<ul class="list-group list-group-flush">';

                        let arrSelecionados = localStorage.getItem("alunos-selecionados-turma-"+(i+1)+"-edit");
                        if(arrSelecionados){

                            let arrSelected = arrSelecionados.split(',');
                            for(let c=0; arrSelected.length > c; c++){
                                let idSelected = arrSelected[c];
                                let listaAlunos = arrTurmas[i]['alunosTurma'];
                                jQuery.each(listaAlunos, function(index, item) {
                                    let codAluno = item['codigoAluno'];
                                    let nomeAluno = item['nomeAluno'];
                                    let possuiDeficiencia = item['possuiDeficiencia'];
                                    let possuiDieta = item['possuiDieta'];
                                    if(idSelected == codAluno){
                                        html += '<li class="list-group-item d-flex justify-content-between">'+
                                                '<span>'+codAluno+' - '+nomeAluno+'</span>'+
                                                '<div class="icons row">'+
                                                    '<span class="icon-btn col-6">';
                                                        if(possuiDeficiencia == 1){
                                                            html += '<img src="'+url_base_img+'/icons/acessibilidade.png">';
                                                        }
                                            html += '</span>'+
                                                    '<span class="icon-btn col-6">';
                                                        if(possuiDieta == 1){
                                                            html += '<img src="'+url_base_img+'/icons/dieta.png">';
                                                        }
                                                    
                                            html += '</span>'+
                                                '</div>'+
                                            '</li>';
                                    }
                                });
                            }

                        }
                    
            html += '</ul>'+
                    '</div>'+
                    '</div>';
        }
    }
    // CARREGA AS INFORMAÇÕES DA 3 TABELA
    jQuery("#exibe-turma-aluno-conf").html(html);

    let arrEducadores = retornaEducadoresEditLocalStorageEdit();
    let arrAcompanhantes = retornaAcompanhantesLocalStorageEdit();

    let htmlEdAc = '';

    if(arrEducadores && arrEducadores.length > 0){

        htmlEdAc += '<div class="col-md-6">'+
        '<p>Educadores e Acompanhantes</p>'+
        '<div class="card card-custom">'+
        '<div class="card-header">RF/CPF e Nome Completo</div>'+
        '<ul class="list-group list-group-flush">';

        arrEducadores.forEach(element => {
            let necessidades = element.necessidades;
            let dieta = element.dieta;
            htmlEdAc += '<li class="list-group-item d-flex justify-content-between">'+
                        '<span>'+element.rf+' - '+element.nome+' - '+retornaDataBrEdit(element.data_nascimento)+'</span>'+
                        '<div class="icons row">';
                        if(necessidades && necessidades.length > 0){
                            htmlEdAc += '<span class="icon-btn col-6">'+
                                    '<img src="'+url_base_img+'/icons/acessibilidade.png" data-toggle="tooltip" data-placement="right" title="'+necessidades+'">'+
                                '</span>';
                        }

                        if(dieta && dieta.length > 0){
                            htmlEdAc += '<span class="icon-btn col-6">'+
                                '<img src="'+url_base_img+'/icons/dieta.png" data-toggle="tooltip" data-placement="right" title="'+dieta+'">'+
                            '</span>';
                        }
                            
                        htmlEdAc += '</div></li>';
        });

        if(arrAcompanhantes && arrAcompanhantes.length > 0){
            arrAcompanhantes.forEach(element => {
                let necessidades = element.necessidades;
                let dieta = element.dieta;

                htmlEdAc += '<li class="list-group-item d-flex justify-content-between"><span>'+element.rf+' - '+element.nome+' - '+retornaDataBrEdit(element.data_nascimento)+'</span>'+
                '<div class="icons row">';

                if(necessidades && necessidades.length > 0){
                        htmlEdAc += '<span class="icon-btn col-6">'+
                            '<img src="'+url_base_img+'/icons/acessibilidade.png" data-toggle="tooltip" data-placement="right" title="'+necessidades+'">'+
                        '</span>';
                    }

                    if(dieta && dieta.length > 0){
                        htmlEdAc += '<span class="icon-btn col-6">'+
                            '<img src="'+url_base_img+'/icons/dieta.png" data-toggle="tooltip" data-placement="right" title="'+dieta+'">'+
                        '</span>';
                    }
                
                htmlEdAc += '</div></li>';
            });
        }
    
        htmlEdAc += '</ul></div></div>';
        // CARREGA AS INFORMAÇÕES DA 3 TABELA
        jQuery("#exibe-educadores-acompanhantes-conf").html(htmlEdAc);
    }

    let dataAgendamento = localStorage.getItem('agendamentoEdit');
    let qtdAlunos = localStorage.getItem('qtdAlunos');
    let qtdEducadores = localStorage.getItem('qtdEducadores');
    let qtdAcompanhantes = localStorage.getItem('qtdAcompanhantes');
    let qtdParticipantes = localStorage.getItem('qtdParticipantes');
    let qtdAlunosComAcessibilidade = localStorage.getItem('qtdAlunosComDeficiencia');
    let qtdAlunosComDieta = localStorage.getItem('qtdAlunosComDieta');
    
    // CARREGA A DATA DO AGENDAMENTO
    if(dataAgendamento){
        jQuery("#divDataAgendamento").html(dataAgendamento);
    }
    
    // CARREGA A QUANTIDADE DE ESTUDANTES
    jQuery("#qtdEstudantes").html(qtdAlunos);

    // CARREGA A QUANTIDADE DE ESTUDANTES COM DEFICIENCIA
    if(!qtdAlunosComAcessibilidade || qtdAlunosComAcessibilidade == 0){
        jQuery("#qtdAlunosComAcessibilidade").html('0');
    } else {
        jQuery("#qtdAlunosComAcessibilidade").html(qtdAlunosComAcessibilidade);
    }

    // CARREGA A QUANTIDADE DE ESTUDANTES COM DIETA
    if(!qtdAlunosComDieta || qtdAlunosComDieta == 0){
        jQuery("#qtdAlunosComDieta").html('0');
    } else {
        jQuery("#qtdAlunosComDieta").html(qtdAlunosComDieta);
    }

    // CARREGA A QUANTIDADE DE PROFESSORES
    jQuery("#qtdProfessores").html(qtdEducadores);

    // CARREGA A QUANTIDADE DE ACOMPANHANTES
    if(!qtdAcompanhantes || qtdAcompanhantes == 0){
        jQuery("#qtdOutros").html('0');
    } else {
        jQuery("#qtdOutros").html(qtdAcompanhantes);
    }

    // CARREGA O TOTAL DE PARTICIPANTES
    jQuery("#qtdParticipantes").html(qtdParticipantes);

}

function exibeQtdTotalParticipantes(){
    // CARREGA AS INFORMAÇÕES DA QUANTIDADE TOTAL DE PARTICIPAÇÃO
    let educadores = retornaEducadoresEditLocalStorageEdit();
    let acompanhantes = retornaAcompanhantesLocalStorageEdit();
    let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadasEdit');
    let qtdTotal = 0;
    if(turmasSelecionadas){
        arrTurmasSelecionadas = turmasSelecionadas.split(',');
        for(let i=0; arrTurmasSelecionadas.length > i; i++){
            let arrSelecionados = localStorage.getItem("alunos-selecionados-turma-"+(i+1)+"-edit");
            if(arrSelecionados){
                let arrIdsSelecionados = arrSelecionados.split(',');
                qtdTotal += arrIdsSelecionados.length;
            }
        }
    }

    qtdTotal = qtdTotal + educadores.length + acompanhantes.length;
    
    jQuery("#qtdTotalParticipantesEdit").html(qtdTotal);
}

function mascaraTelefoneAlpineEdit(input) {
    return input.length <= 14 ? '(99) 9999-9999' : '(99) 99999-9999';
}

function retornaEducadoresEditLocalStorage() {
    let arrEducadores = localStorage.getItem('educadoresEdit');
    let educadores = [];
    if(arrEducadores){
        educadores = JSON.parse(arrEducadores);
    }
    return educadores;
}

// BUSCA O EDUCADOR PELO RF DIGITADO
function buscaEducadorPorRFEdit(idInputRF, idNomeEducador, item){
    // CONTA A QUANTIDADE DE CARACTERES DO RF PARA SOLICITAR DADOS NA API
    let inputElement = document.getElementById(idInputRF);
    inputElement.addEventListener('input', function() {
        let textoDigitado = inputElement.value;
        let numeroDeCaracteres = textoDigitado.length;
        let maxLength = 7;
        if(numeroDeCaracteres == 7){

            let verificacaoDeRf = false;
            if(item == 0){
                let rf2 = jQuery("#idRFEdit2").val();
                if(textoDigitado == rf2){
                    verificacaoDeRf = true;
                    jQuery("#idRFEdit1").val('');
                }
            } else if(item == 1){
                let rf1 = jQuery("#idRFEdit1").val();
                if(textoDigitado == rf1){
                    verificacaoDeRf = true;
                    jQuery("#idRFEdit2").val('');
                }
            }

            if(verificacaoDeRf){
                Swal.fire({
                    icon: "info",
                    position: "center",
                    html: '<b>Este educador já foi adicionado </b>',
                    showConfirmButton: false,
                    timer: 3000
                });
                return;
            }

            Swal.fire({
                position: "center",
                title: '<small>Localizando educador informado...</small>',
                html: 'Aguarde um instante, estamos buscando os dados do educador informado.',
                showConfirmButton: false,
                imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                imageWidth: 100
            });

            let dados = {
                rf: textoDigitado
            }
            fetch("/wp-json/agendamento/acompanhante", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dados)
            }).then(response => response.json()).then(data => {
           
                if(data['success'] && data['data']['nome'] != null){
                    Swal.close();
                    let rf = textoDigitado;
                    let nome = data['data']['nome'];

                    let educadores = retornaEducadoresEditLocalStorageEdit();
                    
                    educadores[item].rf = rf;
                    educadores[item].nome = nome;
                    this.educadoresEdit = educadores;

                    localStorage.setItem('educadoresEdit', JSON.stringify(educadores));
                    
                    jQuery('#'+idNomeEducador).val(nome);
                    if(nome){
                        jQuery('#'+idNomeEducador).prop('readonly', true);
                    }

                } else {
                    Swal.close();
                    let educadores = retornaEducadoresEditLocalStorageEdit();
                    educadores[item].rf = textoDigitado;
                    educadores[item].nome = '';
                    this.educadoresEdit = educadores;
                    localStorage.setItem('educadoresEdit', JSON.stringify(educadores));
                    
                }
            }).catch(() => {
                console.log('Erro ao carregar educador');
            });
        } else if(numeroDeCaracteres < 7){
            jQuery('#'+idNomeEducador).val("");
            jQuery('#'+idNomeEducador).prop('readonly', false);
        }
        
        if (numeroDeCaracteres > maxLength) {
            inputElement.value = textoDigitado.slice(0, maxLength);
        }
    });
}


// BUSCA O EDUCADOR PELO RF DIGITADO
function buscaAcompanhantePorRFEdit(div){
    let idDiv = div.id;
    let numDigitado = jQuery(div).val();
    if(numDigitado){
        let item = idDiv.split('-');
        let posicao = item[1]-1;
        if(numDigitado.length == 7){
            let rf1 = jQuery("#idRF1").val();
            let rf2 = jQuery("#idRF2").val();
            let retVerificacao = false;

            if(numDigitado == rf1 || numDigitado == rf2){
                jQuery("#"+idDiv).val('');
                Swal.fire({
                    icon: "info",
                    position: "center",
                    html: '<b>Este educador já foi adicionado </b>',
                    showConfirmButton: false,
                    timer: 3000
                });
                return;
            } else {
                retVerificacao = verificaAcompanhantesEdit(numDigitado, idDiv, 1);
            }

            if(!retVerificacao){
                buscaEducadorAcompanhanteAPIEdit(numDigitado, "nomeAcompanhanteEdit-"+item[1], posicao, 'acompanhante');
            } 
        } 
    }

}

function buscaEducadorAcompanhanteAPIEdit(textoDigitado, idNomeEducador, keyArr, tipo){
    
    Swal.fire({
        position: "center",
        title: '<small>Localizando educador informado...</small>',
        html: 'Aguarde um instante, estamos buscando os dados do educador informado.',
        showConfirmButton: false,
        imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
        imageWidth: 100
    });

    fetch("/wp-json/agendamento/acompanhante", {
        method: 'POST', 
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({rf:textoDigitado})
    }).then(response => response.json()).then(data => {
        if(data['success'] && data['data']['nome'] != null){
            Swal.close();
            let rf = textoDigitado;
            let nome = data['data']['nome'];
            
            if(tipo == 'educador'){
                let arrEducadores = retornaEducadoresLocalStorageEdit();
                if(!arrEducadores || arrEducadores.length < 1){
                    arrEducadores = [
                        { rf: '', nome: '', data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'educador'},
                        { rf: '', nome: '', data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'educador'}
                    ]
                }
                arrEducadores[keyArr].rf = rf;
                arrEducadores[keyArr].nome = nome;
                localStorage.setItem('educadoresEdit', JSON.stringify(arrEducadores));

            } else {
                let arrAcompanhantes = retornaAcompanhantesLocalStorageEdit();
                if(!arrAcompanhantes || arrAcompanhantes.length < 1){
                    arrAcompanhantes = [
                        { rf:rf, nome:nome, data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'Acompanhante', justificativa:'' }
                    ]
                } else {
                    let arrRfs = [];
                    arrAcompanhantes.forEach(element => {
                        if(element.rf == rf){
                            arrRfs.push(rf);
                        }
                    });
                    if(arrRfs.length < 1){
                       arrAcompanhantes.push({ rf: rf, nome: nome, data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'Acompanhante', justificativa:'' });
                    }
                }
                localStorage.setItem('acompanhantesEdit', JSON.stringify(arrAcompanhantes));
            }

            jQuery('#'+idNomeEducador).val(nome);
            if(nome){
                jQuery('#'+idNomeEducador).prop('readonly', false);
            } else {
                jQuery('#'+idNomeEducador).prop('readonly', false);
            }
        } else {
            Swal.close();
        }
    }).catch(() => {
        console.log('Erro ao carregar acompanhante');
    });
}

function verificaAcompanhantesEdit(rf, idInput, opc){

    let verificacao = false;
    let rfCpf = [];
    let ids = [];

    for(let i=1; 5 > i; i++){
        let valorInputAcompanhante = jQuery("#idRFCpfEdit-"+i).val();
        if(valorInputAcompanhante){
            rfCpf.push({idDiv:"idRFCpfEdit-"+i,valInput:valorInputAcompanhante});
        }
    }

    if(rfCpf.length > opc){
        rfCpf.forEach(ele => {
            if(ele.valInput == rf){
                ids.push(rf);
            }
        });
    }

    if(ids.length > 1 || (opc == 0 && ids.length == 1)){
        jQuery("#"+idInput).val('');
        Swal.fire({
            icon: "info",
            position: "center",
            html: '<b>Este educador já foi adicionado </b>',
            showConfirmButton: false,
            timer: 3000 
        });
        verificacao = true;
    }
 
    return verificacao;

}
