document.addEventListener('keyup', function(e) {

	Farm.changeArrowSection(e)

});

class Farm {

	static pendingSection = null;

	static changeArrowSection(e) {

		if(
			document.activeElement !== document.body ||
			document.body.classList.contains('panel-open')
		) {
			return;
		}

		if(e.key === 'ArrowLeft' || e.key === 'ArrowRight') {

			const sections = [];
			qsa('#farm-nav-sections .farm-nav-section', node => sections[sections.length] = node.dataset.section);

			let position = sections.findIndex(section => document.body.dataset.template.includes('farm-section-'+ section));

			if(position === -1) {
				position = sections.findIndex(section => document.body.dataset.template.includes('farm-'+ section));
			}

			let newPosition;

			switch(e.key) {

				case 'ArrowLeft' :
					newPosition = (position - 1 + sections.length) % sections.length;
					break;

				case 'ArrowRight' :
					newPosition = (position + 1) % sections.length;
					break;

			}

			this.setSection(sections[newPosition]);

		}

	}

	static changeSection(target, delay = 0) {

		if(this.pendingSection !== null) {
			clearTimeout(this.pendingSection);
		}

		this.pendingSection = setTimeout(() => {

			this.setSection(target.dataset.section);

			this.pendingSection = null;

		}, delay);

	}

	static setSection(section) {

		qsa('#farm-nav-sections .farm-nav-section', node => {

			const newTemplates = document.body.dataset.template.replace(' farm-section-' + node.dataset.section, '');
			document.body.dataset.template = newTemplates;

		});

		document.body.dataset.template += ' farm-section-'+ section;

	}

	static clearSection() {

		if(this.pendingSection !== null) {
			clearTimeout(this.pendingSection);
		}

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
				if(
					form.qs('[name="legalStreet1"]').value.length === 0 &&
					form.qs('[name="legalStreet2"]').value.length === 0 &&
					form.qs('[name="legalPostcode"]').value.length === 0 &&
					form.qs('[name="legalCity"]').value.length === 0
				) {
					form.qs('[name="legalStreet1"]').value = response.resultats_siret[0].siege.adresse_ligne_1;
					form.qs('[name="legalStreet2"]').value = response.resultats_siret[0].siege.adresse_ligne_2;
					form.qs('[name="legalPostcode"]').value = response.resultats_siret[0].siege.code_postal;
					form.qs('[name="legalCity"]').value = response.resultats_siret[0].siege.ville;
				}
			});

	}

}
