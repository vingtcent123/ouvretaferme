// Calculs automatiques via la base imposable
document.delegateEventListener('input', '#cerfa-12 table[data-chapter="1"] input[name$="base"], #cerfa-12 table[data-chapter="4"] input[name$="base"]', function(e) {

	const name = this.getAttribute('name').slice(0, this.getAttribute('name').indexOf('-'));
	qs('#cerfa-12 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(this.dataset.rate) * this.value / 100);

	VatCa12.recalculateAll();
});

document.delegateEventListener('input', '#cerfa-12 input', function(e) {

	VatCa12.recalculateAll();

});

class VatCa12 {

	static recalculateAll() {

		VatCa12.updateVatBrute();
		VatCa12.updateVatDeductible();
		VatCa12.updateVatNette();
		VatCa12.updateTaxesAssimilees();
		Vat.updateConsommateursEnergie('12');
		VatCa12.updateRecapitulationBase();
		VatCa12.updateReimburse();

	}

	static updateVatBrute() {

		const inputsArray = Array.from(qsa('#cerfa-12 table[data-chapter="1"] input', value => value.getAttribute('name')));

		// Calcul du total
		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);
		const sumTaxes = inputsTaxes.map(input => {
			if(['0983', '0600', '0602'].indexOf(input.getAttribute('name')) !== -1) {
				return 0;
			}
			return Vat.getValue('12', input.getAttribute('name'));
		}).reduce((acc, value) => acc + value, 0);

		qs('#cerfa-12 [name="16-number"]').value = Vat.computeWithPrecision(sumTaxes);
		qs('#cerfa-12 [name="19-number"]').value = Vat.computeWithPrecision(Vat.getValue('12', '16-number') + Vat.getValue('12', '0983') + Vat.getValue('12', '0600') + Vat.getValue('12', '0602'));

	}

	static updateVatDeductible() {

		qs('#cerfa-12 [name="22-number"]').value = Vat.computeWithPrecision(Vat.getValue('12', '0702') + Vat.getValue('12', '0704'));
		qs('#cerfa-12 [name="26-number"]').value = Vat.computeWithPrecision(
			Vat.getValue('12', '22-number') + Vat.getValue('12', '0703') + Vat.getValue('12', '0058') + Vat.getValue('12', '0059') + Vat.getValue('12', '0603')
		);
	}

	static updateVatNette() {

		const ligne_19 = Vat.getValue('12', '19-number');
		const ligne_26 = Vat.getValue('12', '26-number');

		if(ligne_19 > ligne_26) {
			qs('#cerfa-12 [name="8900"]').value = Vat.computeWithPrecision(ligne_19 - ligne_26);
		} else {
			qs('#cerfa-12 [name="0705"]').value = Vat.computeWithPrecision(ligne_26 - ligne_19);
		}


		qs('#cerfa-12 [name="deposit[total][paid]"]').value = Vat.computeWithPrecision(
			Vat.getValue('12', 'deposit[0][paid]') + Vat.getValue('12', 'deposit[1][paid]')
		);
		qs('#cerfa-12 [name="deposit[total][not-paid]"]').value = Vat.computeWithPrecision(
				Vat.getValue('12', 'deposit[0][not-paid]') + Vat.getValue('12', 'deposit[1][not-paid]')
		);

		const deposit_not_paid = Vat.getValue('12', 'deposit[total][not-paid]');
		const deposit_paid = Vat.getValue('12', 'deposit[total][paid]');

		if(deposit_paid > 0) {
			qs('#cerfa-12 [name="0018"]').value = Vat.computeWithPrecision(deposit_paid);
		}

		const ligne_28 = Vat.getValue('12', '8900');
		const ligne_29 = Vat.getValue('12', '0705');
		const ligne_30 = Vat.getValue('12', '0018');

		if(ligne_28 > ligne_29 + ligne_30) {
			qs('#cerfa-12 [name="33-number"]').value = Vat.computeWithPrecision(ligne_28 - (ligne_29 + ligne_30));
		} else if(ligne_30 > ligne_28) {
			qs('#cerfa-12 [name="34-number"]').value = Vat.computeWithPrecision(ligne_30 - ligne_28);
		}

		qs('#cerfa-12 [name="0020"]').value = Vat.computeWithPrecision(Vat.getValue('12', '0705') + Vat.getValue('12', '34-number'));

	}

	static updateTaxesAssimilees() {

		const inputsArray = Array.from(qsa('#cerfa-12 table[data-chapter="4"] input', value => value.getAttribute('name')));

		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);

		// Calcul actes 4206-base
		const fixedPrice = qs('#cerfa-12 [name="4206-base"]').dataset.fixedPrice;
		const numberActes = Vat.getValue('12', '4206-base');
		qs('#cerfa-12 [name="4206"]').value = Vat.computeWithPrecision(fixedPrice * numberActes);

		const fixed4250Price = qs('#cerfa-12 [name="4250-base"]').dataset.fixedPrice;
		const number4250 = Vat.getValue('12', '4250-base');
		qs('#cerfa-12 [name="4250"]').value = Vat.computeWithPrecision(fixed4250Price * number4250);

		// Production d'électricité (P4)
		const p4_a = Vat.getValue('12', 'p4-a');
		const p4_b = Vat.getValue('12', 'p4-b');
		const p4_c = Vat.getValue('12', 'p4-c');
		if(p4_a > p4_b + p4_c) {
			qs('#cerfa-12 [name="p4-total"]').value = Vat.computeWithPrecision(p4_a - p4_b - p4_c);
		} else {
			qs('#cerfa-12 [name="p4-total"]').value = 0;
		}
		const p4_regularisation = Vat.getValue('12', 'p4-regularisation');
		const p4_total = Vat.getValue('12', 'p4-total');
		qs('#cerfa-12 [name="p4-A"]').value = Vat.computeWithPrecision(Math.max(0, p4_total + p4_regularisation));
		qs('#cerfa-12 [name="4315"]').value = qs('#cerfa-12 [name="p4-A"]').value;

		// produits phytopharmaceutiques
		const base_taux_0_9 = Vat.getValue('12', '4321-a');
		const taux_taux_0_9 = qs('#cerfa-12 [name="4321-a"]').dataset.rate || 0;
		const base_taux_0_1 = Vat.getValue('12', '4321-b');
		const taux_taux_0_1 = qs('#cerfa-12 [name="4321-b"]').dataset.rate || 0
		qs('#cerfa-12 [name="4321"]').value = Vat.computeWithPrecision(base_taux_0_9 * taux_taux_0_9 / 100 + base_taux_0_1 * taux_taux_0_1 / 100);

		// 70A - véhicules lourds
		const inputsVehicules = inputsArray.filter(input => input.getAttribute('name').indexOf('4303') === 0 && input.getAttribute('name').indexOf('-tax') > 0);
		qs('#cerfa-12 [name="4303"]').value = Vat.computeWithPrecision(inputsVehicules.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 84 - exploration d’hydrocarbures
		const inputs84 = inputsArray.filter(input => input.getAttribute('name').indexOf('4291-amount') === 0);
		qs('#cerfa-12 [name="4291"]').value = Vat.computeWithPrecision(inputs84.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 89 - boissons non alcooliques
		const rateByHL = qs('#cerfa-12 [name="4296-number"]').dataset.rateByHl || 0;
		const quantity = qs('#cerfa-12 [name="4296-number"]').value || 0;
		qs('#cerfa-12 [name="4296"]').value = Vat.computeWithPrecision(rateByHL * quantity);

		// 92 - eaux minérales
		const inputs92 = inputsArray.filter(input => input.getAttribute('name').indexOf('4293-amount') === 0);
		qs('#cerfa-12 [name="4293"]').value = Vat.computeWithPrecision(inputs92.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0));

		// 93 - services numériques
		qs('#cerfa-12 [name="4301"]').value = Vat.computeWithPrecision(Vat.getValue('12', '4301-a') + Vat.getValue('12', '4301-b'));

		// Total
		const taxesSum = inputsTaxes.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0, 0);

		qs('#cerfa-12 [name="total-taxes-assimilees"]').value = Vat.computeWithPrecision(taxesSum);
	}

	static updateRecapitulationBase() {

		qs('#cerfa-12 [name="49-number"]').value = Vat.getValue('12', '0020');
		qs('#cerfa-12 [name="8003"]').value = Vat.computeWithPrecision(Vat.getValue('12', '49-number') - Vat.getValue('12', '8002'));
		qs('#cerfa-12 [name="8113"]').value = Vat.getValue('12', 'Y4');
		qs('#cerfa-12 [name="8103"]').value = Vat.getValue('12', 'X4');

		qs('#cerfa-12 [name="8901"]').value = Vat.computeWithPrecision(Vat.getValue('12', '33-number') - Vat.getValue('12', '8103'));

		qs('#cerfa-12 [name="55-number"]').value = Vat.getValue('12', 'total-taxes-assimilees');

		qs('#cerfa-12 [name="8123"]').value = Vat.getValue('12', 'Z4');

		// Ligne 56
		const val_8901 = Vat.getValue('12', '8901');
		const number_55 = Vat.getValue('12', '55-number');
		const val_8123 = Vat.getValue('12', '8123');
		qs('#cerfa-12 [name="9992"]').value = Vat.computeWithPrecision(val_8901 + number_55 + val_8123);

		// Base de calcul des acomptes dus au titre de l'exercice suivant
		const number_16 = Vat.getValue('12', '16-number');
		const number_22 = Vat.getValue('12', '22-number');
		const value_0970 = Vat.getValue('12', '0970');
		const value_0980 = Vat.getValue('12', '0980');

		qs('#cerfa-12 [name="57-number"]').value = Math.max(0, Vat.computeWithPrecision(number_16 - (number_22 + value_0970 + value_0980)));

	}

	static updateReimburse() {

		const value_0705 = Vat.getValue('12', '0705');

		if(value_0705 > 0) {
			qs('#cerfa-12 [name="reimburse-a"]').value = Vat.computeWithPrecision(value_0705);
		}
		const a = Vat.getValue('12', 'reimburse-a');
		const b = Vat.getValue('12', 'reimburse-b');
		const d = Vat.getValue('12', 'reimburse-d');

		const c = Vat.computeWithPrecision(a + b);
		const e = Vat.computeWithPrecision(c - d);

		qs('#cerfa-12 [name="reimburse-c"]').value = c;
		qs('#cerfa-12 [name="reimburse-e"]').value = e;
	}

}
