class Configuration {

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
