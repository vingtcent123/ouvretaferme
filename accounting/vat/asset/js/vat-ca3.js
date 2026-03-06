// Calculs automatiques via la base imposable
document.delegateEventListener('input', '#cerfa-3 table[data-chapter="B"] input[name$="base"], #cerfa-3 table[data-chapter="annexe"] input[name$="base"]', function() {

	const name = this.getAttribute('name').slice(0, this.getAttribute('name').indexOf('-'));
	qs('#cerfa-3 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(this.dataset.rate) * this.value / 100);

	VatCa3.recalculateAll();
});

document.delegateEventListener('input', '#cerfa-3 input', function() {

	VatCa3.recalculateAll();

});


class VatCa3 {

	static recalculateAll() {

		VatCa3.updateAnnexe();
		VatCa3.updateVatBrute();
		VatCa3.updateVatNette();
		Vat.updateConsommateursEnergie('3');
		VatCa3.updateDetermination();

	}
	
	static updateVatBrute() {

		const inputsArray = Array.from(qsa('#cerfa-3 table[data-chapter="B"] input', value => value.getAttribute('name')));

		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);
		const sumTaxes = inputsTaxes.map(input => Vat.getValue('3', input.getAttribute('name'))).reduce((acc, value) => acc + value, 0);

		qs('#cerfa-3 [name="16-number"]').value = Vat.computeWithPrecision(sumTaxes);

		// Total déductible

		qs('#cerfa-3 [name="23-number"]').value = Vat.computeWithPrecision(Vat.getValue('3', '0703') + Vat.getValue('3', '0702') + Vat.getValue('3', '0059') + Vat.getValue('3', '8001') + Vat.getValue('3', '0603'));

	}

	static updateVatNette() {

		const ligne_23 = Vat.getValue('3', '0703');
		const ligne_16 = Vat.getValue('3', '16-number');

		if(ligne_23 > ligne_16) {
			qs('#cerfa-3 [name="0705"]').value = Vat.computeWithPrecision(ligne_23 - ligne_16);
		} else {
			qs('#cerfa-3 [name="0705"]').value = Vat.computeWithPrecision(ligne_16 - ligne_23);
		}

	}

	static updateAnnexe() {

		const ligne_56_b = Vat.getValue('3', '56-b');
		const ligne_56_c = Vat.getValue('3', '56-c');

		if(ligne_56_b < ligne_56_c) {

			qs('#cerfa-3 [name="56-d"]').value = Vat.computeWithPrecision(ligne_56_b - ligne_56_c);
			qs('#cerfa-3 [name="56a-b"]').value = Vat.computeWithPrecision(ligne_56_b - ligne_56_c);

		} else {

			qs('#cerfa-3 [name="56-e"]').value = Vat.computeWithPrecision(ligne_56_c - ligne_56_b);
			qs('#cerfa-3 [name="56a-c"]').value = Vat.computeWithPrecision(ligne_56_b - ligne_56_c);
			qs('#cerfa-3 [name="56a-d"]').value = Vat.computeWithPrecision(ligne_56_b - ligne_56_c);

		}

		const ligne_56a_a = Vat.getValue('3', '56a-a');
		const ligne_56a_b = Vat.getValue('3', '56a-b');
		const ligne_56a_c = Vat.getValue('3', '56a-c');

		if((ligne_56a_a - ligne_56a_b + ligne_56a_c) > 0) {
			qs('#cerfa-3 [name="4328"]').value = Vat.computeWithPrecision(ligne_56a_a - ligne_56a_b + ligne_56a_c);
		}

		const ligne_56b_a = Vat.getValue('3', '56b-a');
		qs('#cerfa-3 [name="56b-b"]').value = Vat.computeWithPrecision(ligne_56b_a * 4.6 / 100);

		const ligne_56b_b = Vat.getValue('3', '56b-b');
		const ligne_56b_c = Vat.getValue('3', '56b-c');

		if(ligne_56b_b < ligne_56b_c) {
			qs('#cerfa-3 [name="56b-d"]').value = Vat.computeWithPrecision(ligne_56b_c - ligne_56b_b);
		} else {
			qs('#cerfa-3 [name="4329"]').value = Vat.computeWithPrecision(ligne_56b_b - ligne_56b_c);
		}

		let totalp4 = 0;
		for(let i = 0; i < 19; i++) {
			const letter = String.fromCharCode(97 + i);
			const ligne_62_letter_margin = Vat.getValue('3', 'p4' + letter + '-margin');
			if(ligne_62_letter_margin > 0) {
				totalp4 += ligne_62_letter_margin;
			}
		}
		qs('#cerfa-3 [name="p4-total"]').value = Vat.computeWithPrecision(totalp4);
		const ligne_62_p4_deposit = Vat.getValue('3', 'p4-deposit');
		const ligne_62_p4_regul = Vat.getValue('3', 'p4-regul');
		qs('#cerfa-3 [name="p4-contribution"]').value = Vat.computeWithPrecision(totalp4 - ligne_62_p4_deposit + ligne_62_p4_regul);

		if((totalp4 - ligne_62_p4_deposit + ligne_62_p4_regul) > 0) {
			qs('#cerfa-3 [name="4315"]').value = Vat.computeWithPrecision(totalp4 - ligne_62_p4_deposit + ligne_62_p4_regul);
		}

		const fixedPrice = qs('#cerfa-3 [name="4206-base"]').dataset.fixedPrice;
		const numberActes = Vat.getValue('3', '4206-base');
		qs('#cerfa-3 [name="4206"]').value = Vat.computeWithPrecision(fixedPrice * numberActes);

		const ligne_65_a = Vat.getValue('3', '65-a');
		const ligne_65_b = Vat.getValue('3', '65-b');
		if((ligne_65_a - ligne_65_b) > 5000000) {
			qs('#cerfa-3 [name="65-c"]').value = Vat.computeWithPrecision(5000000 - (ligne_65_a - ligne_65_b));
			qs('#cerfa-3 [name="4226"]').value = Vat.computeWithPrecision((5000000 - (ligne_65_a - ligne_65_b)) * 1.3/100);
		}

		const fixed4250Price = qs('#cerfa-3 [name="4250-base"]').dataset.fixedPrice;
		const number4250 = Vat.getValue('3', '4250-base');
		qs('#cerfa-3 [name="4250"]').value = Vat.computeWithPrecision(fixed4250Price * number4250);

		const ligne_116_1_a = Vat.getValue('3', '4303-1a-tax');
		const ligne_116_1_b = Vat.getValue('3', '4303-1b-tax');
		const ligne_116_2_a = Vat.getValue('3', '4303-2a-tax');
		const ligne_116_2_b = Vat.getValue('3', '4303-2b-tax');
		const ligne_116_3 = Vat.getValue('3', '4303-3-tax');
		qs('#cerfa-3 [name="4303"]').value = Vat.computeWithPrecision(ligne_116_1_a + ligne_116_1_b + ligne_116_2_a + ligne_116_2_b + ligne_116_3);

		// boissons non alcooliques
		const rateByHL = qs('#cerfa-3 [name="4296-number"]').dataset.rateByHl || 0;
		const quantity = qs('#cerfa-3 [name="4296-number"]').value || 0;
		qs('#cerfa-3 [name="4296"]').value = Vat.computeWithPrecision(rateByHL * quantity);

		const ligne_131_a = Vat.getValue('3', '4301-a');
		const ligne_131_b = Vat.getValue('3', '4301-b');
		const ligne_131_c = Vat.getValue('3', '4301-c');
		if((ligne_131_a + ligne_131_b - ligne_131_c) < 0) {
			qs('#cerfa-3 [name="4301-total"]').value = Vat.computeWithPrecision(ligne_131_a + ligne_131_b - ligne_131_c);
			qs('#cerfa-3 [name="4300-b"]').value = Vat.computeWithPrecision(ligne_131_a + ligne_131_b - ligne_131_c);
		} else {
			qs('#cerfa-3 [name="4301"]').value = Vat.computeWithPrecision(ligne_131_a + ligne_131_b - ligne_131_c);
		}

		const ligne_133_a = Vat.getValue('3', '4300-a');
		const ligne_133_b = Vat.getValue('3', '4300-b');
		if(ligne_133_a < ligne_133_b) {
			qs('#cerfa-3 [name="4300-c"]').value = Vat.computeWithPrecision(ligne_133_a - ligne_133_b);
		}

		const ligne_133_c = Vat.getValue('3', '4300-c');
		if((ligne_133_a + ligne_133_b) > ligne_133_c) {
			qs('#cerfa-3 [name="4300"]').value = Vat.computeWithPrecision(ligne_133_a + ligne_133_b - ligne_133_c);
		}

		const inputsArray = Array.from(qsa('#cerfa-3 table[data-chapter="annexe"] input', value => value.getAttribute('name')));
		const inputsTaxes = inputsArray.filter(input => input.getAttribute('name').indexOf('-') === -1);
		const taxesSum = inputsTaxes.map(input => parseInt(input.value || 0)).reduce((acc, value) => acc + value || 0, 0);
		qs('#cerfa-3 [name="total-3310A"]').value = Vat.computeWithPrecision(taxesSum);
		qs('#cerfa-3 [name="9979"]').value = Vat.computeWithPrecision(taxesSum);
	}

	static updateDetermination() {

		const ligne_25 = Vat.getValue('3', '0705');
		const ligne_26 = Vat.getValue('3', '8002');

		qs('#cerfa-3 [name="8003"]').value = Vat.computeWithPrecision(ligne_25 - ligne_26);
		qs('#cerfa-3 [name="8113"]').value = Vat.getValue('3', 'Y4');
		qs('#cerfa-3 [name="8114"]').value = Vat.getValue('3', 'Y4');
		qs('#cerfa-3 [name="8103"]').value = Vat.getValue('3', 'X4');
		qs('#cerfa-3 [name="8901"]').value = Vat.computeWithPrecision(Vat.getValue('3', '8900') - Vat.getValue('3', '8103'));
		qs('#cerfa-3 [name="8123"]').value = Vat.getValue('3', 'Z4');

		qs('#cerfa-3 [name="9992"]').value = Vat.computeWithPrecision(Vat.getValue('3', '8901') + Vat.getValue('3', '9979') + Vat.getValue('3', '8123') - Vat.getValue('3', '9991'));
	}

}
