class OperationAmount {

	static updateAmount(index, field, amount) {

		if(isNaN(amount)) {
			return;
		}

		const target = qs('[name="' + field + '[' + index + ']"');
		CalculationField.setValue(target, amount);

	}

	static areAmountsChecked(index) {

		return qs('[data-check-amount="1"][data-index="' + index + '"]').isVisible()

	}

	/**
	 * Vérification générale des montants
	 */
	static checkAmounts(index) {

		if(Operation.hasVat() === false) {
			if(typeof Cashflow !== 'undefined') {
				Cashflow.checkValidationValues();
			}
			return;
		}

		if(OperationAmount.areAmountsChecked(index) === false) {
			if(typeof Cashflow !== 'undefined') {
				Cashflow.checkValidationValues();
			}
			return;
		}

		const targetAmount = qs('[name="amount[' + index + ']"');
		const amount = CalculationField.getValue(targetAmount) || 0;

		const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
		const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT) || 0;

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		const targetVatValue = qs('[name="vatValue[' + index + ']"');
		const vatValue = CalculationField.getValue(targetVatValue) || 0;

		OperationAmount.switchDataAmountCheck(index, true);

		// Check TTC = HT + TVA
		if(Math.round(amountIncludingVAT * 100) !== Math.round((amount + vatValue) * 100)) {

			qs('[data-amount-including-vat-warning][data-index="' + index + '"]').removeHide();
			qs('[data-amount-including-vat-warning-value][data-index="' + index + '"]').innerHTML = money(amountIncludingVAT);
			qs('[data-amount-including-vat-warning-calculated-value][data-index="' + index + '"]').innerHTML = money(Math.round((amount + vatValue) * 100) / 100);

			OperationAmount.switchDataAmountCheck(index, false);

		} else {

			qs('[data-amount-including-vat-warning][data-index="' + index + '"]').hide();

		}

		// Check TVA = HT * VatRate
		if(Math.round(vatValue * 100) !== Math.round(amount * vatRate)) {

			qs('[data-vat-value-warning][data-index="' + index + '"]').removeHide();
			qs('[data-vat-value-vat-warning-value][data-index="' + index + '"]').innerHTML = money(vatValue);
			qs('[data-vat-value-vat-warning-calculated-value][data-index="' + index + '"]').innerHTML = money(Math.round(amount * vatRate) / 100);


			OperationAmount.switchDataAmountCheck(index, false);

		} else {

			qs('[data-vat-value-warning][data-index="' + index + '"]').hide();

		}

		// If cashflow = Check the integrity
		if(typeof Cashflow !== 'undefined') {
				Cashflow.checkValidationValues();
		}

	}

	/**
	 * Activation/Désactivation des warnings
	 */
	static switchDataAmountCheck(index, isOK) {

		if(isOK) {

			qs('[data-index="' + index + '"][data-check-amount="1"]').classList.remove('btn-warning');
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ok"]').removeHide();
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ko"]').hide();
			qs('.operation-amount-check-legend[data-index="' + index + '"]').innerHTML = qs('.operation-amount-check-legend[data-index="' + index + '"]').dataset.checkAmountLegendOk;

		} else {

			qs('[data-index="' + index + '"][data-check-amount="1"]').classList.add('btn-warning');
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ko"]').removeHide();
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ok"]').hide();
			qs('.operation-amount-check-legend[data-index="' + index + '"]').innerHTML = qs('.operation-amount-check-legend[data-index="' + index + '"]').dataset.checkAmountLegendKo;

		}
	}

	/*
	Taux de TVA rempli automatiquement (via le choix de la classe)
	=> Si TTC présent, recalcule HT + TVA à partir de TTC
	=> Sinon, si HT présent, recalcule TTC + TVA à partir de HT
	 */
	static calculateFromVatRateAuto(index) {

		const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
		const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT);

		const targetAmount = qs('[name="amount[' + index + ']"');
		const amount = CalculationField.getValue(targetAmount);

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		if(amountIncludingVAT) {

			const amountNew = Math.round(amountIncludingVAT / (1 + vatRate / 100) * 100) / 100;
			const vatValue = Math.round((amountIncludingVAT - amountNew) * 100) / 100;

			OperationAmount.updateAmount(index, 'amount', amountNew);
			OperationAmount.updateAmount(index, 'vatValue', vatValue);

		} else if(amount) {


			const vatValue = Math.round(amount * vatRate ) / 100;
			const amountIncludingVATNew = Math.round((vatValue + amount) * 100) / 100;

			OperationAmount.updateAmount(index, 'amountIncludingVAT', amountIncludingVATNew);
			OperationAmount.updateAmount(index, 'vatValue', vatValue);

		}

	}

	/**
	 * Saisie du TTC
	 * => Si vatRate saisi, recalcul de [HT] + TVA
	 */
	static calculateFromAmountIncludingVAT(index) {

		if(OperationAmount.areAmountsChecked(index) === false) {
			return;
		}

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
		const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT);

		const amount = Math.round(amountIncludingVAT / (1 + vatRate/100) * 100) / 100;
		const vatValue = Math.round((amountIncludingVAT - amount) * 100) / 100;

		OperationAmount.updateAmount(index, 'amount', amount);
		OperationAmount.updateAmount(index, 'vatValue', vatValue);

	}

	/**
	 * Saisie du HT
	 * => Si vatRate saisi, recalcul de [TTC] + TVA
	 */
	static calculateFromAmount(index) {

		if(OperationAmount.areAmountsChecked(index) === false) {
			return;
		}

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		const targetAmount = qs('[name="amount[' + index + ']"');
		const amount = CalculationField.getValue(targetAmount);

		const vatValue = Math.round((amount * vatRate)) / 100;
		const amountIncludingVAT = Math.round((amount + vatValue) * 100) / 100;

		OperationAmount.updateAmount(index, 'amountIncludingVAT', amountIncludingVAT);
		OperationAmount.updateAmount(index, 'vatValue', vatValue);

	}

	/**
	 * Saisie du taux de TVA
	 * => Si HT saisi, recalcul de TVA
	 * => Si TTC saisi, recalcul de TVA
	 */
	static calculateFromVatRate(index) {

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		const recommendedVatRate = qs('[data-field="vatRate"][data-index="' + index + '"]').dataset.vatRateRecommended;

		qs('[data-vat-rate-warning][data-index="' + index + '"]').hide();

		if(Math.round(vatRate * 100) / 100 !== Math.round(recommendedVatRate * 100) / 100) {

			const vatClassChosen = qs('[data-field="vatRate"][data-index="' + index + '"]').dataset.vatClassChosen;
			qs('[data-vat-rate-default][data-index="' + index + '"]').innerHTML = recommendedVatRate;
			qs('[data-vat-rate-class][data-index="' + index + '"]').innerHTML = vatClassChosen;
			qs('[data-vat-rate-warning][data-index="' + index + '"]').removeHide();
		}

		if(OperationAmount.areAmountsChecked(index) === false) {
			return;
		}


		const targetAmount = qs('[name="amount[' + index + ']"');
		const amount = CalculationField.getValue(targetAmount);

		if(amount) {
			const vatValue = Math.round(amount * vatRate) / 100;
			OperationAmount.updateAmount(index, 'vatValue', vatValue);
			return;
		}

		const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
		const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT);

		if(amountIncludingVAT) {
			const vatValue = Math.round((amountIncludingVAT - amountIncludingVAT / (1 + vatRate / 100)) * 100) / 100;
			OperationAmount.updateAmount(index, 'vatValue', vatValue);

		}

	}

}
