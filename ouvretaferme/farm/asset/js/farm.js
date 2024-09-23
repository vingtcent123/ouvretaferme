class Farm {

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