
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
