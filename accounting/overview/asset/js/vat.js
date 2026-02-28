document.delegateEventListener('input', '#cerfa-12 input', function(e) {

	Vat12.recalculateAll();

});
document.delegateEventListener('input', '#cerfa-3 input', function(e) {

	Vat3.recalculateAll();

});

class Vat {

	static toggleJournalFilter(checked) {
		if(checked) {
			qs('#vat-journal').classList.add('vat-journal-filter');
		} else {
			qs('#vat-journal').classList.remove('vat-journal-filter');
		}
	}

	static scrollTo(identifier) {

		const { top: mainTop} = qs('main').getBoundingClientRect();
		const { top: trTop} = qs('[id="'+ identifier +'"]').getBoundingClientRect();
		window.scrollTo({top: trTop - mainTop - 200, behavior: 'smooth'});

		qsa('tr', node => node.classList.remove('row-highlight'));
		qs('tr:has([id="'+ identifier +'"])').classList.add('row-highlight');
	}
	static getValue(cerfa, name) {
		return parseFloat(qs('#cerfa-'+ cerfa +' [name="'+ name +'"]').value || 0);
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


	static updateConsommateursEnergie(cerfa) {

		qs('#cerfa-' + cerfa + ' [name="Y1"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'electricite') - Vat.getValue(cerfa, 'X1')
		);
		qs('#cerfa-' + cerfa + ' [name="M4"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'electricite-majoration') - Vat.getValue(cerfa, 'M1')
		);
		qs('#cerfa-' + cerfa + ' [name="Y2"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'gaz') - Vat.getValue(cerfa, 'X2')
		);
		qs('#cerfa-' + cerfa + ' [name="M5"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'gaz-majoration') - Vat.getValue(cerfa, 'M2')
		);
		qs('#cerfa-' + cerfa + ' [name="Y2"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'charbon') - Vat.getValue(cerfa, 'X3')
		);
		qs('#cerfa-' + cerfa + ' [name="M6"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'charbon-majoration') - Vat.getValue(cerfa, 'M3')
		);
		qs('#cerfa-' + cerfa + ' [name="YA"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'others') - Vat.getValue(cerfa, 'XA')
		);

		qs('#cerfa-' + cerfa + ' [name="X4"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'X1') +
			Vat.getValue(cerfa, 'M1') +
			Vat.getValue(cerfa, 'X2') +
			Vat.getValue(cerfa, 'M2') +
			Vat.getValue(cerfa, 'X3') +
			Vat.getValue(cerfa, 'M3') +
			Vat.getValue(cerfa, 'XA')
		);

		qs('#cerfa-' + cerfa + ' [name="Y4"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'Y1') +
			Vat.getValue(cerfa, 'M4') +
			Vat.getValue(cerfa, 'Y2') +
			Vat.getValue(cerfa, 'M5') +
			Vat.getValue(cerfa, 'Y3') +
			Vat.getValue(cerfa, 'M6') +
			Vat.getValue(cerfa, 'YA')
		);

		qs('#cerfa-' + cerfa + ' [name="Z4"]').value = Vat.computeWithPrecision(
			Vat.getValue(cerfa, 'Z1') +
			Vat.getValue(cerfa, 'M7') +
			Vat.getValue(cerfa, 'Z2') +
			Vat.getValue(cerfa, 'M8') +
			Vat.getValue(cerfa, 'Z3') +
			Vat.getValue(cerfa, 'M9') +
			Vat.getValue(cerfa, 'ZB')
		);

		const val_X4 = Vat.getValue(cerfa, 'X4');
		qs('#cerfa-' + cerfa + ' [name="8103"]').value = val_X4;
	}
}

class Vat3 {

	static recalculateAll() {

		Vat3.updateAnnexe();
		Vat3.updateVatBrute();
		Vat3.updateVatNette();
		Vat.updateConsommateursEnergie('3');
		Vat3.updateDetermination();

	}
	
	static updateVatBrute() {

		const inputsArray = Array.from(qsa('#cerfa-3 table[data-chapter="B"] input', value => value.getAttribute('name')));

		// Calculs automatiques via la base imposable
		const inputsBases = inputsArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);

		inputsBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('#cerfa-3 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})

		const inputsAnnexeArray = Array.from(qsa('#cerfa-3 table[data-chapter="annexe"] input', value => value.getAttribute('name')));

		// Calculs automatiques via la base imposable
		const inputsAnnexeBases = inputsAnnexeArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);

		inputsAnnexeBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('#cerfa-3 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})

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

		const totalAnnexe = Vat.getValue('3', '4213') +
			Vat.getValue('3', '4215') + Vat.getValue('3', '4238') +
			Vat.getValue('3', '4220') + Vat.getValue('3', '4334') +
			Vat.getValue('3', '4207') + Vat.getValue('3', '4328') +
			Vat.getValue('3', '4329') + Vat.getValue('3', '58a') +
			Vat.getValue('3', '58b') + Vat.getValue('3', '4332') +
			Vat.getValue('3', '4333') + Vat.getValue('3', '60a') +
			Vat.getValue('3', '60b') + Vat.getValue('3', '4314') +
			Vat.getValue('3', '4315') + Vat.getValue('3', '4206') +
			Vat.getValue('3', '4226') + Vat.getValue('3', '4324') +
			Vat.getValue('3', '4325') + Vat.getValue('3', '4217') +
			Vat.getValue('3', '4239') + Vat.getValue('3', '4326') +
			Vat.getValue('3', '4236') + Vat.getValue('3', '4243') +
			Vat.getValue('3', '4244') + Vat.getValue('3', '4252') +
			Vat.getValue('3', '4253') + Vat.getValue('3', '4254') +
			Vat.getValue('3', '4247') + Vat.getValue('3', '4248') +
			Vat.getValue('3', '4249') + Vat.getValue('3', '4250') +
			Vat.getValue('3', '4273') + Vat.getValue('3', '4274') +
			Vat.getValue('3', '4321') + Vat.getValue('3', '4268') +
			Vat.getValue('3', '4270') + Vat.getValue('3', '4269') +
			Vat.getValue('3', '4271') + Vat.getValue('3', '4272') +
			Vat.getValue('3', '4256') + Vat.getValue('3', '4259') +
			Vat.getValue('3', '4255') + Vat.getValue('3', '4336') +
			Vat.getValue('3', '4266') + Vat.getValue('3', '4267') +
			Vat.getValue('3', '4309') + Vat.getValue('3', '4310') +
			Vat.getValue('3', '4311') + Vat.getValue('3', '4306') +
			Vat.getValue('3', '4307') + Vat.getValue('3', '4308') +
			Vat.getValue('3', '4258') + Vat.getValue('3', '4261') +
			Vat.getValue('3', '4312') + Vat.getValue('3', '4304') +
			Vat.getValue('3', '4337') + Vat.getValue('3', '4283') +
			Vat.getValue('3', '4284') + Vat.getValue('3', '4285') +
			Vat.getValue('3', '4277') + Vat.getValue('3', '4303') +
			Vat.getValue('3', '4313[value]') + Vat.getValue('3', '4335') +
			Vat.getValue('3', '4291') + Vat.getValue('3', '4294') +
			Vat.getValue('3', '4296') + Vat.getValue('3', '4295') +
			Vat.getValue('3', '4293') + Vat.getValue('3', '4322') +
			Vat.getValue('3', '4301') + Vat.getValue('3', '4300');
		qs('#cerfa-3 [name="total-3310A"]').value = Vat.computeWithPrecision(totalAnnexe);
		qs('#cerfa-3 [name="9979"]').value = Vat.computeWithPrecision(totalAnnexe);
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
		qs('#cerfa-3 [name="9991"]').value = Vat.computeWithPrecision(Vat.getValue('3', '8901') + Vat.getValue('3', '9979') + Vat.getValue('3', '8123'));

		// TODO Émilie Pas compris le calcul de la case 32
		qs('#cerfa-3 [name="9992"]').value = Vat.computeWithPrecision(Vat.getValue('3', '8901') + Vat.getValue('3', '9979') + Vat.getValue('3', '8123'));
	}

}

class Vat12 {

	static recalculateAll() {

		Vat12.updateVatBrute();
		Vat12.updateVatDeductible();
		Vat12.updateVatNette();
		Vat12.updateTaxesAssimilees();
		Vat.updateConsommateursEnergie('12');
		Vat12.updateRecapitulationBase();
		Vat12.updateReimburse();

	}

	static updateVatBrute() {

		const inputsArray = Array.from(qsa('#cerfa-12 table[data-chapter="1"] input', value => value.getAttribute('name')));

		// Calculs automatiques via la base imposable
		const inputsBases = inputsArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);
		inputsBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('#cerfa-12 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})

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

		// Calculs automatiques via la base imposable
		const inputsBases = inputsArray.filter(input => input.getAttribute('name').indexOf('-base') > -1 && input.dataset.rate !== undefined);
		inputsBases.forEach((input) => {
			const name = input.getAttribute('name').slice(0, input.getAttribute('name').indexOf('-'));
			qs('#cerfa-12 [name="' + name + '"]').value = Vat.computeWithPrecision(parseFloat(input.dataset.rate) * input.value / 100);
		})
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
