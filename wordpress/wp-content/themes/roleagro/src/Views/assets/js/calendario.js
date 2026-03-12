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
			click: function (target) {
		
				// 1. Obter os valores das datas
				var dataMinPermitida = retornaDataMinPermitida();
				var dataCalendario = target.date._i;
				
				const date = target.date.format('YYYY-MM-DD');

				if(paginaAtual.split('/')[1] == 'roteiro'){

					let dtPermitida = verificaDataPermitida(dataCalendario, dataMinPermitida);
					if(dtPermitida){
						selecionada = dataCalendario;
						localStorage.setItem('dataSelecionadaCalendario', dataCalendario);
					}

				} else if(paginaAtual == '/agendamento/'){

					let agendamento = localStorage.getItem('agendamento');

					if(dataMinPermitidaForm){
							let permitida = verificaDataPermitida(dataCalendario, dataMinPermitidaForm);

							if(permitida){
								let dataAge = dataCalendario;
			
								if(agendamento) {
									agendamento = JSON.parse(agendamento);
									agendamento.dataAgendamento = dataAge;
								} 
								localStorage.setItem('agendamento', JSON.stringify(agendamento));
							} else {
								return;
							}
			
						if (passeioDatas.bloqueadas.includes(date)) {
							alert('Data indisponível para reserva.');
							return;
						}
				
						if (!passeioDatas.disponiveis.includes(date) && !passeioDatas.feriados.includes(date)) {
							alert('Data não disponível para seleção.');
							return;
						}

						selecionada = date;
						
					} else {
						
						let permitida = verificaDataPermitida(dataCalendario, dataMinPermitida);
						if(permitida){
							localStorage.setItem('dataSelecionadaCalendario', dataCalendario);
							selecionada = dataCalendario;

							if(paginaAtual == '/agendamento/'){
								this.agendamento.dataAgendamento = dataCalendario;
								localStorage.setItem('agendamento', JSON.stringify(agendamento));
							}
						} else {
							return;
						}
						
					}

				}
				

				calendario.render();
		
				// Atualiza input externo se existir
				if( $('#select-datas-disponiveis').length ) {
				
					// Remove a data selecionada anterior, e marca a data selcionada
					let dataSelecionadaCalendario = localStorage.getItem('dataSelecionadaCalendario');
					jQuery('.day[data-date="' + dataSelecionadaCalendario + '"]').removeClass('selected');

					localStorage.setItem('dataSelecionadaCalendario', selecionada);
					$('#select-datas-disponiveis').val(selecionada).trigger('change');
				}
			
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
  