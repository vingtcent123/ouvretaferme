document.delegateEventListener('input', '#journal-deferral-set input[name$="Date"]', function() {
	Deferral.updateAmount(this.firstParent('form').getAttribute('id'));

});

document.delegateEventListener('input', '#journal-deferral-set input[name="amount"]', function() {
	Deferral.updateDates(this.firstParent('form').getAttribute('id'));
});


document.delegateEventListener('autocompleteSelect', '[data-operation="operation-deferral"]', function(e) {

	window.location.href = e.detail.link;

});


class Deferral {

	static getInitialAmount(formId) {

		return qs('#' + formId + ' input[name="operationAmount"]').value;

	}

	static updateDates(formId) {

		qs('#fieldAmount').checked = true;
		Deferral.updateField();

		const amount = qs('#' + formId + ' input[name="amount"]').value;

		const startDate = new Date(qs('#' + formId + ' input[name="startDate"]').value);

		if(startDate.toString() === 'Invalid Date') {
			return;
		}

		const initialAmount = this.getInitialAmount(formId);
		const financialYearEndDate = new Date(qs('#' + formId + ' input[name="financialYearEndDate"]').value);

		const joursN = Math.round((financialYearEndDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif
		const montantConsomme = initialAmount - amount;

		if (montantConsomme <= 0) {
			return;
		}
		const minEndDate = new Date(qs('#' + formId + ' input[name="endDate"]').getAttribute('min'));

		const dureeTotale = Math.round(joursN * (initialAmount / montantConsomme));
		const endDate = new Date(startDate.getTime());
		endDate.setDate(endDate.getDate() + dureeTotale - 1);

		if(minEndDate < endDate) {

			qs('#' + formId + ' input[name="endDate"]').setAttribute('value', endDate.toISOString().substring(0, 10));
			qs('#warning-amount-end-date').hide();

		} else {
			qs('#warning-amount-end-date').removeHide();
		}

	}

	static updateAmount(formId) {

		qs('#fieldDates').checked = true;
		Deferral.updateField();

		const startDate = new Date(qs('#' + formId + ' input[name="startDate"]').value);
		const endDate = new Date(qs('#' + formId + ' input[name="endDate"]').value);
		const financialYearEndDate = new Date(qs('#' + formId + ' input[name="financialYearEndDate"]').value);

		const initialAmount = this.getInitialAmount(formId);

		if(endDate < financialYearEndDate || endDate < startDate) {
			qs('#warning-dates-amount').removeHide();
			return;
		}

		qs('#warning-dates-amount').hide();

		const joursN = Math.round((financialYearEndDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif
		const joursTotal = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif

		const newAmount = Math.round((joursTotal - joursN) * initialAmount / joursTotal);
		qs('#' + formId + ' input[name="amount"]').setAttribute('value', newAmount);

	}

	static updateField() {

		const field = qs('[name="field"]:checked').value;

		if(field === 'dates') {
			qs('[name="amount"]').setAttribute('disabled', 'disabled');
			qs('[name="startDate"]').removeAttribute('disabled');
			qs('[name="endDate"]').removeAttribute('disabled');
		}

		if(field === 'amount') {
			qs('[name="amount"]').removeAttribute('disabled');
			qs('[name="startDate"]').setAttribute('disabled', 'disabled');
			qs('[name="endDate"]').setAttribute('disabled', 'disabled');
		}
	}
}
