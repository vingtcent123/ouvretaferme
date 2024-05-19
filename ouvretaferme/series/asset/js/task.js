document.delegateEventListener('change', '[data-action="task-fertilizer-element"], [data-action="task-fertilizer-tool"]', function(e) {

	let request = document.location.href;

	request = request.setArgument('element', qs('[data-action="task-fertilizer-element"]').value);
	request = request.setArgument('tool', qs('[data-action="task-fertilizer-tool"]').value);

	new Ajax.Query(this)
		.method('get')
		.url(request)
		.fetch();

});

document.addEventListener('scroll', function() {

	if(
		window.scrollY === 0 &&
		document.body.dataset.template.includes(' farm-planning-scrolling')
	) {
		document.body.dataset.template = document.body.dataset.template.replace(' farm-planning-scrolling', '');
	}

});

document.delegateEventListener("scroll", '[data-planning-scroll]', (e) => {

	const currentScroll = parseInt(e.target.dataset.planningScroll);
	const newScroll = e.target.scrollTop;
	const deltaScroll = newScroll - currentScroll;

	const documentScrollable = qs('#main-nav').getBoundingClientRect().height + qs('header').getBoundingClientRect().height;

	if(
		deltaScroll > 0 &&
		window.scrollY < documentScrollable &&
		document.body.dataset.template.includes(' farm-planning-scrolling') === false
	) {

		document.body.dataset.template += ' farm-planning-scrolling';

		window.scrollTo({
			left: 0,
			top: documentScrollable,
			behavior: 'smooth'
		});

	}

	if(
		newScroll === 0 &&
		window.scrollY > 0 &&
		document.body.dataset.template.includes(' farm-planning-scrolling')
	) {

		setTimeout(
			() => document.body.dataset.template = document.body.dataset.template.replace(' farm-planning-scrolling', ''),
			500
		);

		window.scrollTo({
			left: 0,
			top: 0,
			behavior: 'smooth'
		});

	}

});

document.delegateEventListener("scroll", '#planning-container-daily', (e) => {

	if(Task.isScrollByDay()) {
		return;
	}

	const scroll = e.target.scrollLeft;
	const week = e.target.dataset.week;

	Task.setScrollStorage([week, scroll]);

});

document.delegateEventListener("click", '[data-action="task-checkbox"]', (e) => {

	e.preventDefault();
	e.stopImmediatePropagation();

	new Ajax.Query(e.target)
		.url('/series/task:doCheck')
		.body(e.delegateTarget.post())
		.fetch();

});


document.delegateEventListener("touchend", '#planning-container-daily', (e) => {

	if(Task.isScrollByDay() === false) {
		return;
	}

	if(isTouchDown === false) {
		return;
	}

	const cache = Task.getScrollStorage();

	if(cache.length === 0) {
		return;
	}

	const position = qs('#planning-container-daily').scrollLeft / window.innerWidth + 1;
	let screen = null;

	if(position >= cache[1] - 0.05 && position <= cache[1] + 0.05) {
		screen = cache[1];
	} else if(position >= cache[1]) {
		screen = cache[1] + 1;
	} else {
		screen = cache[1] - 1;
	}

	if(screen !== null) {

		qs('#planning-container-daily').style.overflowX = 'hidden';

		const startPosition = qs('#planning-container-daily').scrollLeft;
		const newPosition = window.innerWidth * (screen - 1);

		const scrollFunction = function(value) {

			return () => {

				if(value === newPosition) {
					qs('#planning-container-daily').style.overflowX = '';
				}

				qs('#planning-container-daily').scrollTo({
					left: value,
					top: 0
				});
			};

		};

		if(newPosition > startPosition) {

			for(move = startPosition; ; move += 5) {

				if(move >= newPosition) {
					move = newPosition;
				}

				setTimeout(scrollFunction(move), (move - startPosition) / 2.5);

				if(move === newPosition) {
					break;
				}


			}

		} else {

			for(move = startPosition; ; move -= 5) {

				if(move <= newPosition) {
					move = newPosition;
				}

				setTimeout(scrollFunction(move), startPosition - move);

				if(move === newPosition) {
					break;
				}


			}

		}

		cache[1] = screen;
		Task.setScrollStorage(cache);

	}

});

