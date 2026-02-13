class FinancialYear {

	static changeLegalCategory(target) {

		const category = parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="associates"]', wrapper => (category === 6533) ? // Pour les GAEC uniquement
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static changeTaxSystem(target) {

		const taxSystem = qs('[name="taxSystem"]:checked').value;

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="accountingMode"]', wrapper => (taxSystem === 'micro-ba') ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide')
		);

		form.qsa('[data-wrapper="accountingType"]', wrapper => (taxSystem === 'micro-ba') ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide')
		);

	}

}
