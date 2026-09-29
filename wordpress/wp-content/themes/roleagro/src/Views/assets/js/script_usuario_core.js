document.addEventListener("DOMContentLoaded", function () {

    const qtdUsuarios = document.getElementById("qtd-usuario").value;
    
    for(let i=0; i < qtdUsuarios; i++){

        // Abre o modal ao clicar no botão
        // document.getElementById("btnEditUsuario"+i).addEventListener("click", function () {
        //     document.getElementById("divEditUsuario"+i).style.display = "block";
        // });

        // Fecha o modal ao clicar no 'X'
        // document.getElementById("fechar-modal"+i).addEventListener("click", function () {
        //     document.getElementById("divEditUsuario"+i).style.display = "none";
        // });

        // Fecha o modal ao clicar fora da caixa do conteúdo
        window.addEventListener("click", function (event) {
            if (event.target === document.getElementById("divModal") || event.target === document.getElementById("close")) {
                escondeCampo("detalhamento");
                document.getElementById("rfUsuario").value = "";
                document.getElementById("verificaUsuarioCad").innerHTML = "";
            }
        });

    }

    document.getElementById("limpar-form").addEventListener("click", function () {
        const urlAtual = window.location.href;
        window.location.assign(urlAtual);
    });

    document.getElementById("geraSenha").addEventListener("click", function () {
        getPassword();
    });

    const radios = document.querySelectorAll('input[name="opcao"]');

    radios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            const valor = e.target.value;
            
            if (valor === 'div1') {
            document.getElementById('div1').style.display = 'block';
            document.getElementById('div2').style.display = 'none';
            } else {
            document.getElementById('div1').style.display = 'none';
            document.getElementById('div2').style.display = 'block';
            }
        });
    });

    // Esconde campos do formulário de Detalhamento do usuário
    // jQuery("#nomeUsuarioCad").hide();
    // jQuery("#cpfUsuarioCad").hide();
    // jQuery("#cargoUsuarioCad").hide();
    // jQuery("#emailUsuarioCad").hide();

    jQuery("#cpfUsuExt").mask('000.000.000-00');

    // CONTA A QUANTIDADE DE CARACTERES DO RF PARA SOLICITAR DADOS NA API
    let inputElement = document.getElementById('rfUsuario');
    inputElement.addEventListener('input', function() {
        let textoDigitado = inputElement.value;
        let numeroDeCaracteres = textoDigitado.length;
        if(numeroDeCaracteres == 7){
            
            exibeCampo("carregamento");
            escondeCampo("detalhamento");
            // desabilitaCampo("rfUsuario");

            let dados = {
                rf: textoDigitado
            }
            fetch("/wp-json/servidor/rf", {
                method: 'POST', 
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(dados)
            }).then(response => response.json()).then(data => {

                if(!data['success']){
                    jQuery("#verificaUsuarioCad").html('');
                    escondeCampo("carregamento");
                    exibeCampo("naoEncontrado");
                } else {

                    // Verifica se o usuário está cadastrado
                    checarSeUsuarioExiste(textoDigitado).then(dado => {
                        let inputEle = document.getElementById("verificaUsuarioCad");
                        if (dado['existe']) {
                            inputEle.innerHTML = '<span class="area-destaque-sucesso">Usuário cadastrado!</span> <span class="area-destaque-sucesso"><a href="'+dado['url']+'" class="editUsucad" target="_blank"><span class="dashicons dashicons-edit"></span></a></span>'; 
                        } else {
                            inputEle.innerHTML = '<span class="area-destaque-atencao">O usuário não está cadastrado!</span>';
                        }
                    });

                    if(data['success'] && data['data']['nome'] != null){

                        const conteudoAdicional = document.getElementById("conteudo-detalhes-adicionais");

                        escondeCampo("carregamento");
                        exibeCampo("detalhamento");
                        // habilitaCampo("rfUsuario");                    

                        const dados = data['data'];

                        let nome = dados['nome'];
                        let cpf = dados['cpf'];
                        let email = dados['email'];
                        let areasAtuacao = dados['areasAtuacao'];
                        let cargo = '';
                        if(!dados['cargosSobrePosto']){
                            cargo = dados['cargos'][0].nome;
                        } else{
                            cargo = dados['cargosSobrePosto'][0].nome;
                        }
                
                        jQuery('#nomeUsuarioCad').val(nome);
                        jQuery('#cpfUsuarioCad').val(cpf);
                        jQuery('#emailUsuarioCad').val(email);
                        jQuery('#cargoUsuarioCad').val(cargo);

                        let html = '<h4>Detalhes Adicionais</h4><hr>';
                        html += '<table class="table table-sm">';
                        html += '<tbody>';

                        if(dados['cargosSobrePosto'] != null) {
                            html += renderizaHtmlAdicional("Sobreposto", dados['cargosSobrePosto']);
                        }

                        html += renderizaHtmlAdicional("Cargo(s)", dados['cargos']);

                        if(dados['funcoesAtividade'] != null) {
                            html += renderizaHtmlAdicional("Funções", dados['funcoesAtividade']);
                        }
    
                        html += renderizaHtmlAdicional("UEs Lotação", dados['unidadesLotacao']);

                        if(dados['unidadeExercicio'] != null) {
                            html += '<tr class="table-active"><td colspan="2">EU Exercício</td></tr>';
                            html += '<tr>';
                            html +=     '<th scope="row">'+dados['unidadeExercicio']['codigo']+'</th>';
                            html +=     '<td>'+dados['unidadeExercicio']['nomeUnidade']+'</td>';
                            html += '</tr>';
                        }

                        html += '</tbody>';
                        html += '</table>';
                        html += '<hr>';

                        html += '<strong>Área de Atuação: </strong>';

                        areasAtuacao.forEach(item => {
                            html += '<span class="area-atuacao">'+item+'</span> &nbsp;';
                        });
                        
                        conteudoAdicional.innerHTML = html;

                    } 
                }

            }).catch((e) => {
                console.log('Erro ao carregar '+e);
            });

        } else if(numeroDeCaracteres < 7){ 
            escondeCampo("detalhamento");
            jQuery("#verificaUsuarioCad").html('');
            escondeCampo("naoEncontrado");
        }
    });

    document.getElementById('form-usuario-externo').addEventListener('submit', function(e) {
        e.preventDefault();

        let nome = document.getElementById('nomeUsuExt').value;
        let email = document.getElementById('emailUsuExt').value;
        let cpf = removeMacaraCPF(document.getElementById('cpfUsuExt').value);
        let perfil = document.getElementById('perfilUsuExt').value;
        let pass = document.getElementById('passUsuarioCad').value;

        if(nome && email && cpf && perfil && pass){

            jQuery("#btnSalvaUsuExt").prop("disabled", true);
            jQuery('#imgCarregamento').css('display', 'block');

            const formData = new FormData();
            formData.append('action', 'processa_cadastro_usuario_ext');
            formData.append('seguranca', adminAjax.seguranca); // Envia o nonce gerado pelo PHP
            formData.append('nome', nome);
            formData.append('email', email);
            formData.append('cpf', cpf);
            formData.append('password', pass);
            formData.append('role', perfil);

            const retornoDiv = document.getElementById('retorno-mensagem');
            
            fetch(adminAjax.ajaxurl, {
                method: 'POST',
                body: formData
            }).then(response => {
                // Se o check_ajax_referer falhar, o WordPress retorna status 400 ou 403
                if (!response.ok) {
                    jQuery("#btnSalvaUsuExt").prop("disabled", false);
                    jQuery('#imgCarregamento').css('display', 'none');
                    retornoDiv.innerHTML = renderizaMensagem('Falha na validação de segurança ou requisição inválida.', 'warning');
                }
                return response.json();
            }).then(data => {

                if (data.success) {
                    retornoDiv.innerHTML = renderizaMensagem(data.data.message, 'success');
                    jQuery("#btnSalvaUsuExt").prop("disabled", false);
                    jQuery('#imgCarregamento').css('display', 'none');
                    setTimeout(() => {
                        jQuery('#divModalExterno').modal('hide');
                        location.reload();
                    }, 3000);
                } else {
                    retornoDiv.innerHTML = renderizaMensagem(data.data.message, 'warning');
                    jQuery("#btnSalvaUsuExt").prop("disabled", false);
                    jQuery('#imgCarregamento').css('display', 'none');
                }

            }).catch(error => {
                jQuery("#btnSalvaUsuExt").prop("disabled", false);
                jQuery('#imgCarregamento').css('display', 'none');
                retornoDiv.innerHTML = renderizaMensagem('Sua sessão expirou ou a requisição foi bloqueada por segurança.', 'warning');
            });
        }
    });

       
});

