var consultaLimiteDiarioAtual = 0;
var datasLotadasCalendario = new Set();

function atualizaEstiloDatasLotadas() {
    jQuery('#calendario .day.limite-diario-atingido')
        .removeClass('limite-diario-atingido').removeAttr('title');
    datasLotadasCalendario.forEach(function (dia) {
        jQuery('#calendario .day[data-date="' + dia + '"]')
            .addClass('limite-diario-atingido')
            .attr('title', 'Limite diário de agendamentos atingido');
    });
}

async function validaLimiteDiarioCalendario(dia) {
    const consulta = ++consultaLimiteDiarioAtual;
    try {
        const url = new URL(calendarioApi.disponibilidadeDia);
        url.searchParams.set('data', dia);
        const resposta = await fetch(url, {cache: 'no-store'});
        const resultado = await resposta.json();
        if (consulta !== consultaLimiteDiarioAtual) return false;
        if (!resposta.ok) throw new Error('Falha na consulta');
        if (resultado.disponivel !== true) {
            datasLotadasCalendario.add(dia);
            atualizaEstiloDatasLotadas();
            await Swal.fire({
                icon: 'info',
                text: 'Esta data já atingiu o limite diário de agendamentos. Selecione outra data.'
            });
            return false;
        }
        datasLotadasCalendario.delete(dia);
        atualizaEstiloDatasLotadas();
        return true;
    } catch (erro) {
        if (consulta !== consultaLimiteDiarioAtual) return false;
        await Swal.fire({icon: 'error', text: 'Não foi possível consultar a disponibilidade. Tente novamente.'});
        return false;
    }
}

