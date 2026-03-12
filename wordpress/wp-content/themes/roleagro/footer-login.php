<?php wp_footer(); ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>

    $(document).ready(function() {
        setTimeout(()=>{
            $("#alerta").hide();
        },5000);


        $("#wp-submit").on("click", function(){

            //Cancela o envio do formulário
            event.preventDefault();

            let username = $("#user_login").val();
            let pass = $("#user_pass").val();

            if(!username || !pass){
                Swal.fire({
                    position: "center",
                    icon: "info",
                    html: 'Verifique se o usuário e a senha estão preenchido, por gentileza!',
                    showConfirmButton: false,
                    timer: 4000
                });
            } else {
                Swal.fire({
                    position: "center",
                    title: '<small>Realizando o login...</small>',
                    html: 'Aguarde um instante!',
                    showConfirmButton: false,
                    imageUrl: "https://i.pinimg.com/originals/e7/56/60/e75660be6aba272e4b651911b6faee55.gif",
                    imageWidth: 100
                });
                //Envia o Formulário
                $('#loginform-custom').submit();
            }

        });
    }); 

</script>
</body>
</html>