const url_base = window.location.origin;// Exemplo: "www.exemplo.com"
const caminho = window.location.pathname; // Exemplo: "/pagina"
const url_base_img = url_base+'/wp-content/themes/roleagro/src/Views/assets/img';

var imported = document.createElement('script');
imported.src = url_base+'/wp-content/themes/roleagro/src/Views/assets/js/calendario.js'; 
document.head.appendChild(imported); 

jQuery(document).ready(function($) {

    if (caminho == '/agendamento/'){
        // Aplica mascara no campo de data
        // $("#dataNascimentoEdu1").mask("99/99/9999").val("dd/mm/aaaa");

        let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');
        let arrTurmasSelecionadas = [];
        if(turmasSelecionadas){
            arrTurmasSelecionadas = turmasSelecionadas.split(',');
            for(let i=0; arrTurmasSelecionadas.length > i; i++){
                let turma = arrTurmasSelecionadas[i].split(' - ');
                $('#turma-'+turma[3]).prop('checked', true);
            }
        } 

        renderizaTurmas();

        // CONTA A QUANTIDADE DE CARACTERES DO RF PARA SOLICITAR DADOS NA API
        buscaEducadorPorRF('idRF1', 'nomeEducador1', 0);
        buscaEducadorPorRF('idRF2', 'nomeEducador2', 1);
        // CARREGA AS INFORMAÇÕES DA 3 TABELA
        renderizaDadosTabConf();
    } 

});

