class CompanyConfiguration {

	static changeHasVat() {

		const form = qs('#beta-form');

		const hasVat = !!parseInt(form.qs('[name="hasVat"]:checked')?.value || 0);

		form.qsa('[data-wrapper="vatFrequency"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static changeHelpComment() {

		const form = qs('#beta-form');

		const hasVat = !!parseInt(form.qs('[name="accountingHelped"]:checked')?.value || 0);

		form.qsa('[data-wrapper="helpComment"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static toggleBetaForm() {

		if(qs('#beta-form-container').classList.contains('hide')) {

			qs('#beta-form-container').removeHide();
			qs('.company-accounting-choose-option[data-option="yes"]').classList.add('selected');

		} else {

			qs('.company-accounting-choose-option[data-option="yes"]').classList.remove('selected');
			qs('#beta-form-container').hide()

		}

	}

}