document.delegateEventListener('click', 'input[data-action="task-write-action-change"]', function(e) {

	const form = this.firstParent('form');

	if(this.dataset.fqn === 'recolte') {
		form.qs('[data-wrapper="harvestQuality"]', node => node.classList.remove('hide'))
	} else {
		form.qs('[data-wrapper="harvestQuality"]', node => node.classList.add('hide'))
	}

	if(this.dataset.fqn === 'fertilisation') {
		form.qs('[data-wrapper="fertilizer"]', node => node.classList.remove('hide'))
	} else {
		form.qs('[data-wrapper="fertilizer"]', node => node.classList.add('hide'))
	}

	form.ref('tools', wrapper => {

		new Ajax.Query(this)
			.url('/series/task:getToolsField')
			.body({
				farm: wrapper.dataset.farm,
				action: this.value
			})
			.fetch()
			.then((json) => {

				wrapper.renderInner(json.field);

			});

	});

});

document.addEventListener('scroll', function() {

	const months = qs('#tasks-calendar-months');

	if(months === null) {
		return;
	}

	if(months.getBoundingClientRect().top <= 9 * rem()) {
		qsa('.farm-subnav-planning-year', node => node.classList.remove('hide-touch'));
	} else {
		qsa('.farm-subnav-planning-year', node => node.classList.add('hide-touch'));
	}


});

document.delegateEventListener('dropdownAfterShow', '.tasks-time-day-full-link', function(e) {

	const list = e.detail.list;
	list.qs('[type="number"]').select();

});

document.delegateEventListener('scroll', '#planning-year-weeks', function() {

	const wrapper = qs('#planning-year-weeks');

	if(wrapper === null) {
		return;
	}

	const scrollTarget = wrapper.scrollLeft + window.innerWidth / 2;

	// On sélectionne le mois correspond au scroll
	Array.from(wrapper.qsa('.planning-year-week')).every((week) => {

		if(scrollTarget > week.offsetLeft && scrollTarget <= week.offsetLeft + week.offsetWidth +  2 * rem()) {

			// On va chercher les deux mois attachés à la semaine
			const scrollMonths = [
				week.dataset.months.split(/[- ]/)[1],
				week.dataset.months.split(/[- ]/)[3]
			];

			Task.highlightPlanningMonth(scrollMonths);

			return false;
		} else {
			return true;
		}

	});

});

document.delegateEventListener('autocompleteSelect', '#task-create-plant', function(e) {

	let request = document.location.href;

	if(e.detail.value) {
		request = request.setArgument('plant', e.detail.value);
	} else {
		request = request.removeArgument('plant');
	}

	new Ajax.Query(this)
		.method('get')
		.url(request)
		.fetch();

});

class Task {

	static updateCultivationCheckbox(target) {

		const form = target.firstParent('form');

		const body = new URLSearchParams();

		let sharedSeriesChecked = false;

		const seriesChecked = form.qsa('input[name="series[]"]:checked', (seriesInput) => {

			const series = seriesInput.value;

			form.qs('input[type="radio"][name="cultivation['+ series +']"]:checked, input[type="hidden"][name="cultivation['+ series +']"]', cultivationInput => {
				if(cultivationInput.value) {
					body.append('cultivations[]', cultivationInput.value)
				} else {
					sharedSeriesChecked = true;
				}
			});

		});


		form.qsa('input[type="radio"][name="action"]:not([data-fqn="recolte"])', action => seriesChecked.length === 0 ?
			action.disabled = true :
			action.disabled = false
		);

		const actionRadioRecolte = form.qs('input[type="radio"][name="action"][data-fqn="recolte"]');
		const actionHiddenRecolte = form.qs('input[type="hidden"][name="action"][data-fqn="recolte"]');

		if(Array.from(body).length > 0) {

			body.append('farm', form.dataset.farm);

			new Ajax.Query(target)
				.body(body)
				.url('/series/task:getCreateCollectionFields')
				.fetch()
				.then((json) => {

					if(actionRadioRecolte) {

						if(actionRadioRecolte.matches(':checked')) {
							form.qs('[data-wrapper="harvestQuality"]', node => node.classList.remove('hide'))
						} else {
							form.qs('[data-wrapper="harvestQuality"]', node => node.classList.add('hide'))
						}

					} else if(actionHiddenRecolte) {
						form.qs('[data-wrapper="harvestQuality"]', node => node.classList.remove('hide'))
					}

				});

			if(actionRadioRecolte) {
				// Pas de récolte possible si intervention partagée sélectionnée
				actionRadioRecolte.disabled = sharedSeriesChecked;
			}

		} else {

			qs('#task-create-variety', node => node.renderInner(''));

			if(actionRadioRecolte) {

				qs('#task-create-quality', node => node.renderInner(''));

				actionRadioRecolte.disabled = true;
				actionRadioRecolte.checked = false;

			}

			if(actionHiddenRecolte) {

				qs('#task-create-quality', node => node.renderInner(''));

			}
		}

	}

