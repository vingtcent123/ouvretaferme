class Association {

	static select(element) {

		const amount = parseInt(element.dataset.amount);

		Association.unselectAll();

		qs('[name="amount"]').value = amount;

		qsa('.block-amount', node => node.classList.remove('selected'));
		element.classList.add('selected');

	}

	static validateCustom(element) {

		qs('[name="amount"]').value = element.value;

	}

	static customFocus(element) {

		Association.unselectAll();
		element.classList.add('selected');

	}

	static unselectAll() {

		qsa('.block-amount', node => node.classList.remove('selected'));
		qs('[name="amount"]').value = '';
		qs('input.block-amount').value = '';

	}
}
