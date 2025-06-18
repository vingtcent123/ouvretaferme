class Company {

	static getCompanyDataBySiret(target) {

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
				form.qs('[name="name"]').value = response.resultats_siret[0].nom_entreprise;
				form.qs('[name="addressLine1"]').value = response.resultats_siret[0].siege.adresse_ligne_1;
				form.qs('[name="addressLine2"]').value = response.resultats_siret[0].siege.adresse_ligne_2;
				form.qs('[name="postalCode"]').value = response.resultats_siret[0].siege.code_postal;
				form.qs('[name="city"]').value = response.resultats_siret[0].siege.ville;
				form.qs('[name="nafCode"]').value = response.resultats_siret[0].code_naf;
			});

	}

	static changeCalendarMonth(companyId, target) {

		const form = target.firstParent('form');

		new Ajax.Query(form)
			.method('get')
			.url('/company/company:calendarMonth?id='+ companyId +'&calendarMonthStart='+ form.qs('[name="calendarMonthStart"]').value +'&calendarMonthStop='+ form.qs('[name="calendarMonthStop"]').value)
			.fetch();

	}

}