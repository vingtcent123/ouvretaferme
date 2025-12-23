class CompanyConfiguration {

	static changeHasVat() {

		const form = qs('#beta-form');

		const hasVat = !!parseInt(form.qs('[name="hasVat"]:checked')?.value || 0);

		form.qsa('[data-wrapper="vatFrequency"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}
	static changeHasSoftware() {

		const form = qs('#beta-form');

		const hasSoftware = !!parseInt(form.qs('[name="hasSoftware"]:checked')?.value || 0);

		form.qsa('[data-wrapper="software"]', wrapper => hasSoftware ?
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

}
