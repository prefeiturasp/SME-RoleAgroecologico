jQuery(document).ready(function ($) {
    
    //Cria os tooltips nos cabaçalhos das tabelas do repetidor
    $('.acf-table').each(function () {
        const $table = $(this);

        $table.find('tr .acf-th').each(function () {
            const $inputWrapper = $(this);
            const $instruction = $inputWrapper.find('.description');

            if ($instruction.length && $instruction.text().trim() !== '') {
                const tooltipText = $instruction.text().trim();

                const tooltip = $(`
                    <span class="acf-repeater-tooltip">
                        <span class="dashicons dashicons-editor-help"></span> <span class="acf-tooltip-text">${tooltipText}</span>
                    </span>
                `);

                $inputWrapper.append(tooltip);
                $instruction.remove();
            }
        });
    });

    //Cria os tooltips ao lado do nome dos campos
    $('.acf-label').each(function () {
        const $label = $(this);
        const $instruction = $label.find('.description');
  
        if ($instruction.length && $instruction.text().trim() !== '') {
            const tooltipText = $instruction.text().trim();

            const tooltip = $(`
                <span class="acf-repeater-tooltip">
                    <span class="dashicons dashicons-editor-help"></span> <span class="acf-tooltip-text">${tooltipText}</span>
                </span>
            `);

            $label.append(tooltip);
            $instruction.remove();
        }
    });

    //Preenche os dados de encdereço automáticamente com base no CEP
    $('#cep input[type="text"]').on('focusout', function () {

        const input = $(this);
        const value = input.cleanVal();

        if (value.length === 8) {
            $.ajax({
                url : `https://viacep.com.br/ws/${value}/json/`,
                type : 'GET',
                beforeSend : function(){
                    input.prop('disabled', true);
                }
            })
            .success(function(response){
                $('#logradouro input[type="text"]').val(response.logradouro);
                $('#estado input[type="text"]').val(response.estado);
                $('#cidade input[type="text"]').val(response.localidade);
                $('#bairro input[type="text"]').val(response.bairro);
            })
            .done(function () {
                input.prop('disabled', false);
            });
        }
    })

    //Adiciona máscara nos inputs
    $( '.input-cep input[type="text"]' ).mask( '00000-000' );
    $( '.input-cnpj input[type="text"]' ).mask( '00.000.000/0000-00', {reverse: true} );
    $( document ).on( 'focus', '.input-telefone input[type="text"]', function(){
        $(this).mask('(00) 00000-0000'); 
    });
});

//Preenche os tipos de veículos disponíveis com base no transportador selecionado
jQuery(function($){

    var $transportadores = $('.acf-field[data-name="transporte"] select');
    var $veiculos = $('.acf-field[data-name="veiculos_passeio"] select');

    if ( $transportadores.val() ) {
        carregarVeiculos($transportadores.val())
    } else {
        $veiculos.prop('disabled', true)
        $veiculos.find('option').hide();
        $veiculos.append('<option value="" disabled>É necessário selecionar um transportador...</option>')
    }

    $(document).on('change', '.acf-field[data-name="transporte"] select', function() {
        carregarVeiculos( $(this).val());
    });

    function carregarVeiculos( transportadorId ) {

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_transportador_veiculos',
                transportador_id: transportadorId
            },
            success: function(response) {

                $veiculos.prop('disabled', false)
                $veiculos.find('option').hide();

                $.each(response.data, function(indice, id) {
                    $veiculos.find(`option[value="${id}"]`).show();
                });
            }
        });
    }
})
