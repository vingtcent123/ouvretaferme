
document.delegateEventListener('autocompleteSelect', '#panel-cash-create [data-wrapper="account"] [data-autocomplete-field="account"]', function(e) {

	if(e.detail.value) {
		qs('[name="vatRate"]').value = e.detail.vatRate;
	}

});

class Cash {

	static recalculateAmount(target) {

		if(
			target.name === 'amountExcludingVat' ||
			target.name === 'vat'
		) {
			return;
		}

		const form = target.firstParent('form');

		const amountIncludingVat = parseFloat(form.qs('[name="amountIncludingVat"]').value);
		const vatRate = parseFloat(form.qs('[name="vatRate"]').value);

		if(
			isNaN(amountIncludingVat) === false &&
			isNaN(vatRate) === false
		) {

			const amountExcludingVatField = form.qs('[name="amountExcludingVat"]');
			const vatField = form.qs('[name="vat"]');

			if(vatRate > 0) {

				amountExcludingVatField.value = calculateExcludingFromIncluding(amountIncludingVat, vatRate);
				vatField.value = calculateVatFromIncluding(amountIncludingVat, vatRate);

			} else {
				amountExcludingVatField.value = amountIncludingVat;
				vatField.value = 0;
			}


		}

	}

}
