class Configuration {

	static changeHasVat(target) {

		const hasVat = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="invoiceVat"], [data-wrapper="defaultVat"], [data-wrapper="defaultVatShipping"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

}