	static selectSeriesCheckbox(target) {

		const tbody = target.firstParent('tbody');

		if(target.checked === false) {

			const cultivationsCheckbox = tbody.qsa('input[type="radio"][name="cultivation['+ target.value +']"]');

			if(cultivationsCheckbox.length > 0) {

				target.disabled = true;
				cultivationsCheckbox.forEach(input => input.checked = false);

			}

		}

		this.updateCultivationCheckbox(target);

	}

	static selectCultivationRadio(target) {

		const tbody = target.firstParent('tbody');

		const seriesCheckbox = tbody.qs('[name="series[]"]');
		seriesCheckbox.disabled = null;
		seriesCheckbox.checked = true;

		this.updateCultivationCheckbox(target);

	}

	static selectAll(target, plant) {

		const table = qs('#series-field-table');

		if(target.checked === false) {

			table.qsa('[name^="series"]', fieldSeries => {

				fieldSeries.checked = false;

				if(fieldSeries.dataset.cultivations > 1) {

					fieldSeries.disabled = true;

					table.qs('input[type="radio"][name="cultivation['+ fieldSeries.value +']"]:checked', fieldCultivation => fieldCultivation.checked = false);

				}

			});

		} else {

			const tdCheckbox = qs('#series-field-filter');
			tdCheckbox.qsa('input[type="checkbox"]', checkbox => {
				if(checkbox !== target) {
					checkbox.checked = false;
				}
			});

			table.qsa('[name^="series"]', fieldSeries => {

				if(fieldSeries.dataset.cultivations > 1) {

					table.qs(
						'input[type="radio"][name="cultivation['+ fieldSeries.value +']"]'+ (plant ? '[data-plant="'+ plant +'"]' : ':not([data-plant])'),
						fieldCultivation => {

							fieldCultivation.checked = true;

							fieldSeries.checked = true;
							fieldSeries.disabled = null;

						},
						() => {

							fieldSeries.checked = false;
							fieldSeries.disabled = true;

						}
					);

				} else {
					fieldSeries.checked = true;
				}

			});

		}

		this.updateCultivationCheckbox(table);

	}

	static createSelectCultivation(target) {

		let request = document.location.href;

		if(target.value) {
			request = request.setArgument('cultivation', target.value);
		} else {
			request = request.removeArgument('cultivation');
		}

		new Ajax.Query()
			.method('get')
			.url(request)
			.fetch();

	}

	static updateSelectCultivation(target) {

		const form = target.firstParent('form');

		if(target.value === '') {
			form.ref('varieties', wrapper => wrapper.renderInner(''));
		} else {

			form.ref('varieties', wrapper => {

				new Ajax.Query(target)
					.url('/series/task:getVarietiesField')
					.body({
						id: form.qs('[name="id"]').value,
						cultivation: target.value
					})
					.fetch()
					.then((json) => {

						wrapper.renderInner(json.field);

					});

			});

		}

	}

	static changePlanned(target, period) {

		const wrapper = target.firstParent('.task-write-planned');

		wrapper.dataset.period = period;
		wrapper.qsa('div.task-write-planned-'+ period +' input', field => field.removeAttribute('disabled'));
		wrapper.qsa('div.task-write-planned-field:not(.task-write-planned-'+ period +') input', field => field.setAttribute('disabled', 'disabled'));

		if(period === 'unplanned') {

			this.changeRepeat(target, false);

			wrapper.qsa('.task-write-norepeat, .task-write-repeat', node => node.classList.add('hide'));

		} else {
			wrapper.qs('.task-write-repeat', node => node.classList.remove('hide'));
		}

	}

