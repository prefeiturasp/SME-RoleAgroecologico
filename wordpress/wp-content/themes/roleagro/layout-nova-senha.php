<?php
/*
 * Template Name: Layout Nova Senha
 * Description: Modelo renovar a senha
 */
wp_head();
get_header('login');

?>
<div class="container-fluid container-forms">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-5 offset-md-7">
                <?php
                    get_template_part('src/Views/template-parts/nova-senha-form');
                ?>
            </div>
        </div>
    </div>			
</div>

<?php 

wp_footer();
get_footer('login'); 

?>
   
<script>

    function validaQtdTxt(txt){
        // Valida quantidade de caracteres digitados
        if(txt.length >= 8 && txt.length <= 12 ){
            $("#sp-qtd").removeClass("alert-danger");
            $("#sp-qtd").addClass("alert-success");
            return true;
        } else {
            $("#sp-qtd").removeClass("alert-success");
            $("#sp-qtd").addClass("alert-danger");
            return false;
        }
    }

    function validaTxtMaiusculo(txt){
        // Regex para verificar letras maiúsculas
        let regexMaiuscula = /[A-Z]/;
        let temMaiuscula = regexMaiuscula.test(txt);

        if(temMaiuscula){
            $("#sp-maius").removeClass("alert-danger");
            $("#sp-maius").addClass("alert-success");
            return true;
        } else {
            $("#sp-maius").removeClass("alert-success");
            $("#sp-maius").addClass("alert-danger");
            return false;
        }
    }

    function validaTxtMinusculo(txt){
         // Regex para verificar letras minuscula
        let regexMinuscula = /[a-z]/;
        let temMinuscula = regexMinuscula.test(txt);

        if(temMinuscula){
            $("#sp-minus").removeClass("alert-danger");
            $("#sp-minus").addClass("alert-success");
            return true;
        } else {
            $("#sp-minus").removeClass("alert-success");
            $("#sp-minus").addClass("alert-danger");
            return false;
        }
    }

    function validaNumero(txt){
        // Regex para verificar se tem um número
        let regexNumeros = /[0-9]/;
        let temNumeros = regexNumeros.test(txt);

        if(temNumeros){
            $("#sp-num").removeClass("alert-danger");
            $("#sp-num").addClass("alert-success");
            return true;
        } else {
            $("#sp-num").removeClass("alert-success");
            $("#sp-num").addClass("alert-danger");
            return false;
        }
    }

    function validaCharEsp(txt){
    
        // Regex para verificar caracteres especiais
        let regexEspeciais = /[$*&@#]/;
        let temEspeciais = regexEspeciais.test(txt);

        if(temEspeciais){
            $("#sp-char").removeClass("alert-danger");
            $("#sp-char").addClass("alert-success");
            return true;
        } else {
            $("#sp-char").removeClass("alert-success");
            $("#sp-char").addClass("alert-danger");
            return false;
        }
    }

    function validaTxtAcentuados(txt){
        var regexAcentos = /[áàâãéèêíìîóòôõöúùûçÁÀÂÃÉÈÊÍÏÓÔÕÖÚÇÑ]/; 
        let temAcento = regexAcentos.test(txt);

        if(temAcento){
            $("#sp-acento").removeClass("alert-success");
            $("#sp-acento").addClass("alert-danger");
            return true;
        } else {
            $("#sp-acento").removeClass("alert-danger");
            $("#sp-acento").addClass("alert-success");
            return false;
        }
    }

    //Esconde a div de validações
    $("#itens-validacao").hide();
    $("#btn-continua-rec-senha").prop("disabled", true);

    $('#nv-senha1').on('keyup', function(event) {

        let txt = $('#nv-senha1').val();
        let validaQtdChar = false;
        let validaMaiusculo = false;
        let validaMinusculo = false;
        let validaNum = false;
        let validaEsp = false;
        let validaAcento = false;

        if(txt.length > 0){
            $("#itens-validacao").show();
        } else {
            $("#itens-validacao").hide();
        }

        validaQtdChar = validaQtdTxt(txt);
        validaAcento = validaTxtAcentuados(txt);
        validaMaiusculo = validaTxtMaiusculo(txt);
        validaMinusculo = validaTxtMinusculo(txt);
        validaEsp = validaCharEsp(txt);
        validaNum = validaNumero(txt);

        if(validaQtdChar && validaMaiusculo && validaMinusculo && validaNum && validaEsp && !validaAcento){
            $("#btn-continua-rec-senha").prop("disabled", false);
        } else {
            $("#btn-continua-rec-senha").prop("disabled", true);
        }

    });

    $('#btn-continua-rec-senha').on('click', function(event) {
        //Cancela o envio do formulário
        event.preventDefault();
        let senha1 = $("#nv-senha1").val();
        let senha2 = $("#nv-senha2").val();

        if(senha1 === senha2){
            $('#form-nova-senha').submit();
        } else {
            Swal.fire({icon: "info",html:'<b>As senhas informadas não são iguais!</b>',showConfirmButton: false,timer: 2000});
        }
    });

</script>
