<div class="container-fluid container-forms" id="painel-login-duplo">
    <div class="container conteudo-opcoes" style="background-color: rgba(255, 255, 255, 0.7);">
        <h2 class="titulo-bloco-selecao text-center">Localizamos mais de uma lotação para seu RF,<br> selecione abaixo com qual Unidade deseja acessar:</h2>
        <p>&nbsp;</p>
            
            <div class="row">
                <div class="col">
                    <a onClick="selecionaPerfil(1)" style="cursor: pointer;">
                        <div class="shadow p-3 mb-5 bg-white rounded">
                            <h5 class="card-title"><?= $_SESSION['arrCargosUe'][0]['cargoo']?> 1</h5>
                            <p class="card-text">ughdjsdfhjdfjhgj</p>
                        </div>
                    </a>
                </div>

                <div class="col">
                    <a onClick="selecionaPerfil(2)" style="cursor: pointer;">
                        <div class="shadow p-3 mb-5 bg-white rounded">
                            <h5 class="card-title"><?= $_SESSION['arrCargosUe'][1]['cargoo']?> 2</h5>
                            <p class="card-text">dvsdvmnsd,mvn</p>
                        </div>
                    </a>
                </div>
            </div>

    </div>

    <form id="duplo-login-op1" action="/login-duplo" method="POST">
        <input value="1" name="opc" type='hidden'/>
    </form>

     <form id="duplo-login-op2" action="/login-duplo" method="POST">
        <input value="2" name="opc" type='hidden'/>
    </form>

    <br>
    <br>
    <br>
</div>
