class FinancialYear {

	static changeHasVat(target) {

		const hasVat = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="vatFrequency"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static displayCharges(button) {

		qsa('table.financial-year-cca-table tr.hide', element => element.removeHide());
		button.classList.add('hide');

	}

}