function renderizaHtmlAdicional(descricao, dados){

    let html = '<tr class="table-active"><td colspan="2">'+descricao+'</td></tr>';
        dados.forEach(dado => {
            html += '<tr>';
            html +=     '<th scope="row">'+dado['codigo']+'</th>';
            if(dado['nomeUnidade']){
                html +=     '<td>'+dado['nomeUnidade']+'</td>';
            } else{
                html +=     '<td>'+dado['nome']+'</td>';
            }
            
            html += '</tr>';
        });
        
    return html;
}

function getPassword() {
    var chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJLMNOPQRSTUVWXYZ!@#$%^&*()+?><:{}[]";
    var passwordLength = 16;
    var pass = "";

    for (var i = 0; i < passwordLength; i++) {
        var randomNumber = Math.floor(Math.random() * chars.length);
        pass += chars.substring(randomNumber, randomNumber + 1);
    }
    document.getElementById('passUsuarioCad').value = pass
}

function escondeCampo(idCampo){
    // Esconde o campo
    document.getElementById(idCampo).style.display = "none";
}

function exibeCampo(idCampo){
    // Exibe o campo
    document.getElementById(idCampo).style.display = "block";
}

function habilitaCampo(idCampo){
    const campo = document.getElementById(idCampo);
    campo.readOnly = false;
}

function desabilitaCampo(idCampo){
    const campo = document.getElementById(idCampo);
    campo.readOnly = true;
}

async function checarSeUsuarioExiste(nomeOuEmail) {
    const formData = new FormData();
    formData.append('action', 'verificar_usuario');
    formData.append('usuario', nomeOuEmail);

    // Substitua pelo caminho correto do admin-ajax se necessário, ou localize via wp_localize_script
    const urlAjax = '/wp-admin/admin-ajax.php'; 

    try {
        const resposta = await fetch(urlAjax, {
            method: 'POST',
            body: formData
        });
        
        const resultado = await resposta.json();
        return resultado.success ? resultado.data : false;
    } catch (erro) {
        console.error('Erro na requisição:', erro);
        return false;
    }
}

function renderizaMensagem(msg, notificacao){
    html = '<div class="notice notice-'+notificacao+' is-dismissible">';
    html += '<p><strong>'+msg+'</strong></p>';
    html += '</div>';
    return html;
}

function removeMacaraCPF(cpf) {
  return cpf.replace(/\D/g, ""); // O 'D' maiúsculo remove tudo o que não for dígito
}


