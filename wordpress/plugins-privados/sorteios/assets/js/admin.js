document.addEventListener('DOMContentLoaded', function () {

    const botaoAbrirLog = document.querySelectorAll(
        '.sorteios-abrir-log'
    );

    const modal = document.getElementById(
        'sorteios-modal-log'
    );

    const botaoFechar = document.querySelector(
        '.sorteios-modal-fechar'
    );

    const overlay = document.querySelector(
        '.sorteios-modal-overlay'
    );

    const botaoTrocar = document.querySelector(
        '.sorteios-trocar-arquivo'
    );

    const areaTroca = document.getElementById(
        'sorteios-upload-troca'
    );

    /*
     * Abrir modal do log.
     */
    botaoAbrirLog.forEach(function (botao) {
        botao.addEventListener('click', function () {
            if (!modal) {
                return;
            }

            modal.style.display = 'block';
            document.body.classList.add(
                'sorteios-modal-aberto'
            );
        });
    });

    /*
     * Fechar modal.
     */
    function fecharModal() {
        if (!modal) {
            return;
        }

        modal.style.display = 'none';

        document.body.classList.remove(
            'sorteios-modal-aberto'
        );
    }

    if (botaoFechar) {
        botaoFechar.addEventListener(
            'click',
            fecharModal
        );
    }

    if (overlay) {
        overlay.addEventListener(
            'click',
            fecharModal
        );
    }

    /*
     * ESC fecha o modal.
     */
    document.addEventListener(
        'keydown',
        function (event) {
            if (
                event.key === 'Escape' &&
                modal &&
                modal.style.display !== 'none'
            ) {
                fecharModal();
            }
        }
    );

    /*
     * Trocar arquivo.
     */
    if (botaoTrocar && areaTroca) {
        botaoTrocar.addEventListener(
            'click',
            function () {
                areaTroca.style.display = 'block';

                botaoTrocar.style.display = 'none';

                const input = areaTroca.querySelector(
                    'input[type="file"]'
                );

                if (input) {
                    input.focus();
                }
            }
        );
    }

    const botaoSelecionarTodas = document.getElementById(
        'sorteios-selecionar-todas-dres'
    );

    const botaoLimparDres = document.getElementById(
        'sorteios-limpar-dres'
    );

    const contadorDres = document.getElementById(
        'sorteios-dres-contador'
    );

    const checkboxesDres = document.querySelectorAll(
        '.sorteios-dre-item input[type="checkbox"]'
    );

    function atualizarContadorDres() {
        if (!contadorDres) {
            return;
        }

        const total = checkboxesDres.length;

        const selecionadas = document.querySelectorAll(
            '.sorteios-dre-item input[type="checkbox"]:checked'
        ).length;

        contadorDres.textContent =
            selecionadas +
            ' de ' +
            total +
            ' DREs selecionadas';
    }

    checkboxesDres.forEach(function (checkbox) {
        checkbox.addEventListener(
            'change',
            atualizarContadorDres
        );
    });

    if (botaoSelecionarTodas) {
        botaoSelecionarTodas.addEventListener(
            'click',
            function () {
                checkboxesDres.forEach(function (checkbox) {
                    checkbox.checked = true;
                });

                atualizarContadorDres();
            }
        );
    }

    if (botaoLimparDres) {
        botaoLimparDres.addEventListener(
            'click',
            function () {
                checkboxesDres.forEach(function (checkbox) {
                    checkbox.checked = false;
                });

                atualizarContadorDres();
            }
        );
    }

    atualizarContadorDres();

    const opcoesHorta = document.querySelectorAll(
        'input[name="tipo_horta"]'
    );

    const totalHorta = document.getElementById(
        'sorteios-horta-total'
    );

    const subtituloHorta = document.getElementById(
        'sorteios-horta-subtitulo'
    );

    function atualizarHorta() {
        const selecionado = document.querySelector(
            'input[name="tipo_horta"]:checked'
        );

        if (!selecionado) {
            return;
        }

        const tipo = selecionado.value;

        const total = parseInt(
            selecionado.dataset.total || '0',
            10
        );

        if (totalHorta) {
            totalHorta.textContent =
                total.toLocaleString('pt-BR') +
                ' unidades aptas nas DREs selecionadas';
        }

        if (subtituloHorta) {

            if (tipo === 'Todos') {
                subtituloHorta.textContent =
                    'Todos — com ou sem horta pedagógica';
            } else if (tipo === 'Sim') {
                subtituloHorta.textContent =
                    'Sim — apenas com horta ativa';
            } else if (tipo === 'Não') {
                subtituloHorta.textContent =
                    'Não — sem horta pedagógica ativa';
            } else if (tipo === 'Não informado') {
                subtituloHorta.textContent =
                    'Não informado';
            }
        }
    }

    opcoesHorta.forEach(function (opcao) {
        opcao.addEventListener(
            'change',
            atualizarHorta
        );
    });

    atualizarHorta();

    // Quantidade de unidades.
    const quantidadeSorteio = document.getElementById(
        'sorteios-quantidade'
    );

    const resumoQuantidade = document.getElementById(
        'sorteios-quantidade-resumo'
    );

    function atualizarResumoQuantidade() {
        if (!quantidadeSorteio || !resumoQuantidade) {
            return;
        }

        const quantidade = parseInt(
            quantidadeSorteio.value || '5',
            10
        );

        const totalDres = parseInt(
            resumoQuantidade.dataset.totalDres || '0',
            10
        );

        const totalDisponivel = parseInt(
            resumoQuantidade.dataset.totalDisponivel || '0',
            10
        );

        const totalSolicitado = totalDres * quantidade;

        const quantidadeListaEspera = Math.max(
            0,
            totalDisponivel - totalSolicitado
        );

        if (totalDisponivel === 0) {

            resumoQuantidade.textContent =
                'Não existem unidades aptas nas DREs selecionadas ' +
                'para o tipo de horta escolhido.';

        } else if (totalDisponivel >= totalSolicitado) {

            let mensagemListaEspera =
                ' Não há unidades adicionais em lista de espera.';

            if (quantidadeListaEspera > 0) {
                mensagemListaEspera =
                    ' As demais ' +
                    '<strong>' + quantidadeListaEspera + '</strong>' +
                    ' entram em lista de espera.';
            }

            resumoQuantidade.innerHTML =
                'Serão sorteadas até ' +
                '<strong>' + totalSolicitado + '</strong>' +
                ' unidades (' +
                totalDres +
                ' DREs × ' +
                quantidade +
                ').' +
                mensagemListaEspera;

        } else {

            resumoQuantidade.innerHTML =
                'Serão sorteadas até ' +
                '<strong>' + totalSolicitado + '</strong>' +
                ' unidades (' +
                totalDres +
                ' DREs × ' +
                quantidade +
                '). Existem apenas ' +
                '<strong>' + totalDisponivel + '</strong>' +
                ' unidades aptas disponíveis.';
        }
    }

    if (quantidadeSorteio) {
        quantidadeSorteio.addEventListener(
            'input',
            atualizarResumoQuantidade
        );

        atualizarResumoQuantidade();
    }

});

