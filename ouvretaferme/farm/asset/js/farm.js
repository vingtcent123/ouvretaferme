class Farm {

	static renderSeriesDropdown(target) {

		target.addEventListener('dropdownBeforeShow', (e) => {

			let h = '';

			ref('plant', (plant) => {
				h += '<a '+ 'onclick'.attr('Farm.scrollSeries("[data-ref=\''+ plant.dataset.ref +'\']")') +'>'+ plant.qs('.series-item-title-plant').outerHTML +'</a>';
			}, null, '|=');

			e.detail.list.qs('#farm-subnav-plants').renderInner(h);

		});

	}

	static scrollSeries(id) {

		// Ajustements liés au position: sticky
		let boundsReference;

		if(document.body.matches('[data-touch="yes"]')) {
			boundsReference = qs('#farm-subnav').getBoundingClientRect();
		} else {
			boundsReference = qs('.series-item-header').getBoundingClientRect();
		}

		const bounds = qs(id).getBoundingClientRect();

		setTimeout(() => window.scrollTo(0, bounds.top - boundsReference.bottom), 10);

	}

	static changeSearchFamily(target) {

		const seenField = target.firstParent('form').qs('.bed-rotation-search-seen');

		if(target.value) {
			seenField.classList.remove('hide');
		} else {
			seenField.classList.add('hide');
		}

	}

	static changeCalendarMonth(farmId, target) {

		const form = target.firstParent('form');

		new Ajax.Query(form)
			.method('get')
			.url('/farm/farm:calendarMonth?id='+ farmId +'&calendarMonthStart='+ form.qs('[name="calendarMonthStart"]').value +'&calendarMonthStop='+ form.qs('[name="calendarMonthStop"]').value)
			.fetch();

	}

}