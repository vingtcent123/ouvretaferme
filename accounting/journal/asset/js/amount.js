class OperationAmount {

    static sumType(type) {

        const allValues = Array.from(qsa('[type="hidden"][name^="' + type + '["]', element => element.value));

        return round(allValues.reduce(function (acc, valueTarget) {
            const index = valueTarget.firstParent('.input-group').qs('input[data-index]').dataset.index;

            const creditType = qs('[name="type[' + index + ']"]:checked')?.getAttribute('value') || null;

            if(creditType === 'credit') {
							return acc - parseFloat(valueTarget.value || 0);
						} else if(creditType === 'debit') {
                return acc + parseFloat(valueTarget.value || 0);
            } else {
							return acc;
						}
        }, 0));

    }

	static setValidationValues(multiplier) {

			const amount = OperationAmount.sumType('amount') * (multiplier ?? 1);

			if(Operation.hasVat()) {

				const amountIncludingVAT = OperationAmount.sumType('amountIncludingVAT') * (multiplier ?? 1);
				const vatValue = OperationAmount.sumType('vatValue') * (multiplier ?? 1);

				qs('.operation-create-validation [data-field="vatValue"] [data-type="value"]').innerHTML = money(vatValue);
				qs('.operation-create-validation [data-field="amountIncludingVAT"] [data-type="value"]').innerHTML = money(amountIncludingVAT);

				if(typeof Cashflow === 'undefined') {

					qsa('#balance-information-warning', node => node.hide());

					if(parseFloat(amountIncludingVAT) !== 0.0) {

            qs('.balance-information').classList.add('danger');
            qs('#balance-information-warning').removeHide();

					}
				}
			}

			qs('.operation-create-validation [data-field="amount"] [data-type="value"]').innerHTML = money(amount);

	}

	static calculateVatValueFromAmountIncludingVAT(amountIncludingVAT, vatRate) {

		return Math.round((amountIncludingVAT / (1 + vatRate / 100)) * (vatRate / 100) * 100) / 100;

	}

	static calculateAmountFromAmountIncludingVAT(amountIncludingVAT, vatRate) {

		return Math.round(amountIncludingVAT / (1 + vatRate/100) * 100) / 100;

	}

	static updateAmount(index, field, amount) {

		if(isNaN(amount)) {
			return;
		}

		const target = qs('[name="' + field + '[' + index + ']"');
		CalculationField.setValue(target, amount);

	}

	static areAmountsCorrected(index) {

		if(Operation.hasVat() === false) {
			return true;
		}

		return qs('[data-check-amount="1"][data-index="' + index + '"]').isVisible()

	}

	/**
	 * Vérification générale des montants
	 */
	static checkAmounts(index) {

		const type = qs('[name="type[' + index + ']"]:checked').value;
		const multiplier = type === 'credit' ? -1 : 1;

		OperationAmount.setValidationValues(multiplier);

		if(Operation.hasVat() === false) {
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
		if(Math.abs(round(amountIncludingVAT) - round(amount + vatValue)) > 0.01) {

			qs('[data-amount-including-vat-warning][data-index="' + index + '"]').removeHide();
			qs('[data-amount-including-vat-warning-value][data-index="' + index + '"]').innerHTML = money(amountIncludingVAT);
			qs('[data-amount-including-vat-warning-calculated-value][data-index="' + index + '"]').innerHTML = money(round(amount + vatValue, 2));
			qs('[data-amount-including-vat-warning-calculated-value][data-index="' + index + '"]').dataset.value =round(amount + vatValue, 2);

			OperationAmount.switchDataAmountCheck(index, false);

		} else {

			qs('[data-amount-including-vat-warning][data-index="' + index + '"]').hide();

		}

		// Check TVA = HT * VatRate OU formule depuis TTC avec taux de TVA
		if(
			round(Math.abs(vatValue - amount * vatRate / 100, 2)) > 0.01 &&
			round(Math.abs(vatValue - OperationAmount.calculateVatValueFromAmountIncludingVAT(amountIncludingVAT, vatRate)), 2) > 0.01
		) {

			qs('[data-vat-value-warning][data-index="' + index + '"]').removeHide();
			qs('[data-vat-value-vat-warning-value][data-index="' + index + '"]').innerHTML = money(round(vatValue, 2));
			qs('[data-vat-value-vat-warning-value][data-index="' + index + '"]').dataset.value = round(vatValue, 2);
			qs('[data-vat-value-vat-warning-calculated-value][data-index="' + index + '"]').innerHTML = money(round(amount * vatRate / 100, 2));
			qs('[data-vat-value-vat-warning-calculated-value][data-index="' + index + '"]').dataset.value = round(amount * vatRate / 100, 2);

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
			qs('.operation-amount-check-legend[data-index="' + index + '"]').innerHTML = '&nbsp;' + qs('.operation-amount-check-legend[data-index="' + index + '"]').dataset.checkAmountLegendOk;

		} else {

			qs('[data-index="' + index + '"][data-check-amount="1"]').classList.add('btn-warning');
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ko"]').removeHide();
			qs('[data-index="' + index + '"][data-check-amount="1"] [data-check-amount-icon="ok"]').hide();
			qs('.operation-amount-check-legend[data-index="' + index + '"]').innerHTML = '&nbsp;' + qs('.operation-amount-check-legend[data-index="' + index + '"]').dataset.checkAmountLegendKo;

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

		if(Operation.hasVat() === false || OperationAmount.areAmountsCorrected(index) === false) {
			return;
		}

		const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

		const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
		const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT);

		const amount = OperationAmount.calculateAmountFromAmountIncludingVAT(amountIncludingVAT, vatRate);
		const vatValue = OperationAmount.calculateVatValueFromAmountIncludingVAT(amountIncludingVAT, vatRate);

		OperationAmount.updateAmount(index, 'amount', amount);
		OperationAmount.updateAmount(index, 'vatValue', vatValue);

	}

	/**
	 * Saisie du HT
	 * => Si vatRate saisi, recalcul de [TTC] + TVA
	 */
	static calculateFromAmount(index) {

		if(Operation.hasVat() === false || OperationAmount.areAmountsCorrected(index) === false) {
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

		const targetAmount = qs('[name="amount[' + index + ']"');
		const amount = CalculationField.getValue(targetAmount);

		if(!amount) {
			return;
		}

		const vatValue = Math.round(amount * vatRate) / 100;
		OperationAmount.updateAmount(index, 'vatValue', vatValue);

		if(typeof Cashflow === 'undefined' || parseInt(qs('[data-columns]').dataset.columns) > 1) {

			const amountIncludingVAT = amount + vatValue;
			const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
			CalculationField.setValue(targetAmountIncludingVAT, amountIncludingVAT);

		}

	}

}