jQuery(function ($) {

	const paginaAtual = window.location.pathname; // Exemplo: "/pagina"
	
	if(paginaAtual != '/agendamento/' && paginaAtual.split('/')[1] != 'roteiro'){
		localStorage.removeItem('dataSelecionadaCalendario');
		localStorage.removeItem('alunos-com-dieta');
		localStorage.removeItem('agendamento');
	}

	if(paginaAtual == '/agendamento/' || paginaAtual.split('/')[1] == 'roteiro'){
		moment.locale('pt-br');

		if (moment.updateLocale) {
			moment.updateLocale('pt-br', { week: { dow: 0, doy: 4 } });
		}

		var hoje = '';
		var mesSelecionado = moment().add(0, 'month');

		//Verifica a data permitida
		var dataMinPermitidaForm = retornaDataMinPermitida();
		
		let arrDatas = retornaMesCalendarioSelecionado();

		let qtdMes = arrDatas.qtdMes;
		hoje = arrDatas.dataMoment;

		var selecionada = arrDatas.selecionada;;

		if(qtdMes > 0){
			mesSelecionado = moment().add(qtdMes, 'month');
		}

		var anoAtual = hoje.year();
  
		// Anos disponíveis no select
		var anos = [];
		for (var a = anoAtual; a <= anoAtual + 1; a++) {
			anos.push({ year: a });
		}
	
		// Datas enviadas pelo PHP via wp_localize_script
		var passeioDatas = {
			disponiveis: datas.disponiveis || [],
			bloqueadas: datas.indisponiveis || [],
			feriados: ['2025-08-16']
		};
 

		if(paginaAtual == '/agendamento/'){
			setTimeout(function(){
				$('#select-datas-disponiveis').val(selecionada);
			},1000);
		} else if(paginaAtual.split('/')[1] == 'roteiro'){
			let dataCalendario = localStorage.getItem('dataSelecionadaCalendario');
			if (dataCalendario){
				selecionada = dataCalendario;
			} 
		}


		// Template CLNDR
		var template = `<div class="clndr-controls">
							<select id="clndr-year" class="clndr-year">
							<% _.each(extras.years, function(year) { %>
								<option value="<%= year.year %>"><%= year.year %></option>
							<% }); %>
							</select>
							<select id="clndr-month" class="clndr-month">
							<% _.each(extras.months, function(month) { %>
								<option value="<%= month.num %>"><%= month.name %></option>
							<% }); %>
							</select>
						</div>
					
						<div class="clndr-grid">
							<% _.each(daysOfTheWeek, function(day) { %>
							<div class="header-day"><%= day %></div>
							<% }); %>
							<% _.each(days, function(day) { %>
							<div class="<%= day.classes %>" data-date="<%= day.date.format('YYYY-MM-DD') %>"><%= day.day %></div>
							<% }); %>
						</div>`;
	
		window.addEventListener('data-agendamento-validada', function (evento) {
			selecionada = evento.detail;
			if (calendario) calendario.render();
		});
		// Inicializa CLNDR
		var calendario = $('#calendario').clndr({
		template: template,
		weekOffset: 0, // Domingo
		daysOfTheWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
		startWithMonth: mesSelecionado,
		extras: {
			months: [
				{ name: 'Janeiro', num: 0 },
				{ name: 'Fevereiro', num: 1 },
				{ name: 'Março', num: 2 },
				{ name: 'Abril', num: 3 },
				{ name: 'Maio', num: 4 },
				{ name: 'Junho', num: 5 },
				{ name: 'Julho', num: 6 },
				{ name: 'Agosto', num: 7 },
				{ name: 'Setembro', num: 8 },
				{ name: 'Outubro', num: 9 },
				{ name: 'Novembro', num: 10 },
				{ name: 'Dezembro', num: 11 }
			],
			years: anos
		},
		clickEvents: {
			click: async function (target) {
				const dia = target.date.format('YYYY-MM-DD');
				if (passeioDatas.bloqueadas.includes(dia)) {
					Swal.fire({icon: 'info', text: 'Data indisponível para reserva.'});
					return;
				}
				if (!passeioDatas.disponiveis.includes(dia)) {
					return;
				}
				if (!verificaDataPermitida(dia, retornaDataMinPermitida())) {
					return;
				}
				if (!await validaLimiteDiarioCalendario(dia)) {
					return;
				}

				selecionada = dia;
				localStorage.setItem('dataSelecionadaCalendario', dia);
				if (paginaAtual === '/agendamento/') {
					const agendamento = JSON.parse(localStorage.getItem('agendamento') || '{}');
					agendamento.dataAgendamento = dia;
					localStorage.setItem('agendamento', JSON.stringify(agendamento));
					$('#select-datas-disponiveis').val(dia);
				}
				window.dispatchEvent(new CustomEvent('data-agendamento-validada', {detail: dia}));
				calendario.render();
			}
		},
		doneRendering: function () {

			// sincroniza selects
			$('#clndr-month').val(hoje.month()); // 0-11
			$('#clndr-year').val(hoje.year());
			
			// Adiciona classes visuais
			passeioDatas.bloqueadas.forEach(function (d) {
				$('.day[data-date="' + d + '"]').addClass('bloqueada');
			});
	
			passeioDatas.disponiveis.forEach(function (d) {
				const data = $('.day[data-date="' + d + '"]');
				if (!data.hasClass('bloqueada') && !data.hasClass('adjacent-month')) {
					data.addClass('disponivel');
				}
			});
	
			passeioDatas.feriados.forEach(function (d) {
				$('.day[data-date="' + d + '"]').addClass('feriado');
			});
	
			if (selecionada) {
				$('.day[data-date="' + selecionada + '"]').addClass('selected');
			}

			atualizaEstiloDatasLotadas();

			// Atualiza selects conforme mês/ano exibido no calendário
			if (calendario && calendario.month) {
				$('#clndr-month').val(calendario.month.month());
				$('#clndr-year').val(calendario.month.year());
			}
	
			// Eventos de troca
			$('#clndr-month').off('change').on('change', function () {
			//   Aluaniza a data docalendário al alterar o select
			let dataSelecionadaCalendario = localStorage.getItem('dataSelecionadaCalendario');
			jQuery('.day[data-date="' + dataSelecionadaCalendario + '"]').removeClass('selected');

			var novoMes = parseInt($(this).val(), 10);
				if (calendario && calendario.setMonth) {
					calendario.setMonth(novoMes);
				}
			});

			$('#clndr-year').off('change').on('change', function () {
				var novoAno = parseInt($(this).val(), 10);
				if (calendario && calendario.setYear) {
					calendario.setYear(novoAno);
					calendario.setMonth(11);
				}
			});
		}
		});
		
	}


  });

  function retornaMesCalendarioSelecionado(){
	let hoje = (moment.tz ? moment.tz('America/Sao_Paulo') : moment()).startOf('day');
	let mesAtual = hoje.month();
	let anoAtual = hoje.year();

	let mesSelecionado = mesAtual;
	let anoSelecionado = anoAtual;

	let dataMomentSelecionada = '';

	var dataSelecionada = localStorage.getItem('dataSelecionadaCalendario');

	let selecionada = null;

	if(dataSelecionada){
		dataMomentSelecionada = moment(dataSelecionada, "YYYY-MM-DD");
		mesSelecionado = dataMomentSelecionada.month();
		anoSelecionado = dataMomentSelecionada.year();
		selecionada = dataSelecionada;
	} else {
		dataMomentSelecionada = hoje
	}
	let qtdMes = 0;
	
	if(anoSelecionado > anoAtual){
		anoSelecionado = anoSelecionado - anoAtual;
		let qtdMesRestanteAno = mesAtual - 11;
		qtdMes = mesSelecionado + qtdMesRestanteAno;
	} else {
		if (mesSelecionado > mesAtual){
			qtdMes = mesSelecionado - mesAtual;
		}
	}

	return {"dataMoment":dataMomentSelecionada,"qtdMes":qtdMes,"selecionada":selecionada};
  }

  function verificaDataPermitida(dataCalendario, dataPermitida){

	// 2. Criar objetos Date a partir das strings
	var data1 = new Date(dataCalendario);
	var data2 = new Date(dataPermitida);

	// 3. Comparar os milissegundos
	if (data1.getTime() < data2.getTime()) {
		Swal.fire({
			position: "center",
			title: '<small>Data não permitida!</small>',
			html: '<b>É necessário um prazo mínimo de 14 dias a partir da data de hoje!</b>',
			showConfirmButton: false,
			timer: 4000
		});

		return false;
	} else if(data1.getTime() >= data2.getTime()){
		return true;
	}
  }

  function retornaDataMinPermitida(){
		let dias = 14; //Quantidades de dias somados a data de hoje
		let dataAtual = new Date();            
		let previsao = dataAtual;

		previsao.setDate(dataAtual.getDate() + dias);  		
		n = previsao.getFullYear()  +"-" + (previsao.getMonth() + 1)+ "-" + previsao.getDate();
		return n;
	}

	function retornaCalendario(){
		return calendario;
	}
  