// BUSCA O EDUCADOR PELO RF DIGITADO
function buscaEducadorPorRF(idInputRF, idNomeEducador, item){
    // CONTA A QUANTIDADE DE CARACTERES DO RF PARA SOLICITAR DADOS NA API
    let inputElement = document.getElementById(idInputRF);
    inputElement.addEventListener('input', function() {
        let textoDigitado = inputElement.value;
        let numeroDeCaracteres = textoDigitado.length;
        let maxLength = 7;
        if(numeroDeCaracteres == 7){

            let verificacaoDeRf = false;
            if(item == 0){
                let rf2 = jQuery("#idRF2").val();
                if(textoDigitado == rf2){
                    verificacaoDeRf = true;
                    jQuery("#idRF1").val('');
                }
            } else if(item == 1){
                let rf1 = jQuery("#idRF1").val();
                if(textoDigitado == rf1){
                    verificacaoDeRf = true;
                    jQuery("#idRF2").val('');
                }
            } 

            let retVerificacao = verificaAcompanhantes(textoDigitado, idInputRF, 0);

            if(verificacaoDeRf || retVerificacao){
                Swal.fire({
                    icon: "info",
                    position: "center",
                    html: '<b>Este educador já foi adicionado </b>',
                    showConfirmButton: false,
                    timer: 3000
                });
                return;
            }

            buscaEducadorAcompanhanteAPI(textoDigitado, idNomeEducador, item, 'educador');

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
function buscaAcompanhantePorRF(div){
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
                retVerificacao = verificaAcompanhantes(numDigitado, idDiv, 1);
            }

            if(!retVerificacao){
                buscaEducadorAcompanhanteAPI(numDigitado, "nomeAcompanhante-"+item[1], posicao, 'acompanhante');
            } 
        }
    }

}

function buscaEducadorAcompanhanteAPI(textoDigitado, idNomeEducador, keyArr, tipo){
    
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
                let arrEducadores = retornaEducadoresLocalStorage();
                if(!arrEducadores || arrEducadores.length < 1){
                    arrEducadores = [
                        { rf: '', nome: '', data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'educador'},
                        { rf: '', nome: '', data_nascimento:'', celular: '', dieta:'', necessidades:'', tipo: 'educador'}
                    ]
                }
                arrEducadores[keyArr].rf = rf;
                arrEducadores[keyArr].nome = nome;
                localStorage.setItem('educadores', JSON.stringify(arrEducadores));

            } else {
                let arrAcompanhantes = retornaAcompanhantesLocalStorage();
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
                localStorage.setItem('acompanhantes', JSON.stringify(arrAcompanhantes));
            }

            jQuery('#'+idNomeEducador).val(nome);
            if(nome){
                jQuery('#'+idNomeEducador).prop('readonly', true);
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

function verificaAcompanhantes(rf, idInput, opc){

    let verificacao = false;
    let rfCpf = [];
    let ids = [];

    for(let i=1; 5 > i; i++){
        let valorInputAcompanhante = jQuery("#idRFCpf-"+i).val();
        if(valorInputAcompanhante){
            rfCpf.push({idDiv:"idRFCpf-"+i,valInput:valorInputAcompanhante});
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

function retornaEducadoresLocalStorage() {
    let arrEducadores = localStorage.getItem('educadores');
    let educadores = [];
    if(arrEducadores){
        educadores = JSON.parse(arrEducadores);
    }
    return educadores;
}


function retornaAcompanhantesLocalStorage() {
    let arrAcompanhantes = localStorage.getItem('acompanhantes');
    let acompanhantes = [];
    if(arrAcompanhantes){
        acompanhantes = JSON.parse(arrAcompanhantes);
    }
    return acompanhantes;
}

function mascaraTelefoneAlpine(input) {
    return input.length <= 14 ? '(99) 9999-9999' : '(99) 99999-9999';
}

function validarEmailAgendamento(email) {
  const regex = /\S+@\S+\.\S+/; // 
  return regex.test(email);
}

function agendamentoForm() {
    
    return {
        step: 1,
        stepHistory: 1,
        loading: false,
        alunos: [],
        alunosSelecionados: [],
        educadores: [
            { rf: '', nome: '', data_nascimento: '', celular: '', dieta: '', necessidades: '', tipo: 'Educador' },
            { rf: '', nome: '', data_nascimento: '', celular: '', dieta: '', necessidades: '', tipo: 'Educador' }
        ],
        acompanhantes: [],
        dados: {
        },
        get progresso() {
            return (this.step / 4) * 100;
        },
        carregarDados() {
            const agendamento = localStorage.getItem('agendamento');
            if(agendamento) {
                this.dados = JSON.parse(agendamento);
            } else {
                let dataAge = jQuery("#arrDatasPermitidas").val();
                this.dados.dataAgendamento = dataAge;
            }
            localStorage.setItem('agendamento', JSON.stringify(this.dados));

            const idUser = jQuery('#idUser').val();
            this.buscaAlunosDietasSigPae(idUser);

            const step = localStorage.getItem('etapa_atual');
 
            if (step) {
                this.step = parseInt(step);
            }

            if (this.step === 2) {
                this.carregarEducadores();
            }
 
            if (this.step === 3) {
                this.carregarAlunos();
            }
 
            if (agendamento) {
                this.dados = JSON.parse(agendamento);
            }
        },

        salvarDados() {
            localStorage.setItem('educadores', JSON.stringify(this.educadores));
            localStorage.setItem('agendamento', JSON.stringify(this.dados));
        },
        
        adicionarAcompanhante() {

            this.acompanhantes.push({
                rf: '',
                nome: '',
                data_nascimento: '',
                celular: '',
                dieta: '',
                necessidades: '',
                tipo: 'Acompanhante',
                justificativa: ''
            });

        },
        removerAcompanhante(index) {
            let arrAcompanhantes = retornaAcompanhantesLocalStorage();
            arrAcompanhantes.splice(index, 1);
            this.acompanhantes.splice(index, 1);
            localStorage.setItem('acompanhantes', JSON.stringify(arrAcompanhantes));
        },

        proximo() {
            if (this.validar()) { //this.validar()

                this.salvarDados();
 
                if (this.step < 4) {
                    this.step++;
                }

                if (this.step === 2) {
                     let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');
                     
                     if(!turmasSelecionadas){
                       // Limpa o localStorage de turmas selecionadas
                       localStorage.removeItem('alunos-turmas-selecionadas');
                       // Limpa o localStorage de alunos selecionados
                       for(let i=0; 10 > i; i++){
                            localStorage.removeItem('alunos-selecionados-turma-'+i);
                       }

                        jQuery('#tab-turmas').html('<div class="col text-center">Carregando lista de alunos...</div>');
            
                        Swal.fire({
                            position: "center",
                            icon: "info",
                            html: 'Selecione ao menos uma turma para renderizar a lista de alunos, por gentileza!',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        this.step = 2;
                        this.anterior();
                     } else {
                        Swal.fire({
                            position: "center",
                            title: '<small>Carregando estudantes das turmas...</small>',
                            html: 'Aguarde um instante, estamos buscando os estudantes matriculados nas turmas selecionadas.',
                            showConfirmButton: false,
                            imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                            imageWidth: 100
                        });
                        this.carregarAlunos();
                    }
                    localStorage.setItem('etapa_atual', this.step);
                } 

                if(this.step === 3){
                    
                    let qtdMin = jQuery('#qtdParticipantesMin').val();
                    let qtdMax = jQuery('#qtdParticipantesMax').val();
                    
                    let educadores = retornaEducadoresLocalStorage();
                    let acompanhantes = retornaAcompanhantesLocalStorage();

                    let arrAlunosTurmas = JSON.parse(localStorage.getItem('alunos-turmas-selecionadas'));

                    let idsTurmas = localStorage.getItem('idsTurmasSelecionadas');
                    let arrIds = idsTurmas.split(',');
                    let qtdAlunos = 0;

                    let qtdAlunosComDeficiencia = 0;
                    let qtdAlunosComDieta = 0;

                    for(let i=0; arrIds.length > i; i++){

                        let alunosTurma = localStorage.getItem('alunos-selecionados-turma-'+(i+1));
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
                   
                    let qtdGeral = educadores.length + acompanhantes.length + qtdAlunos;
                    
                    if(qtdGeral >= qtdMin && qtdGeral <= qtdMax){

                        let idRoteiro = jQuery('#idRoteiro').val();
                         localStorage.setItem('idRoteiro', idRoteiro);

                        let idUser = jQuery('#idUser').val();
                        localStorage.setItem('idUser', idUser);

                        let nomeRoteiro = jQuery('#nomeRoteiro').val();
                        localStorage.setItem('nomeRoteiro', nomeRoteiro);
                       
                        localStorage.setItem('qtdAlunos', qtdAlunos);

                        localStorage.setItem('qtdAlunosComDeficiencia', qtdAlunosComDeficiencia);
                        localStorage.setItem('qtdAlunosComDieta', qtdAlunosComDieta);

                        localStorage.setItem('qtdEducadores', educadores.length);
                        localStorage.setItem('qtdAcompanhantes', acompanhantes.length);
                        localStorage.setItem('qtdParticipantes', qtdGeral);

                        this.step = 3;
                        // CARREGA AS INFORMAÇÕES DA 3 TABELA
                        renderizaDadosTabConf();
                    } else if(qtdGeral < qtdMin){
                        Swal.fire({
                            icon: "info",
                            position: "center",
                            html: '<b>Atenção! A inscrição não atinge o mínimo de '+qtdMin+' participantes para realizar o agendamento!</b>',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        this.step = 2;
                        // this.anterior();
                    } else if(qtdGeral > qtdMax){
                        Swal.fire({
                            icon: "info",
                            position: "center",
                            html: '<b>Atenção! A inscrição ultrapassou o máximo de '+qtdMax+' participantes para realizar o agendamento!</b>',
                            showConfirmButton: false,
                            timer: 4000
                        });
                        this.step = 2;
                        // this.anterior();
                    }
                    localStorage.setItem('etapa_atual', this.step);
                }
            }
        },

        anterior() {
            if (this.step > 1) {
                this.step--;
                localStorage.setItem('etapa_atual', this.step);
            } else if (this.step == 1) {
                this.stepHistory--;
            }
        },
        acionaBtnVoltar(){
            
            this.anterior();
            
            if (this.stepHistory < 1) {
                window.history.back();
            }
        },
        alteraDataSelect(){
           
            let dataSelecionadaCalendario = localStorage.getItem('dataSelecionadaCalendario');
            let valorSelecionado = jQuery("#select-datas-disponiveis").val();
            
            // Remove a data selecionada anterior, e marca a data selcionada
            jQuery('.day[data-date="' + dataSelecionadaCalendario + '"]').removeClass('selected');
            jQuery('.day[data-date="' + valorSelecionado + '"]').addClass('selected');
            
            let agendamento = JSON.parse(localStorage.getItem('agendamento'));
            agendamento.dataAgendamento = valorSelecionado;
            localStorage.setItem('agendamento', JSON.stringify(agendamento));
            localStorage.setItem('dataSelecionadaCalendario', valorSelecionado);

        },
        validar() {

            if (this.step === 1){

                if (!this.dados.nomeResponsavel) {
                    Swal.fire({icon: "info",html:'<b>Preencha o nome do responsável, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if (!this.dados.telefoneUe || this.dados.telefoneUe.length < 14 ) {
                    Swal.fire({icon: "info",html:'<b>Preencha o telefone da UE, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if (!validarEmailAgendamento(this.dados.emailUe)) {
                    Swal.fire({icon: "info",html:'<b>Preencha o e-mail da UE, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if (!this.dados.dataAgendamento ) {
                    let dataSelecionadaCalendario = localStorage.getItem('dataSelecionadaCalendario');
                    if(dataSelecionadaCalendario){
                        this.dados.dataAgendamento = dataSelecionadaCalendario;
                    } else {
                        Swal.fire({icon: "info",html:'<b>Selecione a data do agendamento, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    }
                }

            } else if (this.step === 2) {

                if(!this.educadores[0].rf || this.educadores[0].rf.length != 7){
                    Swal.fire({icon: "info",html:'<b>Preencha o RF do primeiro educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                let nomeEducador01 = document.getElementById("nomeEducador1");
                let nomeEducador1 = nomeEducador01.value;

                if (nomeEducador1.length < 1 ) {
                    Swal.fire({icon: "info",html:'<b>Preencha o nome do primeiro educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                } else {
                    this.educadores[0].nome = nomeEducador1;
                }

                if (!this.educadores[1].rf || this.educadores[1].rf.length != 7) {
                    Swal.fire({icon: "info",html:'<b>Preencha o RF do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if (!this.educadores[0].data_nascimento) {
                    Swal.fire({icon: "info",html:'<b>Preencha corretamente a data de nascimento do primero educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if ((!this.educadores[0].celular || this.educadores[0].celular.length < 14 )) {
                    Swal.fire({icon: "info",html:'<b>Preencha o telefone do primero educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                let nomeEducador02 = document.getElementById("nomeEducador2");
                let nomeEducador2 = nomeEducador02.value;
                if (nomeEducador2.length < 1 ) {
                    Swal.fire({icon: "info",html:'<b>Preencha o nome do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                } else {
                    this.educadores[1].nome = nomeEducador2;
                }

                if (!this.educadores[1].data_nascimento ) {
                    Swal.fire({icon: "info",html:'<b>Preencha corretamente a data de nascimento do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }

                if ((!this.educadores[1].celular || this.educadores[1].celular.length < 14 )) {
                    Swal.fire({icon: "info",html:'<b>Preencha o telefone do segundo educador, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                    return false;
                }


                let idRFCpf1 = document.getElementById("idRFCpf-1");
                if(idRFCpf1){

                    let arrAcompanhantes1 = retornaAcompanhantesLocalStorage();

                    if(!idRFCpf1.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].rf = idRFCpf1.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes1);
                    }

                    let nomeAcompanhante1 = document.getElementById("nomeAcompanhante-1");
                    if(!nomeAcompanhante1.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].nome = nomeAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes1);
                    }

                    let dataNascimentoAcompanhante1 = document.getElementById("dataNascimentoAcompanhante-1");
                    if (!dataNascimentoAcompanhante1.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].data_nascimento = dataNascimentoAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes1);
                    }

                    let celAcompanhante1 = document.getElementById("celAcompanhante-1");
                    if (!celAcompanhante1.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].celular = celAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes1);
                    }

                    let dietaAcompanhante1 = document.getElementById("dietaAcompanhante-1");
                    if (dietaAcompanhante1.value) {
                        arrAcompanhantes1[0].dieta = dietaAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes1);
                    }

                    let necessidadesAcompanhante1 = document.getElementById("necessidadesAcompanhante-1");
                    if (necessidadesAcompanhante1.value) {
                        arrAcompanhantes1[0].dieta = necessidadesAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes1);
                    }

                    let justificativaAcompanhante1 = document.getElementById("justificativaAcompanhante-1");
                    if (!justificativaAcompanhante1.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes1[0].justificativa = justificativaAcompanhante1.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes1);
                    }
                } 

                let idRFCpf2 = document.getElementById("idRFCpf-2");
                if(idRFCpf2){
                   
                    let arrAcompanhantes2 = retornaAcompanhantesLocalStorage();
                    let tamArr2 = arrAcompanhantes2.length;
                    if(tamArr2 == 1){
                        arrAcompanhantes2.push({"rf":"","nome":"","data_nascimento":"","celular":"","dieta":"","necessidades":"","tipo":"Acompanhante","justificativa":""});
                    }

                    if(!idRFCpf2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].rf = idRFCpf2.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes2);
                    }

                    let nomeAcompanhante2 = document.getElementById("nomeAcompanhante-2");
                    if(!nomeAcompanhante2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].nome = nomeAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes2);
                    }

                    let dataNascimentoAcompanhante2 = document.getElementById("dataNascimentoAcompanhante-2");
                    if (!dataNascimentoAcompanhante2.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].data_nascimento = dataNascimentoAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes2);
                    }

                    let celAcompanhante2 = document.getElementById("celAcompanhante-2");
                    if (!celAcompanhante2.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].celular = celAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes2);
                    }

                    let dietaAcompanhante2 = document.getElementById("dietaAcompanhante-2");
                    if (dietaAcompanhante2.value) {
                        arrAcompanhantes2[1].dieta = dietaAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes2);
                    }

                    let necessidadesAcompanhante2 = document.getElementById("necessidadesAcompanhante-2");
                    if (necessidadesAcompanhante2.value) {
                        arrAcompanhantes2[1].dieta = necessidadesAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes2);
                    }

                    let justificativaAcompanhante2 = document.getElementById("justificativaAcompanhante-2");
                    if (!justificativaAcompanhante2.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa do segundo acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes2[1].justificativa = justificativaAcompanhante2.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes2);
                    }
                } 

                let idRFCpf3 = document.getElementById("idRFCpf-3");
                if(idRFCpf3){

                    let arrAcompanhantes3 = retornaAcompanhantesLocalStorage();
                    let tamArr3 = arrAcompanhantes3.length;
                    if(tamArr3 == 2){
                        arrAcompanhantes3.push({"rf":"","nome":"","data_nascimento":"","celular":"","dieta":"","necessidades":"","tipo":"Acompanhante","justificativa":""});
                    }

                    if(!idRFCpf2.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o RF ou CPF do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].rf = idRFCpf2.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes3);
                    }

                    let nomeAcompanhante3 = document.getElementById("nomeAcompanhante-3");
                    if(!nomeAcompanhante3.value){
                        Swal.fire({icon: "info",html:'<b>Preencha o nome do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].nome = nomeAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes3);
                    }

                    let dataNascimentoAcompanhante3 = document.getElementById("dataNascimentoAcompanhante-3");
                    if (!dataNascimentoAcompanhante3.value) {
                        Swal.fire({icon: "info",html:'<b>Preencha a data de nascimento do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].data_nascimento = dataNascimentoAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes3);
                    }

                    let celAcompanhante3 = document.getElementById("celAcompanhante-3");
                    if (!celAcompanhante3.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha o número de celular do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].celular = celAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes3);
                    }

                    let dietaAcompanhante3 = document.getElementById("dietaAcompanhante-3");
                    if (dietaAcompanhante3.value) {
                        arrAcompanhantes3[2].dieta = dietaAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes3);
                    }

                    let necessidadesAcompanhante3 = document.getElementById("necessidadesAcompanhante-3");
                    if (necessidadesAcompanhante3.value) {
                        arrAcompanhantes3[2].dieta = necessidadesAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes', arrAcompanhantes3);
                    }

                    let justificativaAcompanhante3 = document.getElementById("justificativaAcompanhante-3");
                    if (!justificativaAcompanhante3.value ) {
                        Swal.fire({icon: "info",html:'<b>Preencha a justificativa do terceiro acompanhante, por gentileza!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    } else {
                        arrAcompanhantes3[2].justificativa = justificativaAcompanhante3.value;
                        this.salvaLocalStorage('acompanhantes',arrAcompanhantes3);
                    }
                }

                // Valida os alunos
                let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');
                if(turmasSelecionadas){
                    let alunosSelecionados = localStorage.getItem('alunos-selecionados-turma-1');
                    if(!alunosSelecionados){
                        Swal.fire({icon: "info",html:'<b>Não existe alunos selecionados!</b>',showConfirmButton: false,timer: 2000});
                        return false;
                    }
                }
            }

            return true;
        },
        salvaLocalStorage(nome, dados){
            localStorage.setItem(nome, JSON.stringify(dados));
        },
        carregarAlunos() {
            let qtdTurmasSelecionadas = 0;
            let arrAlunosTurma = localStorage.getItem('alunos-turmas-selecionadas');
            if(arrAlunosTurma){

                let arrTurmasSelecionadas = [];
                let idsTurma = [];
                let turmas = [];

                let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');
                if(turmasSelecionadas){
                    qtdTurmasSelecionadas = turmasSelecionadas.split(' - ').length;
                }

                let strIdsTurma = '';
                let arrTurmas = JSON.parse(arrAlunosTurma);
       
                arrTurmas.forEach(element => {
                    let nometurma = element['turma'].nomeTurma;
                    let idTurma = element['turma'].idTurma;

                    strIdsTurma += nometurma+' - '+idTurma+', ';
                });
                
                strIdsTurma = strIdsTurma.slice(0, -2);

                if(strIdsTurma != turmasSelecionadas){
                    arrTurmasSelecionadas = turmasSelecionadas.split(',');
                    for(let i=0; arrTurmasSelecionadas.length > i; i++){
                        let arrDadosTurma = arrTurmasSelecionadas[i].split(' - ');
                        let nome = arrDadosTurma[0]+' - '+arrDadosTurma[1]+' - '+arrDadosTurma[2];
                        idsTurma.push(arrDadosTurma[3]);
                        turmas.push({nomeTurma:nome, idTurma:arrDadosTurma[3]});
                    }

                    let dados = {
                        arrIdsTurma: idsTurma,
                        arrTurmas: turmas
                    }

                    this.requisitaAlunosAPI(dados);
                }
                renderizaTurmas();
            } else {
                let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');

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

                this.requisitaAlunosAPI(dados);
            }
            setTimeout(() => {Swal.close()}, 2000);
        },
        carregarEducadores() {

            let arrEducadores = retornaEducadoresLocalStorage();
            let tamArrEdu = arrEducadores.length;
            if(arrEducadores && tamArrEdu > 0){
                for(let i=0; tamArrEdu > i; i++){
                    this.educadores[i].rf = arrEducadores[i].rf;
                    this.educadores[i].nome = arrEducadores[i].nome;
                    this.educadores[i].data_nascimento = arrEducadores[i].data_nascimento;
                }
            }

            let arrAcompanhantes = retornaAcompanhantesLocalStorage();
            let tamArrAcomp = arrAcompanhantes.length;
            if(arrAcompanhantes && tamArrAcomp > 0){
                for(let i=0; tamArrAcomp > i; i++){
                    this.acompanhantes[i] = {"rf":"","nome":"","data_nascimento":"","celular":"","dieta":"","necessidades":"","tipo":"Acompanhante","justificativa":""};
                    this.acompanhantes[i].rf = arrAcompanhantes[i].rf;
                    this.acompanhantes[i].nome = arrAcompanhantes[i].nome;
                    this.acompanhantes[i].data_nascimento = arrAcompanhantes[i].data_nascimento;
                    this.acompanhantes[i].justificativa = arrAcompanhantes[i].justificativa;
                }
            }
        },
        requisitaAlunosAPI(dados){
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
                        localStorage.setItem("alunos-turmas-selecionadas", JSON.stringify(dados));
                        renderizaTurmas();
                        Swal.close();
                    } else {
                        jQuery('#tab-turmas').html('<div class="col text-center">Não foi encontrado alunos</div>');
                        Swal.close();
                    }
                }).catch(() => {
                    console.log('Erro ao carregar pessoas');
                });
        },
        buscaAlunosComDietaAPI(idUser){
            let alunosDieta = JSON.parse(localStorage.getItem("alunos-com-dieta"));
            if(!alunosDieta){
                fetch("/wp-json/alunos-ue/dieta", {
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({idUser: idUser})
                }).then(response => response.json()).then(data => {
                    
                    if(data.success){
                        let arrAlunosDieta = [];
                        let arrAlunos = JSON.parse(data.data);
                        jQuery.each(arrAlunos.solicitacoes, function(index, item) {
                            let turmaAluno = item.serie[0];
                            if(turmaAluno == '6' && item.classificacao_dieta_ativa !== null ){
                                arrAlunosDieta.push(item);
                            }
                        });
                        if(arrAlunosDieta.length > 0){
                            localStorage.setItem("alunos-com-dieta", JSON.stringify(arrAlunosDieta));
                        }
                    }
                }).catch(() => {
                    console.log('Erro ao carregar os alunos com dietas');
                });
            }
        },
        buscaAlunosDietasSigPae(idUser){
            console.log('buscando as dietas...');
            // Cria o objeto FormData para enviar os dados
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
                            localStorage.setItem("alunos-com-dieta", JSON.stringify(arrAlunosDieta));
                        } else {
                            console.log('Sem alunos com dietas');
                        }
                } else {
                    console.log('Erro:', data.data);
                }
            }).catch(error => {
                console.log('Erro na requisição:', error);
            });

        },
        toggleAluno(event) {
 
            const id = parseInt(event.target.value);
 
            if (event.target.checked) {
                const aluno = this.alunos.find(a => a.id === id);
 
                if (aluno) {
                    this.dados.alunosData.push(aluno);
                }
            } else {
             this.dados.alunosData.filter(a => a.id !== id);
            }

        },
        enviar() {
            this.salvarDados();
            localStorage.removeItem('agendamento');
            localStorage.removeItem('etapa_atual');
            // Aqui você pode fazer um fetch para enviar via AJAX para o WordPress
        },
        salvarAgendamento(){

            Swal.fire({
                position: "center",
                title: '<small>Aguarde um momento...</small>',
                html: 'As informações estão sendo salvas.',
                showConfirmButton: false,
                imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                imageWidth: 100
            });

            let dadosAgendamento = JSON.parse(localStorage.getItem('agendamento'));
            let arrAlunosTurmas = JSON.parse(localStorage.getItem('alunos-turmas-selecionadas'));

            let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');
            let arrayTurmas = turmasSelecionadas.split(',');
            let arrTurmas = [];
            let i = 0;
            arrayTurmas.forEach(element => {
                let Turma = element.split(' - ');
                let idTurma = Turma[3];
                let nomeTurma = Turma[0]+' - '+Turma[1]+' - '+Turma[2];

                let strIdsAlunos = localStorage.getItem('alunos-selecionados-turma-'+(i+1));
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

            let idRoteiro = localStorage.getItem('idRoteiro');
            let idUser = localStorage.getItem('idUser');
            let nomeRoteiro = localStorage.getItem('nomeRoteiro');
            let educadores = retornaEducadoresLocalStorage();
            let acompanhantes = retornaAcompanhantesLocalStorage();

            let dados = {
                idUser: idUser,
                idRoteiro: idRoteiro,
                nomeRoteiro: nomeRoteiro,
                dadosAgendamento: dadosAgendamento,
                dadosTurmas: arrTurmas,
                dadosEducadores: educadores,
                dadosAcompanhantes: acompanhantes
            }
            
            fetch("/wp-json/agendamento/salvar", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dados)
            }).then(response => response.json()).then(data => {
                if(data['success']){
                    Swal.close();
                    Swal.fire({icon: "success",html:'<b>Inscrição realizada com sucesso!</b>',showConfirmButton: false});
                    removeItemLocalStorage();
                    setTimeout(() => {
                        window.location.href = '/meus-agendamentos';
                    },2000);
                }
            }).catch(() => {
                console.log('Erro ao carregar pessoas');
            });
        },
        verificaData(valor){
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

function removeItemLocalStorage(){
    // Limpa localStorage dos participantes
    for(let i=0; 10 > i; i++){
        localStorage.removeItem('alunos-selecionados-turma-'+(i+1));
    }
    localStorage.removeItem('agendamento');
    localStorage.removeItem('etapa_atual');
    localStorage.removeItem('idsTurmasSelecionadas');
    localStorage.removeItem('alunos-turmas-selecionadas');
    localStorage.removeItem('educadores');
    localStorage.removeItem('acompanhantes');
    localStorage.removeItem('idUser');
    localStorage.removeItem('idRoteiro');
    localStorage.removeItem('nomeRoteiro');
    localStorage.removeItem('etapa_atual');
    localStorage.removeItem('alunos-com-dieta');

    // Limpa localStorage de quantidades
    localStorage.removeItem('qtdAlunos');
    localStorage.removeItem('qtdAcompanhantes');
    localStorage.removeItem('qtdEducadores');
    localStorage.removeItem('qtdParticipantes');
    localStorage.removeItem('qtdAlunosComDeficiencia');
    localStorage.removeItem('qtdAlunosComDieta');
    localStorage.removeItem('qtdMaxParticipantes');
    localStorage.removeItem('qtdMinParticipantes');

    localStorage.removeItem('dataSelecionadaCalendario');
}

function renderizaTurmas(){
    let arrTurmas = JSON.parse(localStorage.getItem("alunos-turmas-selecionadas"));
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
            localStorage.setItem("alunos-turmas-selecionadas", JSON.stringify(arrTurmas));
            jQuery('#tab-turmas').html(html);
        } 

        adicionaAcoesCheckboxTurma();

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

function renderToasts(codAluno){

    let html = '';
    let alunosDieta = JSON.parse(localStorage.getItem("alunos-com-dieta"));

    jQuery.each(alunosDieta, function(index, item) {
        if (codAluno == item.cod_eol_aluno){
            html += '<br><img style="width:20px" src="'+url_base_img+'/icons/dieta.png">&nbsp;&nbsp;<b>'+item.nome_aluno+':</b><br>Dieta Especial - '+item.classificacao.nome+'<br>&nbsp;';
        }
    });

    Swal.fire({
        position: "center",
        html: html,
        width: 450,
        showCloseButton: true,
        showConfirmButton: false,
    });

}

function exibeModalDieta(nome, classificacaoDieta){

    Swal.fire({
        position: "center",
        html: '<br><img style="width:20px" src="'+url_base_img+'/icons/dieta.png">&nbsp;&nbsp;<b>'+nome+':</b><br>Dieta Especial - '+classificacaoDieta+'<br>&nbsp;',
        width: 450,
        showCloseButton: true,
        showConfirmButton: false,
    });

}

function renderizaDadosTabConf(){

    let arrTurmas = JSON.parse(localStorage.getItem("alunos-turmas-selecionadas"));
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

                        let arrSelecionados = localStorage.getItem("alunos-selecionados-turma-"+(i+1));
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
                                                            html += '<img src="'+url_base_img+'/icons/dieta.png" x-on:click="renderToasts('+codAluno+')">';
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

    let arrEducadores = JSON.parse(localStorage.getItem("educadores"));
    let arrAcompanhantes = JSON.parse(localStorage.getItem("acompanhantes"));

    let htmlEdAc = '';

    if(arrEducadores && arrEducadores.length > 0){

        htmlEdAc += '<div class="col-md-6">'+
        '<p>Educadores e Acompanhantes</p>'+
        '<div class="card card-custom">'+
        '<div class="card-header">RF/CPF e Nome Completo</div>'+
        '<ul class="list-group list-group-flush">';

        arrEducadores.forEach(element => {
            htmlEdAc += '<li class="list-group-item d-flex justify-content-between">'+
                        '<span>'+element.rf+' - '+element.nome+' - '+retornaDataBr(element.data_nascimento)+'</span>'+
                        '<div class="icons row">';
                        if(element.necessidades){
                            htmlEdAc += '<span class="icon-btn col-6">'+
                                    '<img src="'+url_base_img+'/icons/acessibilidade.png">'+
                                '</span>';
                        }

                        if(element.dieta){
                            htmlEdAc += '<span class="icon-btn col-6">'+
                                '<img src="'+url_base_img+'/icons/dieta.png">'+
                            '</span>';
                        }
                            
                        htmlEdAc += '</div></li>';
        });

        if(arrAcompanhantes && arrAcompanhantes.length > 0){
            arrAcompanhantes.forEach(element => {
                htmlEdAc += '<li class="list-group-item">'+element.rf+' - '+element.nome+' - '+retornaDataBr(element.data_nascimento)+'</li>';
            });
        }
    
        htmlEdAc += '</ul></div></div>';
        // CARREGA AS INFORMAÇÕES DA 3 TABELA
        jQuery("#exibe-educadores-acompanhantes-conf").html(htmlEdAc);
    }

    let dadosAgendamento = JSON.parse(localStorage.getItem('agendamento'));
    let qtdAlunos = localStorage.getItem('qtdAlunos');
    let qtdEducadores = localStorage.getItem('qtdEducadores');
    let qtdAcompanhantes = localStorage.getItem('qtdAcompanhantes');
    let qtdParticipantes = localStorage.getItem('qtdParticipantes');
    let qtdAlunosComAcessibilidade = localStorage.getItem('qtdAlunosComDeficiencia');
    let qtdAlunosComDieta = localStorage.getItem('qtdAlunosComDieta');
    
    // CARREGA A DATA DO AGENDAMENTO
    if(dadosAgendamento){
        jQuery("#dataAgendamento").html(retornaDataBr(dadosAgendamento['dataAgendamento']));
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

function retornaDataBr(data){
    if(!data) { 
        return; 
    } else {
        let arrData = data.split('-');
        return arrData[2] + '/' + arrData[1] + '/' + arrData[0];
    }
}

function retornaDataEn(data){
    if(!data) { 
        return; 
    } else {
        let arrData = data.split('/');
        return arrData[2] + '-' + arrData[1] + '-' + arrData[0];
    }
}

function getCheckboxListagemAlunos(){
    let qtdCheckBoxTurmas = 0;
    let arrIdsChecked = [];
    let arrIdsUnChecked = [];
    let turmasSelecionadas = [];
    document.querySelectorAll('input[type="checkbox"][name="selecao-alunos[]"').forEach(checkbox => {
        if (checkbox.checked) {
            arrIdsChecked.push(checkbox.id);
            turmasSelecionadas.push(checkbox.value);
        } else {
            arrIdsUnChecked.push(checkbox.id);
        }
        qtdCheckBoxTurmas++;
    });
    return {idsChecked:arrIdsChecked, idsUnChecked:arrIdsUnChecked, tamCheck:qtdCheckBoxTurmas, idsTurmasSelecionadas:turmasSelecionadas}
}

// SALVA OS VALORES DAS CHECKBOXs DE SELEÇÃO DAS TURMAS
jQuery(document).on('click', 'input[type="checkbox"][name="selecao-alunos[]"]', function() {
    let checkboxTurmas = getCheckboxListagemAlunos();
    localStorage.setItem('idsTurmasSelecionadas', checkboxTurmas['idsTurmasSelecionadas']);
});


function getCkbAlunosTurma(opc){
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

function adicionaAcoesCheckboxTurma(){
    
    let turmasSelecionadas = localStorage.getItem('idsTurmasSelecionadas');

    if(turmasSelecionadas){

        jQuery.each(turmasSelecionadas.split(','), function(index, item) {
        
            // SALVA OS VALORES DAS CHECKBOXs DE SELEÇÃO DOS ALUNOS DA TURMA 2
            jQuery(document).on('click', 'input[type="checkbox"][name="ckb-alunos-t'+(index+1)+'[]"]', function() {
                let alunosTurma = getCkbAlunosTurma(index+1);
                localStorage.setItem('alunos-selecionados-turma-'+(index+1), alunosTurma['alunosSelecionados']);
            });

            // SALVA OS VALORES DAS CHECKBOXs DE SELEÇÃO DOS ALUNOS DA TURMA 2
            jQuery(document).on('click', 'input[type="checkbox"][name="ckb-turma-'+(index+1)+'"]', function() {
                if (this.checked) {
                    let alunosTurma = getCkbAlunosTurma(index+1);
                    if (alunosTurma.idsDesmarcados.length > 0){
                        jQuery.each(alunosTurma.idsDesmarcados, function(index, item) {
                            jQuery("#"+item).prop("checked", true);
                        });
                        let alunos = getCkbAlunosTurma(index+1);
                        localStorage.setItem('alunos-selecionados-turma-'+(index+1), alunos['alunosSelecionados']);
                    }
                } else {
                    let alunosTurma = getCkbAlunosTurma(index+1);
                    if (alunosTurma.idsMarcados.length > 0){
                        jQuery.each(alunosTurma.idsMarcados, function(index, item) {
                            jQuery("#"+item).prop("checked", false);
                        });
                        let alunos = getCkbAlunosTurma(index+1);
                        localStorage.setItem('alunos-selecionados-turma-'+(index+1), alunos['alunosSelecionados']);
                    }
                }
            });

        });
    }
}
