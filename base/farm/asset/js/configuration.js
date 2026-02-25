class Configuration {

	static changeInvoiceMandatoryTexts() {

		const checked = !!parseInt(qs('[name="invoiceMandatoryTexts"]:checked').value);

		if(checked) {
			qs('[data-wrapper="invoiceCollection"]').removeHide();
			qs('[data-wrapper="invoiceLateFees"]').removeHide();
			qs('[data-wrapper="invoiceDiscount"]').removeHide();
			qs('[data-wrapper="invoiceMandatoryTexts"] .form-info').hide();
		} else {
			qs('[data-wrapper="invoiceCollection"]').hide();
			qs('[data-wrapper="invoiceLateFees"]').hide();
			qs('[data-wrapper="invoiceDiscount"]').hide();
			qs('[data-wrapper="invoiceMandatoryTexts"] .form-info').removeHide();
		}

	}

	static changeHasVat(target) {

		const hasVat = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="vatNumber"], [data-wrapper="defaultVat"], [data-wrapper="defaultVatShipping"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static accountDissociation(target) {

		const proAccountTarget = target.firstParent('.form-group').nextSibling;
		const specificLabelTarget = target.firstParent('.form-group').qs('label .form-info');

		if(proAccountTarget.classList.contains('hide')) {

			proAccountTarget.removeHide();
			specificLabelTarget.removeHide();


		} else {

			proAccountTarget.hide();
			specificLabelTarget.hide();

			const element = proAccountTarget.qs(' a[class="autocomplete-empty"]');
			AutocompleteField.empty(element);

		}
	}
}
