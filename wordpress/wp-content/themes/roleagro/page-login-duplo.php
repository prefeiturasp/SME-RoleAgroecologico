<?php

get_header('login');

!isset($_SESSION['arrCargosUe']) ? wp_redirect(site_url("/login")) : '';

?>
<div class="container-fluid container-forms" >

    <div class="container conteudo-opcoes" style="background-color: rgba(255, 255, 255, 0.7);">
       
        <h2 class="titulo-bloco-selecao text-center">Localizamos mais de uma lotação para seu RF,<br> selecione abaixo com qual Unidade deseja acessar:</h2>

        <p>&nbsp;</p>
      
            <div class="row">

                    <div class="col" style="cursor: pointer;">
                        <a onClick="document.getElementById('duplo-login-op1').submit();">
                            <div class="shadow p-3 mb-5 bg-white rounded">
                                <h5 class="card-title"><?= $_SESSION['arrCargosUe'][0]['cargo']?></h5>
                                <p class="card-text"><?= $_SESSION['arrCargosUe'][0]['nomeUe']?></p>
                            </div>
                        </a>
                    </div>

                    <div class="col" style="cursor: pointer;">
                        <a onClick="document.getElementById('duplo-login-op2').submit();">
                            <div class="shadow p-3 mb-5 bg-white rounded">
                                <h5 class="card-title"><?= $_SESSION['arrCargosUe'][1]['cargo']?></h5>
                                <p class="card-text"><?= $_SESSION['arrCargosUe'][1]['nomeUe']?></p>
                            </div>
                        </a>
                    </div>
                
            </div>

    </div>

    <form id="duplo-login-op1" action="/processa-login-duplo" method="POST">
        <input value="1" name="opc" type='hidden'/>
    </form>

     <form id="duplo-login-op2" action="/processa-login-duplo" method="POST">
        <input value="2" name="opc" type='hidden'/>
    </form>

</div>

<?php
       
get_footer('login');