	static changeRepeat(target, repeat) {

		const wrapper = target.firstParent('.task-write-planned');

		if(repeat) {

			wrapper.qs('.task-write-repeat', node => node.classList.add('hide'));
			wrapper.qsa('.task-write-norepeat, .task-write-repeat-field', node => node.classList.remove('hide'));

			wrapper.qsa('.task-write-repeat-field input', field => field.removeAttribute('disabled'));

		} else {

			wrapper.qs('.task-write-repeat', node => node.classList.remove('hide'));
			wrapper.qsa('.task-write-norepeat, .task-write-repeat-field', node => node.classList.add('hide'));

			wrapper.qsa('.task-write-repeat-field input', field => field.setAttribute('disabled', 'disabled'));

		}

	}

	static isScrollByDay() {

		return (
			isTouch() &&
			window.matchMedia('(max-width: 575px)').matches
		);

	}

	static getScrollStorage() {

		const value = JSON.parse(localStorage.getItem('task-scroll-'+ (this.isScrollByDay() ? 'screen' : 'scroll')) || '[]');

		if(Date.now() - value[2] > 3600 * 1000) { // Expiration à une heure
			localStorage.removeItem(value);
			return [];
		}

		return value;

	}

	static setScrollStorage(value) {

		value[2] = Date.now();

		localStorage.setItem('task-scroll-'+ (this.isScrollByDay() ? 'screen' : 'scroll'), JSON.stringify(value));

	}

	static scrollPlanningDaily(target, screen) {

		const cache = this.getScrollStorage();
		const useCache = (
			cache.length !== null &&
			target.dataset.week === cache[0]
		);

		let position;

		if(this.isScrollByDay()) {

			if(useCache) {
				screen = cache[1];
			} else {
				this.setScrollStorage([target.dataset.week, screen]);
			}

			position = document.documentElement.clientWidth * (screen - 1);

		} else {

			if(useCache) {
				position = cache[1];
			} else {

				const plannerWidth = (27 * 8) * rem();
				const screenWidth = document.documentElement.clientWidth;

				position = (screen - 1) * 27 * rem() + 13.5 * rem() - screenWidth / 2;
				position = Math.max(0, position);
				position = Math.min(plannerWidth, position);

			}

		}

		target.scroll(position, 0);

	}

	static selectPlanningMonth(year, month) {

		this.highlightPlanningMonth([month]);

		const weeks = qsa('#planning-year-weeks > [data-months~="'+ year +'-'+ month +'"]');

		// Current week ?
		const currentWeek = qs('#planning-year-weeks > [data-months~="'+ year +'-'+ month +'"].planning-year-week-current');

		let targetOffset;

		if(currentWeek !== null) {

			const startOffset = currentWeek.offsetLeft;
			targetOffset = startOffset - (window.innerWidth - currentWeek.offsetWidth) / 2;

		} else {

			const startOffset = weeks[0].offsetLeft;
			targetOffset = startOffset - rem();

		}

		const wrapper = qs('#planning-year-weeks');
		wrapper.scroll(targetOffset, 0);

		// Enregistrement de l'URL
		const base = qs('#tasks-calendar-months').dataset.url;
		Lime.History.replaceState(base +'/'+ year +'/'+ month);

	}

	static clickPlanningMonth(year, month) {

		this.selectPlanningMonth(year, month);

	}

	static highlightPlanningMonth(months) {

		const wrapper = qs('#tasks-calendar-months');

		if(months.filter(month => wrapper.qs('.tasks-calendar-month[data-month="'+ month +'"].selected')).length > 0) {
			return;
		} else {

			const month = months[0];

			wrapper.qsa('.tasks-calendar-month', node => node.classList.remove('selected'));
			wrapper.qs('.tasks-calendar-month:nth-child('+ month +')').classList.add('selected');

		}

	}

	static checkPlanningAction(selector) {

		const wrapper = selector.firstParent('.tasks-planning-items');

		wrapper.qsa('[name="batch[]"]', node => node.checked = selector.checked);

		this.changePlanningSelection();

	}

	static checkPlanningSeries(selector) {

		qsa('#series-task-wrapper [name="batch[]"]', node => node.checked = selector.checked);

		this.changePlanningSelection();

	}

	static checkPlanningPlant(selector, plant) {

		const wrapper = selector.firstParent('.tasks-planning-items');

		wrapper.qsa('[data-filter-plant="'+ plant +'"] [name="batch[]"]', node => node.checked = selector.checked);

		this.changePlanningSelection();

	}

	static hidePlanningSelection() {

		qs('#batch-several').hide();

		qsa('[name="batch[]"]:checked', (field) => field.checked = false);
		qsa('[name="batchAction[]"]:checked', (field) => field.checked = false);

	}

