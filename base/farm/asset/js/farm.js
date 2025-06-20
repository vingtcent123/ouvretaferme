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
	static getFarmDataBySiret(target) {

		if(target.value.length !== 14) {
			return;
		}

		const siret = target.value;

		const form = target.firstParent('form');

		new Ajax.Query(form)
			.method('get')
			.url('https://suggestions.pappers.fr/v2?cibles=siret&q='+ siret)
			.fetch()
			.then((response) => {
				if(response.resultats_siret[0] === undefined) {
					return;
				}
				if(form.qs('[name="legalName"]').value.length === 0) {
					form.qs('[name="legalName"]').value = response.resultats_siret[0].nom_entreprise;
				}
				if(form.qs('[name="legalStreet1"]').value.length === 0) {
					form.qs('[name="legalStreet1"]').value = response.resultats_siret[0].siege.adresse_ligne_1;
				}
				if(form.qs('[name="legalStreet2"]').value.length === 0) {
					form.qs('[name="legalStreet2"]').value = response.resultats_siret[0].siege.adresse_ligne_2;
				}
				if(form.qs('[name="legalPostcode"]').value.length === 0) {
					form.qs('[name="legalPostcode"]').value = response.resultats_siret[0].siege.code_postal;
				}
				if(form.qs('[name="legalCity"]').value.length === 0) {
					form.qs('[name="legalCity"]').value = response.resultats_siret[0].siege.ville;
				}
			});

	}

}
