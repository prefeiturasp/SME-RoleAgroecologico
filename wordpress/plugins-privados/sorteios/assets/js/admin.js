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