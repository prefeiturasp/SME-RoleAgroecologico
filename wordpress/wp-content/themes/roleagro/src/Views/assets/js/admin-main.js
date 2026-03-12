document.addEventListener('DOMContentLoaded', function() {

            const tipoField = document.createElement('tr');
            let tipLog = document.getElementById('tipLog').value;
    
            let selParceiro = '';
            let selPfom = '';
            let selEmail = '';

            if(tipLog){
                switch(tipLog){
                    case 'cpfEntPar': selParceiro = 'selected'; break;
                    case 'cpfEmePfom': selPfom = 'selected'; break;
                    case 'email': selEmail = 'selected'; break;
                }
            }
            tipoField.innerHTML = `<th><label for="tipo_login">Tipo de Usuário</label></th>
                                    <td>
                                        <select id="tipo_login" name="tipo_login" >
                                            <option value="">--- Selecione ---</option>
                                            <option value="cpfEntPar" `+selParceiro+`>Entidade Parceira</option>
                                            <option value="cpfEmePfom" `+selPfom+`>Diretor EMEF PFOM</option>
                                            <option value="email" `+selEmail+`>Acesso com E-mail</option>
                                        </select>
                                        <p class="description">Escolha o tipo de dado que será usado como login.</p>
                                    </td>`;

            const loginRow = document.querySelector('#user_login').closest('tr');
            const emailRow = document.querySelector('#email').closest('tr');
            loginRow.parentNode.insertBefore(tipoField, loginRow);

            const lastNameRow = document.querySelector('#last_name').closest('tr');
            lastNameRow.style.display = 'none';

            const userLogin = document.getElementById('user_login');
            const userEmail = document.getElementById('email');
            const tipoSelect = document.getElementById('tipo_login');


            // Alterna comportamento conforme o tipo
            tipoSelect.addEventListener('change', function() {
                userLogin.value = '';

                if (this.value === 'cpfEntPar' || this.value === 'cpfEmePfom') {
                    userLogin.placeholder = 'Digite o CPF (ex: 123.456.789-00)';
                    userLogin.type = 'text';
                    emailRow.style.display = ''; 
                    jQuery("#user_login").mask('000.000.000-00');
                } 

                if (this.value === 'cpfEmePfom') {
                    eolUsuarioRow.style.display = ''; 
                    ueUsuarioRow.style.display = '';
                    dreUsuarioRow.style.display = '';
                } else {
                    eolUsuarioRow.style.display = 'none';
                    ueUsuarioRow.style.display = 'none';
                    dreUsuarioRow.style.display = 'none';
                }
               
                if (this.value === 'email') {
                    userLogin.placeholder = 'Digite o e-mail (será usado como login)';
                    userLogin.type = 'email';
                    emailRow.style.display = 'none'; // oculta o campo e-mail
                }

            });

        // Aplica máscaras dinamicamente
        userLogin.addEventListener('input', function() {
            // Se for e-mail, replica automaticamente para o campo de e-mail do WP
            if (tipoSelect.value === 'email' && userEmail) {
                userEmail.value = this.value;
            }
        });

        criaCampoTelefone();
        criaCampoEolUsu();
        criaCampoEuUsu();
        criaCampoDreUsu();

        const eolUsuarioRow = document.querySelector('#eol_usuario').closest('tr');
        const ueUsuarioRow = document.querySelector('#ue_usuario').closest('tr');
        const dreUsuarioRow = document.querySelector('#dre_usuario').closest('tr');

        eolUsuarioRow.style.display = 'none';
        ueUsuarioRow.style.display = 'none';
        dreUsuarioRow.style.display = 'none';

        // Máscara dinâmica
        const campoTelefone = document.getElementById('telefone_usuario');
        campoTelefone.addEventListener('input', function(e) {
            let x = this.value.replace(/\D/g, '').slice(0, 11);
            if (x.length > 6) {
                this.value = `(${x.substring(0,2)}) ${x.substring(2,7)}-${x.substring(7,11)}`;
            } else if (x.length > 2) {
                this.value = `(${x.substring(0,2)}) ${x.substring(2,7)}`;
            } else if (x.length > 0) {
                this.value = `(${x}`;
            }
        });




        // CONTA A QUANTIDADE DE CARACTERES DO COD EOL
        const inputElement = document.getElementById('eol_usuario');
        inputElement.addEventListener('input', function() {
            const textoDigitado = inputElement.value;
            const numeroDeCaracteres = textoDigitado.length;
            const maxLength = 6;
            if(numeroDeCaracteres == 6){
                Swal.fire({
                    position: "center",
                    title: '<small>Localizando a unidade informada...<?small>',
                    html: 'Aguarde um instante, estamos buscando os dados informados.',
                    showConfirmButton: false,
                    imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                    imageWidth: 100
                });

                let dados = {
                    cod_eol: textoDigitado
                }
                enviaRequisicaoAPI(dados);
            }
            if (numeroDeCaracteres > maxLength) {
                inputElement.value = textoDigitado.slice(0, maxLength);
            }
        });


    });

    function criaCampoTelefone(){
        const inputPrimeiroNome = document.getElementById('first_name');
        if (!inputPrimeiroNome) return;
        // Cria o campo de telefone
        let telUsu = document.getElementById('telUsu').value;
        let phoneRow = document.createElement('tr');
        phoneRow.innerHTML = '<th><label for="c">Telefone</label></th>'+
            '<td><input type="text" name="telefone_usuario" id="telefone_usuario" class="regular-text" placeholder="(99) 99999-9999" value="'+telUsu+'"/>'+
                '<p class="description">Informe o telefone do usuário.</p>'+
            '</td>';
        // Insere logo após o campo "Nome"
        let firstNameRow = inputPrimeiroNome.closest('tr');
        firstNameRow.parentNode.insertBefore(phoneRow, firstNameRow.nextSibling);
    }

    function criaCampoEolUsu(){
        const inputTelefone = document.getElementById('telefone_usuario');
        if (!inputTelefone) return;
        // Cria o campo de Eol
        
        const eolUsuRow = document.createElement('tr');
        eolUsuRow.innerHTML = '<th><label for="eol_usuario">Cod. EOL</label></th>'+
            '<td><input type="text" name="eol_usuario" id="eol_usuario" class="regular-text" placeholder="999999" required/>'+
                '<p class="description">Informe o código Eol da Unidade Educacional.</p>'+
            '</td>';
        // Insere logo após o campo "Nome"
        let firstNameRow = inputTelefone.closest('tr');
        firstNameRow.parentNode.insertBefore(eolUsuRow, firstNameRow.nextSibling);
    }

    function criaCampoEuUsu(){
        const inputEol = document.getElementById('eol_usuario');
        if (!inputEol) return;
        // Cria o campo de Eol
        
        const ueUsuRow = document.createElement('tr');
        ueUsuRow.innerHTML = '<th><label for="ue_usuario">Unidade Educacional</label></th>'+
            '<td><input type="text" name="ue_usuario" id="ue_usuario" class="regular-text" readonly/>'
            '</td>';
        // Insere logo após o campo "Nome"
        let firstNameRow = inputEol.closest('tr');
        firstNameRow.parentNode.insertBefore(ueUsuRow, firstNameRow.nextSibling);
    }

    function criaCampoDreUsu(){
        const inputUeUsu = document.getElementById('ue_usuario');
        if (!inputUeUsu) return;
        // Cria o campo de Eol
        
        const dreUsuRow = document.createElement('tr');
        dreUsuRow.innerHTML = '<th><label for="dre_usuario">DRE</label></th>'+
            '<td><input type="text" name="dre_usuario" id="dre_usuario" class="regular-text" readonly/></td>';
        // Insere logo após o campo "Nome"
        let firstNameRow = inputUeUsu.closest('tr');
        firstNameRow.parentNode.insertBefore(dreUsuRow, firstNameRow.nextSibling);
    }

    function enviaRequisicaoAPI(dados){
        fetch("/wp-json/busca/eol-ue", {
            method: 'POST', 
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(dados)
        }).then(response => response.json()).then(data => {
            if(data['success']){
                Swal.close();
                let dados = data.data;
                jQuery("#ue_usuario").val(dados['siglaTipoEscola'].trim()+' - '+dados['nomeExibicao']); 
                jQuery("#dre_usuario").val(dados['nomeDRE']); 
            }
        }).catch(() => {
            console.log('Erro ao solicitar dados!');
        });
    }

    