	static toggleDistributionHarvestTable() {

		const target = qs("#task-distribution-dates");

		if(target === null) {
			return;
		}

		const form = target.firstParent('form');

		const date = form.qs('[name="date"]').value;

		if(target.matches(':not([data-day~="'+ date +'"])')) {
			target.hide();
		} else {
			target.removeHide();
			target.qsa('tr', tr => tr.matches('[data-day~="'+ date +'"]') ? tr.removeHide() : tr.hide());
		}

	}

	static changePlanningSelection() {

		const menu = qs('#batch-several');
		const one = qs('#batch-one');
		const selection = qsa('[name="batch[]"]:checked');

		switch(selection.length) {

			case 0 :
				one.hide();
				menu.hide();
				break;

			case 1 :
				one.removeHide();
				selection[0].firstParent('.batch-item').insertAdjacentElement('afterbegin', one);
				menu.hide();
				return this.updateBatchMenu(selection);

			default :
				one.hide();
				menu.removeHide();
				menu.style.zIndex = Lime.getZIndex();
				return this.updateBatchMenu(selection);

		}

	}

	static updateBatchMenu(selection) {

		qs('#batch-menu-count').innerHTML = selection.length;

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa('.batch-ids', node => node.innerHTML = newIds);

		if(selection.filter(':not([data-batch~="harvest"])').length > 0) {
			qsa(".batch-menu-harvest", button => button.hide());
		} else {

			// Récolte = même plante, même variété
			let plantVariety = null;
			let hide = false;

			selection.forEach((field) => {

				const task = field.firstParent('.batch-item');
				const taskPlantVariety = task.dataset.filterPlant +'-'+ task.dataset.filterVariety +'-'+ task.dataset.filterHarvestUnit +'-'+ task.dataset.filterHarvestQuality;

				if(plantVariety === null) {
					plantVariety = taskPlantVariety;
				} else if(plantVariety !== taskPlantVariety) {
					hide = true;
				}


			});

			if(hide) {
				qsa(".batch-menu-harvest", button => button.hide());
			} else {
				qsa(".batch-menu-harvest", button => button.removeHide());
			}

		}

		// Temps de travail = même action
		let action = null;
		let sameAction = true;

		selection.forEach((field) => {

			const task = field.firstParent('.batch-item');
			const taskAction = task.dataset.filterAction;

			if(action === null) {
				action = taskAction;
			} else if(action !== taskAction) {
				sameAction = false;
			}


		});

		if(sameAction === false) {
			qsa('.batch-menu-timesheet', button => button.hide());
		} else {
			qsa('.batch-menu-timesheet', button => button.removeHide());
		}

		if(selection.length > 1) {
			qs('.batch-menu-update', button => button.hide());
		} else {
			qs('.batch-menu-update', button => {
					 button.setAttribute('href', '/series/task:update?id='+ selection[0].value);
					 button.removeHide();
			});
		}

		if(selection.filter('[data-batch~="done"]').length > 0) {
			qsa('.batch-menu-planned', button => button.hide());
			qsa('.batch-menu-users', button => button.hide());
		} else {
			qsa('.batch-menu-planned', button => button.removeHide());

			if(selection.filter('[data-batch~="not-postpone"]').length > 0) {
				qsa('.batch-menu-planned .batch-menu-postpone', link => link.hide());
			} else {
				qsa('.batch-menu-planned .batch-menu-postpone', link => link.removeHide());
			}

			qsa('.batch-menu-users', button => button.removeHide());

			qsa('.batch-menu-users .batch-planned-user', node => {

				if(selection.filter('[data-batch~="user-'+ node.getAttribute('post-user') +'"]').length !== selection.length) {
					node.setAttribute('post-action', 'add');
					node.classList.remove('selected');
				} else {
					node.setAttribute('post-action', 'delete');
					node.classList.add('selected');
				}

			});

		}

		if(selection.filter('[data-batch~="todo"]').length > 0) {
			qsa('.batch-menu-todo', button => button.hide());
		} else {
			qsa('.batch-menu-todo', button => button.removeHide());
		}

		if(sameAction === false || selection.filter('[data-batch~="done"]').length > 0) {
			qsa('.batch-menu-done', button => button.hide());
		} else {
			qsa('.batch-menu-done', button => button.removeHide());
		}

	}

}