document.delegateEventListener('input', '#cerfa-12 input', function(e) {

	Vat.recalculateAll();

});

class Vat {

	static recalculateAll() {

		Vat.updateVatBrute();
		Vat.updateVatDeductible();
		Vat.updateVatNette();
		Vat.updateTaxesAssimilees();
		Vat.updateConsommateursEnergie();
		Vat.updateRecapitulationBase();
		Vat.updateReimburse();

	}

	static getValue(name) {
		return parseFloat(qs('[name="'+ name +'"]').value || 0);
	}

	static updateVatBrute() {

		const inputsArray = Array.from(qsa('table[data-chapter="1"] input', value => value.getAttribute('name')));

		// Calculs automatiques via la base imposable
		const inputsBases = inputsArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);
		inputsBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('[name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})

		// Calcul du total
		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);
		const sumTaxes = inputsTaxes.map(input => {
			if(['0983', '0600', '0602'].indexOf(input.getAttribute('name')) !== -1) {
				return 0;
			}
			return Vat.getValue(input.getAttribute('name'));
		}).reduce((acc, value) => acc + value, 0);

		qs('[name="16-number"]').value = Vat.computeWithPrecision(sumTaxes);
		qs('[name="19-number"]').value = Vat.computeWithPrecision(Vat.getValue('16-number') + Vat.getValue('0983') + Vat.getValue('0600') + Vat.getValue('0602'));

	}

	static updateVatDeductible() {

		qs('[name="22-number"]').value = Vat.computeWithPrecision(Vat.getValue('0702') + Vat.getValue('0704'));
		qs('[name="26-number"]').value = Vat.computeWithPrecision(
			Vat.getValue('22-number') + Vat.getValue('0703') + Vat.getValue('0058') + Vat.getValue('0059') + Vat.getValue('0603')
		);
	}

	static updateVatNette() {

		const ligne_19 = Vat.getValue('19-number');
		const ligne_26 = Vat.getValue('26-number');

		if(ligne_19 > ligne_26) {
			qs('[name="8900"]').value = Vat.computeWithPrecision(ligne_19 - ligne_26);
		} else {
			qs('[name="0705"]').value = Vat.computeWithPrecision(ligne_26 - ligne_19);
		}


		qs('[name="deposit[total][paid]"]').value = Vat.computeWithPrecision(
			Vat.getValue('deposit[0][paid]') + Vat.getValue('deposit[1][paid]')
		);
		qs('[name="deposit[total][not-paid]"]').value = Vat.computeWithPrecision(
				Vat.getValue('deposit[0][not-paid]') + Vat.getValue('deposit[1][not-paid]')
		);

		const deposit_not_paid = Vat.getValue('deposit[total][not-paid]');
		const deposit_paid = Vat.getValue('deposit[total][paid]');

		if(deposit_paid - deposit_not_paid > 0) {
			qs('[name="0018"]').value = Vat.computeWithPrecision(-1 * deposit_paid);
		}

		const ligne_28 = Vat.getValue('8900');
		const ligne_29 = Vat.getValue('0705');
		const ligne_30 = Vat.getValue('0018');

		if(ligne_28 > ligne_29 + ligne_30) {
			qs('[name="33-number"]').value = Vat.computeWithPrecision(ligne_28 - (ligne_29 + ligne_30));
		} else if(ligne_30 > ligne_28) {
			qs('[name="34-number"]').value = Vat.computeWithPrecision(ligne_30 - ligne_28);
		}

		qs('[name="0020"]').value = Vat.computeWithPrecision(Vat.getValue('0705') + Vat.getValue('34-number'));

	}

	static updateConsommateursEnergie() {

		qs('[name="Y1"]').value = Vat.computeWithPrecision(
			Vat.getValue('electricite') - Vat.getValue('X1')
		);
		qs('[name="M4"]').value = Vat.computeWithPrecision(
			Vat.getValue('electricite-majoration') - Vat.getValue('M1')
		);
		qs('[name="Y2"]').value = Vat.computeWithPrecision(
			Vat.getValue('gaz') - Vat.getValue('X2')
		);
		qs('[name="M5"]').value = Vat.computeWithPrecision(
			Vat.getValue('gaz-majoration') - Vat.getValue('M2')
		);
		qs('[name="Y2"]').value = Vat.computeWithPrecision(
			Vat.getValue('charbon') - Vat.getValue('X3')
		);
		qs('[name="M6"]').value = Vat.computeWithPrecision(
			Vat.getValue('charbon-majoration') - Vat.getValue('M3')
		);
		qs('[name="YA"]').value = Vat.computeWithPrecision(
			Vat.getValue('others') - Vat.getValue('XA')
		);

		qs('[name="X4"]').value = Vat.computeWithPrecision(
				Vat.getValue('X1') +
				Vat.getValue('M1') +
				Vat.getValue('X2') +
				Vat.getValue('M2') +
				Vat.getValue('X3') +
			Vat.getValue('M3') +
			Vat.getValue('XA')
		);

		qs('[name="Y4"]').value = Vat.computeWithPrecision(
			Vat.getValue('Y1') +
			Vat.getValue('M4') +
			Vat.getValue('Y2') +
			Vat.getValue('M5') +
			Vat.getValue('Y3') +
			Vat.getValue('M6') +
			Vat.getValue('YA')
		);

		qs('[name="Z4"]').value = Vat.computeWithPrecision(
			Vat.getValue('Z1') +
			Vat.getValue('M7') +
			Vat.getValue('Z2') +
			Vat.getValue('M8') +
			Vat.getValue('Z3') +
			Vat.getValue('M9') +
			Vat.getValue('ZB')
		);

		const val_X4 = Vat.getValue('X4');
		qs('[name="8103"]').value = val_X4;
	}

	static updateTaxesAssimilees() {

		const inputsArray = Array.from(qsa('table[data-chapter="4"] input', value => value.getAttribute('name')));

		// Calculs automatiques via la base imposable
		const inputsBases = inputsArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);
		inputsBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('[name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})
		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);

		// Calcul actes 4206-base
		const fixedPrice = qs('[name="4206-base"]').dataset.fixedPrice;
		const numberActes = Vat.getValue('4206-base');
		qs('[name="4206"]').value = Vat.computeWithPrecision(fixedPrice * numberActes);

		// Production d'électricité (P4)
		const p4_a = Vat.getValue('p4-a');
		const p4_b = Vat.getValue('p4-b');
		const p4_c = Vat.getValue('p4-c');
		if(p4_a > p4_b + p4_c) {
			qs('[name="p4-total"]').value = Vat.computeWithPrecision(p4_a - p4_b - p4_c);
		} else {
			qs('[name="p4-total"]').value = 0;
		}
		const p4_regularisation = Vat.getValue('p4-regularisation');
		const p4_total = Vat.getValue('p4-total');
		qs('[name="p4-A"]').value = Vat.computeWithPrecision(Math.max(0, p4_total + p4_regularisation));
		qs('[name="4315"]').value = qs('[name="p4-A"]').value;

		// produits phytopharmaceutiques
		const base_taux_0_9 = Vat.getValue('4321-a');
		const taux_taux_0_9 = qs('[name="4321-a"]').dataset.rate || 0;
		const base_taux_0_1 = Vat.getValue('4321-b');
		const taux_taux_0_1 = qs('[name="4321-b"]').dataset.rate || 0
		qs('[name="4321"]').value = Vat.computeWithPrecision(base_taux_0_9 * taux_taux_0_9 / 100 + base_taux_0_1 * taux_taux_0_1 / 100);

		// 70A - véhicules lourds
		const inputsVehicules = inputsArray.filter(input => input.getAttribute('name').indexOf('4303') === 0 && input.getAttribute('name').indexOf('-tax') > 0);
		qs('[name="4303"]').value = Vat.computeWithPrecision(inputsVehicules.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 84 - exploration d’hydrocarbures
		const inputs84 = inputsArray.filter(input => input.getAttribute('name').indexOf('4291-amount') === 0);
		qs('[name="4291"]').value = Vat.computeWithPrecision(inputs84.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 89 - boissons non alcooliques
		const rateByHL = qs('[name="4296-number"]').dataset.rateByHl || 0;
		const quantity = qs('[name="4296-number"]').value || 0;
		qs('[name="4296"]').value = Vat.computeWithPrecision(rateByHL * quantity);

		// 92 - eaux minérales
		const inputs92 = inputsArray.filter(input => input.getAttribute('name').indexOf('4293-amount') === 0);
		qs('[name="4293"]').value = Vat.computeWithPrecision(inputs92.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 93 - services numériques
		qs('[name="4301"]').value = Vat.computeWithPrecision(Vat.getValue('4301-a') + Vat.getValue('4301-b'));

		// Total
		const taxesSum = inputsTaxes.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0, 0);

		qs('[name="total-taxes-assimilees"]').value = Vat.computeWithPrecision(taxesSum);
	}

	static updateRecapitulationBase() {

		qs('[name="49-number"]').value = Vat.getValue('0020');
		qs('[name="8003"]').value = Vat.computeWithPrecision(Vat.getValue('49-number') - Vat.getValue('8002'));
		qs('[name="8113"]').value = Vat.getValue('Y4');
		qs('[name="8103"]').value = Vat.getValue('X4');

		qs('[name="8901"]').value = Vat.computeWithPrecision(Vat.getValue('33-number') - Vat.getValue('8103'));

		qs('[name="55-number"]').value = Vat.getValue(
			'total-taxes-assimilees');

		qs('[name="8123"]').value = Vat.getValue('Z4');

		// Ligne 56
		const val_8901 = Vat.getValue('8901');
		const number_55 = Vat.getValue('55-number');
		const val_8123 = Vat.getValue('8123');
		qs('[name="9992"]').value = Vat.computeWithPrecision(val_8901 + number_55 + val_8123);

		// Base de calcul des acomptes dus au titre de l'exercice suivant
		const number_16 = Vat.getValue('16-number');
		const number_22 = Vat.getValue('22-number');
		const value_0970 = Vat.getValue('0970');
		const value_0980 = Vat.getValue('0980');

		qs('[name="57-number"]').value = Math.max(0, Vat.computeWithPrecision(number_16 - (number_22 + value_0970 + value_0980)));

	}

	static updateReimburse() {

		const a = Vat.getValue('reimburse-a');
		const b = Vat.getValue('reimburse-b');
		const d = Vat.getValue('reimburse-d');

		const c = Vat.computeWithPrecision(a + b);
		const e = Vat.computeWithPrecision(c + d);

		qs('[name="reimburse-c"]').value = c;
		qs('[name="reimburse-e"]').value = e;
	}

	static computeWithPrecision(result) {

		const precision = Vat.getPrecision();

		if(precision === 0) {
			return Math.round(result);
		}

		return Math.round(result * (precision * 10) / (precision * 10));

	}

	static getPrecision() {
		return parseInt(qs('[data-precision]').dataset.precision || 0);
	}

}
