class Vat {

	static toggleEffectiveAt(target, hasVatAccountingOrigin) {

		const hasVatAccounting = !!parseInt(target.value);
		const form = target.firstParent('form');

		if(hasVatAccounting === !!hasVatAccountingOrigin) {
			form.qs('[data-wrapper="effectiveAt"]').classList.add('hide');
		} else {
			form.qs('[data-wrapper="effectiveAt"]').classList.remove('hide');
		}
	}

	static changeHasVat(target) {

		const hasVatAccounting = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="vatFrequency"], [data-wrapper="vatChargeability"], [data-wrapper="legalCategory"]', wrapper => hasVatAccounting ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

		form.qsa('[data-wrapper="associates"]', wrapper => hasVatAccounting && parseInt(qs('[name="legalCategory"]').value) === 6533 ? // Pour les GAEC uniquement
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}


}