jQuery(function ($) {

    /*
     * Exclusão de sorteio.
     *
     * Este código precisa ficar antes da validação
     * da tabela de resultados, pois também é usado
     * na página de listagem dos sorteios.
     */
    $(document).on(
        'click',
        '.sorteios-excluir-sorteio',
        function () {

            const botao = $(this);

            const sorteioId = botao.data(
                'sorteio-id'
            );

            const sorteioNome = botao.data(
                'sorteio-nome'
            );

            $('#sorteios-excluir-id')
                .val(sorteioId);

            $('#sorteios-excluir-nome')
                .text(sorteioNome);

            $('#sorteios-modal-excluir')
                .addClass('aberto')
                .attr(
                    'aria-hidden',
                    'false'
                );
        }
    );

    /*
     * Fecha o modal pelo botão Cancelar
     * ou clicando no fundo.
     */
    $(document).on(
        'click',
        '#sorteios-cancelar-exclusao, #sorteios-modal-excluir .sorteios-modal-overlay',
        function () {

            $('#sorteios-modal-excluir')
                .removeClass('aberto')
                .attr(
                    'aria-hidden',
                    'true'
                );

            $('#sorteios-excluir-id')
                .val('');

            $('#sorteios-excluir-nome')
                .text('');
        }
    );

    /*
     * Fecha o modal com ESC.
     */
    $(document).on(
        'keydown',
        function (e) {

            if (
                e.key === 'Escape' &&
                $('#sorteios-modal-excluir')
                    .hasClass('aberto')
            ) {
                $('#sorteios-cancelar-exclusao')
                    .trigger('click');
            }
        }
    );

    /*
     * Filtros da tabela de resultado do sorteio.
     */
    const tabelaResultado = $('#tabela-resultado-sorteio');

    if (!tabelaResultado.length) {
        return;
    }

    const dataTable = tabelaResultado.DataTable({
        pageLength: 25,
        lengthChange: false,     
        order: [
            [1, 'asc'],
            [0, 'asc']
        ],
        columnDefs: [
            {
                targets: [4],
                orderable: false
            }
        ],
        //searching: false,
        language: {
            decimal: ',',
            thousands: '.',
            processing: 'Processando...',
            search: 'Pesquisar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            loadingRecords: 'Carregando...',
            zeroRecords: 'Nenhum registro encontrado',
            emptyTable: 'Nenhum registro disponível',
            paginate: {
                first: 'Primeiro',
                previous: 'Anterior',
                next: 'Próximo',
                last: 'Último'
            }
        }
    });

    /*
     * Exporta os resultados do sorteio.
     */

    $('#sorteios-exportar-resultados').on(
        'click',
        function () {

            const botao = $(this);

            const sorteioId = botao.data(
                'sorteio-id'
            );

            const unidadesIds = [];

            /*
            * Pega todos os registros que passaram
            * pelos filtros atuais.
            *
            * Não fica limitado à página atual.
            */
            dataTable
                .rows({
                    search: 'applied'
                })
                .nodes()
                .each(function (linha) {

                    const unidadeId = $(linha)
                        .attr('data-unidade-id');

                    if (unidadeId) {
                        unidadesIds.push(
                            unidadeId
                        );
                    }

                });

            if (!unidadesIds.length) {
                swal.fire({
                    title: 'Erro!',
                    text: 'Não existem resultados para exportar.',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                return;
            }

            /*
            * Cria um formulário temporário para enviar
            * os dados via POST e iniciar o download.
            */
            const form = $('<form>', {
                method: 'POST',
                action: SorteiosAdmin.exportUrl
            });

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'action',
                    value: 'sorteios_exportar_resultados'
                })
            );

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'nonce',
                    value: SorteiosAdmin.nonceExportar
                })
            );

            form.append(
                $('<input>', {
                    type: 'hidden',
                    name: 'sorteio_id',
                    value: sorteioId
                })
            );

            $.each(
                unidadesIds,
                function (indice, unidadeId) {

                    form.append(
                        $('<input>', {
                            type: 'hidden',
                            name: 'unidades_ids[]',
                            value: unidadeId
                        })
                    );

                }
            );

            $('body').append(form);

            form.trigger('submit');

            form.remove();
        }
    );

    /*
     * Abre/fecha os filtros de seleção múltipla.
     */
    $('.sorteios-filtro-toggle').on('click', function (e) {

        e.stopPropagation();

        const filtro = $(this).data('filtro');

        $('.sorteios-filtro-dropdown')
            .not('[data-filtro-dropdown="' + filtro + '"]')
            .removeClass('aberto');

        $('[data-filtro-dropdown="' + filtro + '"]')
            .toggleClass('aberto');
    });

    /*
     * Impede que o clique dentro do dropdown
     * feche o filtro.
     */
    $('.sorteios-filtro-dropdown').on('click', function (e) {
        e.stopPropagation();
    });

    /*
     * Fecha os filtros ao clicar fora.
     */
    $(document).on('click', function () {

        $('.sorteios-filtro-dropdown')
            .removeClass('aberto');

    });

    /*
     * Inicializa os filtros.
     */
    function atualizarFiltros() {

        const dresSelecionadas = [];

        $('[data-filtro-checkbox="dre"]:checked')
            .each(function () {
                dresSelecionadas.push(
                    $(this).val()
                );
            });

        const statusSelecionados = [];

        $('[data-filtro-checkbox="status"]:checked')
            .each(function () {
                statusSelecionados.push(
                    $(this).val()
                );
            });

        const nome = $.trim(
            $('#filtro-nome-unidade').val()
        ).toLowerCase();

        dataTable
            .column(1)
            .search(
                dresSelecionadas.length > 0
                    ? dresSelecionadas.join('|')
                    : 'a^',
                true,
                false
            );

        dataTable
            .column(2)
            .search(nome);

        dataTable
            .column(3)
            .search(
                statusSelecionados.length > 0
                    ? statusSelecionados.join('|')
                    : 'a^',
                true,
                false
            );

        dataTable.draw();

        atualizarLabelsFiltros(
            dresSelecionadas,
            statusSelecionados
        );
    }

    /*
     * Atualiza o texto dos botões dos filtros.
     */
    function atualizarLabelsFiltros(
        dresSelecionadas,
        statusSelecionados
    ) {

        const totalDres =
            $('[data-filtro-checkbox="dre"]').length;

        const totalStatus =
            $('[data-filtro-checkbox="status"]').length;

        const labelDre =
            $('[data-filtro="dre"] .sorteios-filtro-label');

        const labelStatus =
            $('[data-filtro="status"] .sorteios-filtro-label');

        if (
            dresSelecionadas.length === totalDres
        ) {

            labelDre.text('Todas');

        } else if (
            dresSelecionadas.length === 0
        ) {

            labelDre.text('Nenhuma');

        } else {

            labelDre.text(
                dresSelecionadas.length +
                ' selecionada(s)'
            );

        }

        if (
            statusSelecionados.length === totalStatus
        ) {

            labelStatus.text('Todos');

        } else if (
            statusSelecionados.length === 0
        ) {

            labelStatus.text('Nenhum');

        } else {

            labelStatus.text(
                statusSelecionados.length +
                ' selecionado(s)'
            );

        }
    }    

    /*
     * Selecionar todas as opções.
     */
    $('.sorteios-filtro-todos').on(
        'click',
        function () {

            const dropdown = $(this)
                .closest('.sorteios-filtro-dropdown');

            dropdown
                .find('input[type="checkbox"]')
                .prop('checked', true);

            atualizarFiltros();
        }
    );

    /*
     * Desmarcar todas as opções.
     */
    $('.sorteios-filtro-nenhum').on(
        'click',
        function () {

            const dropdown = $(this)
                .closest('.sorteios-filtro-dropdown');

            dropdown
                .find('input[type="checkbox"]')
                .prop('checked', false);

            atualizarFiltros();
        }
    );

    /*
     * Alteração de uma opção.
     */
    $(document).on(
        'change',
        '[data-filtro-checkbox]',
        function () {

            atualizarFiltros();

        }
    );

    /*
     * Filtro por nome da unidade.
     */
    $('#filtro-nome-unidade').on(
        'input',
        function () {

            atualizarFiltros();

        }
    );

    function atualizarResumoStatus() {

        const contadores = {
            aguardando_confirmacao: 0,
            confirmado: 0,
            lista_espera: 0,
            desistencia: 0
        };

        dataTable
            .rows()
            .every(function () {

                const linha = $(this.node());

                const status = linha
                    .find('.sorteios-coluna-status')
                    .attr('data-search');

                if (
                    Object.prototype.hasOwnProperty.call(
                        contadores,
                        status
                    )
                ) {
                    contadores[status]++;
                }

            });

        $.each(
            contadores,
            function (status, quantidade) {

                $(
                    '[data-resumo-status="' +
                    status +
                    '"]'
                ).text(quantidade);

            }
        );
    }

    /*
     * Estado inicial.
     */
    atualizarFiltros();
    atualizarResumoStatus();

    /*
    * Modal de alteração de status.
    */
    $(document).on(
        'click',
        '.sorteios-alterar-status',
        function () {

            const unidadeId = $(this).data('unidade-id');
            const statusAtual = $(this).data('status');

            $('#sorteios-unidade-id').val(
                unidadeId
            );

            $('input[name="sorteios-status"]')
                .prop('checked', false);

            $(
                'input[name="sorteios-status"][value="' +
                statusAtual +
                '"]'
            ).prop('checked', true);

            $('#modal-alterar-status')
                .fadeIn(150);
        }
    );

    /*
    * Fecha o modal pelo botão.
    */
    $(document).on(
        'click',
        '.sorteios-modal-fechar, .sorteios-modal-cancelar',
        function () {

            $('#modal-alterar-status')
                .fadeOut(150);

        }
    );

    /*
    * Fecha o modal clicando no fundo.
    */
    $(document).on(
        'click',
        '.sorteios-modal-overlay',
        function () {

            $('#modal-alterar-status')
                .fadeOut(150);

        }
    );

    /*
    * Salva a alteração de status.
    */
    $(document).on(
        'click',
        '.sorteios-modal-salvar',
        function () {

            const unidadeId = $(
                '#sorteios-unidade-id'
            ).val();

            const status = $(
                'input[name="sorteios-status"]:checked'
            ).val();

            if (!unidadeId) {
                swal.fire({
                    title: 'Erro!',
                    text: 'Unidade inválida.',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                return;
            }

            if (!status) {
                swal.fire({
                    title: 'Erro!',
                    text: 'Selecione um status.',
                    icon: 'error',
                    confirmButtonText: 'Ok'
                });

                return;
            }

            const botao = $(this);

            botao.prop('disabled', true);

            $.ajax({
                url: SorteiosAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sorteios_alterar_status',
                    nonce: SorteiosAdmin.nonceAlterarStatus,
                    unidade_id: unidadeId,
                    status: status
                },
                success: function (response) {

                    console.log(response);

                    if (!response.success) {
                        swal.fire({
                            title: 'Erro!',
                            text: response.data.message || 'Não foi possível alterar o status.',
                            icon: 'error',
                            confirmButtonText: 'Ok'
                        });

                        return;
                    }

                    const unidadeId = $(
                        '#sorteios-unidade-id'
                    ).val();

                    const linha = $(
                        'tr[data-unidade-id="' +
                        unidadeId +
                        '"]'
                    );

                    /*
                    * Atualiza o status armazenado no botão
                    * para que o modal abra com o valor atual.
                    */
                    linha
                        .find('.sorteios-alterar-status')
                        .attr(
                            'data-status',
                            response.data.status
                        )
                        .data(
                            'status',
                            response.data.status
                        );                    

                    const colunaStatus = linha.find(
                        '.sorteios-coluna-status'
                    );

                    const badge = colunaStatus.find(
                        '.sorteios-status'
                    );

                    /*
                    * Atualiza o valor utilizado
                    * pelo filtro do DataTable.
                    */
                    colunaStatus.attr(
                        'data-search',
                        response.data.status
                    );

                    /*
                    * Atualiza o texto exibido.
                    */
                    badge.text(
                        response.data.label
                    );

                    /*
                    * Remove a classe de status anterior.
                    */
                    badge.removeClass(
                        function (index, className) {

                            return (
                                className
                                    .split(' ')
                                    .filter(function (classe) {
                                        return classe.indexOf(
                                            'sorteios-status-'
                                        ) === 0;
                                    })
                                    .join(' ')
                            );
                        }
                    );

                    /*
                    * Adiciona a nova classe de status.
                    */
                    badge.addClass(
                        'sorteios-status-' +
                        response.data.statusClass
                    );

                    /*
                    * Atualiza os contadores dos status.
                    */
                    atualizarResumoStatus();

                    /*
                    * Faz o DataTables reler os dados
                    * atuais do DOM.
                    */
                    dataTable
                        .rows()
                        .invalidate('dom')
                        .draw(false);

                    $('#modal-alterar-status')
                        .fadeOut(150);

                    swal.fire({
                        title: 'Sucesso!',
                        text: response.data.message,
                        icon: 'success',
                        confirmButtonText: 'Ok'
                    });
                    
                },
                error: function () {

                    swal.fire({
                        title: 'Erro!',
                        text: 'Ocorreu um erro ao alterar o status.',
                        icon: 'error',
                        confirmButtonText: 'Ok'
                    });
                },
                complete: function () {

                    botao.prop('disabled', false);
                }
            });
        }
    );  
    
    /*
    * Limpar filtros.
    */
    $('#sorteios-limpar-filtros').on(
        'click',
        function () {

            /*
            * Seleciona novamente todas as DREs.
            */
            $('[data-filtro-checkbox="dre"]')
                .prop('checked', true);

            /*
            * Seleciona novamente todos os status.
            */
            $('[data-filtro-checkbox="status"]')
                .prop('checked', true);

            /*
            * Limpa o filtro pelo nome da unidade.
            */
            $('#filtro-nome-unidade')
                .val('');

            /*
            * Aplica novamente os filtros.
            */
            atualizarFiltros();
        }
    );

});