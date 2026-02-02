class FinancialYear {

	static changeHasVat(target) {

		const hasVat = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="vatFrequency"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

		form.qsa('[data-wrapper="vatChargeability"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

		form.qsa('[data-wrapper="legalCategory"]', wrapper => hasVat ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

		form.qsa('[data-wrapper="associates"]', wrapper => hasVat && parseInt(qs('[name="legalCategory"]').value) === 6533 ? // Pour les GAEC uniquement
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static changeLegalCategory(target) {

		const category = parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="associates"]', wrapper => (category === 6533) ? // Pour les GAEC uniquement
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static getByDate() {

		const farm;

	}

}
