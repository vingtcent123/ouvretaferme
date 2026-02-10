document.delegateEventListener('input', '#payment-update-list input[name^="amount"]', function(e) {

	Payment.recalculate();

});

class Payment {

	static add() {


		const position = parseInt(qs('#payment-update-new').dataset.position);
		qs('#payment-update-new').dataset.position = position + 1;

		let h = qs('#payment-update-new').innerHTML;
		h = h.replaceAll('[]', '['+ position +']')

		qs('#payment-update-list').insertAdjacentHTML('beforeend', h);

		// On ajoute le montant manquant par défaut
		const unbalanced = this.getUnbalanced();

		if(unbalanced !== 0.0) {
			qs('input[name="amountIncludingVat['+ position +']"]').value = unbalanced;
			this.recalculate();
		}

	}

	static magic(target) {

		const unbalanced = this.getUnbalanced();

		if(unbalanced !== 0.0) {

			const input = target
				.firstParent('.payment-update-amount')
				.qs('input[name^="amountIncludingVat"]');

			const value = (input.value === '') ? 0 : Number(input.value);

			if(isNaN(value) === false) {
				input.value = (value + unbalanced).toFixed(2);
			}

			this.recalculate();

		}

	}

	static getUnbalanced() {

		const wrapper = qs('#payment-update-total');
		const balance = Number(wrapper.dataset.target) - Number(wrapper.dataset.value);

		if(isNaN(balance)) {
			return 0.0;
		} else {
			return balance;
		}

	}

	static recalculate() {

		const wrapperTotal = qs('#payment-update-total');
		const wrapperList= qs('#payment-update-list');

		let total = 0.0;

		qsa('#payment-update-list input[name^="amount"]', (input) => {

			if(input.value !== '') {

				let value = input.value;
				total += parseFloat(value);
			}

		});

		wrapperTotal.dataset.value = total;
		qs('#payment-update-amount').innerHTML = formatNumber(total);

		if(wrapperTotal.dataset.target != wrapperTotal.dataset.value) {
			wrapperTotal.classList.add('payment-update-total-error');
			wrapperList.dataset.balanced = '0';
		} else {
			wrapperTotal.classList.remove('payment-update-total-error');
			wrapperList.dataset.balanced = '1';
		}

	}

	static delete(target) {

		target.firstParent('.payment-update').remove();

		this.recalculate();

	}

}
