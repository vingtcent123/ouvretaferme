document.delegateEventListener('input', '#journal-deferredCharge-set input[name$="Date"]', function() {
	DeferredCharge.updateAmount();
});

document.delegateEventListener('input', '#journal-deferredCharge-set input[name="amount"]', function() {
	DeferredCharge.updateDates();
});




class DeferredCharge {

	static getInitialAmount() {

		return qs('#journal-deferredCharge-set input[name="operationAmount"]').value;

	}

	static updateDates() {

		const amount = qs('#journal-deferredCharge-set input[name="amount"]').value;

		const startDate = new Date(qs('#journal-deferredCharge-set input[name="startDate"]').value);

		if(startDate.toString() === 'Invalid Date') {
			return;
		}

		const initialAmount = this.getInitialAmount();
		const financialYearEndDate = new Date(qs('#journal-deferredCharge-set input[name="financialYearEndDate"]').value);

		const joursN = Math.round((financialYearEndDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif
		const montantConsomme = initialAmount - amount;
		if (montantConsomme <= 0) {
			return;
		}

		const dureeTotale = Math.round(joursN * (initialAmount / montantConsomme));
		const endDate = new Date(startDate.getTime());
		endDate.setDate(endDate.getDate() + dureeTotale - 1);

		qs('#journal-deferredCharge-set input[name="endDate"]').setAttribute('value', endDate.toISOString().substring(0, 10));

	}

	static updateAmount() {

		const startDate = new Date(qs('#journal-deferredCharge-set input[name="startDate"]').value);
		const endDate = new Date(qs('#journal-deferredCharge-set input[name="endDate"]').value);
		const financialYearEndDate = new Date(qs('#journal-deferredCharge-set input[name="financialYearEndDate"]').value);

		const initialAmount = this.getInitialAmount();

		if(endDate < financialYearEndDate || endDate < startDate) {
			return;
		}

		const joursN = Math.round((financialYearEndDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif
		const joursTotal = Math.round((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1; // inclusif

		const newAmount = Math.round((joursTotal - joursN) * initialAmount / joursTotal);
		qs('#journal-deferredCharge-set input[name="amount"]').setAttribute('value', newAmount);

	}

}
