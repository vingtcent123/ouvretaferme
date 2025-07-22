document.delegateEventListener('input', '#journal-deferral-set input[name$="Date"]', function() {
	Deferral.updateAmount(this.firstParent('form').getAttribute('id'));

});

document.delegateEventListener('input', '#journal-deferral-set input[name="amount"]', function() {
	Deferral.updateDates(this.firstParent('form').getAttribute('id'));
});




class Deferral {

	static getInitialAmount(formId) {

		return qs('#' + formId + ' input[name="operationAmount"]').value;

	}

	static updateDates(formId) {

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

		}

	}

	static updateAmount(formId) {

		const startDate = new Date(qs('#' + formId + ' input[name="startDate"]').value);
		const endDate = new Date(qs('#' + formId + ' input[name="endDate"]').value);
		const financialYearEndDate = new Date(qs('#' + formId + ' input[name="financialYearEndDate"]').value);

		const initialAmount = this.getInitialAmount(formId);

		if(endDate < financialYearEndDate || endDate < startDate) {
			return;
		}

		const joursN = Math.round((financialYearEndDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif
		const joursTotal = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif

		const newAmount = Math.round((joursTotal - joursN) * initialAmount / joursTotal);
		qs('#' + formId + ' input[name="amount"]').setAttribute('value', newAmount);

	}

}
