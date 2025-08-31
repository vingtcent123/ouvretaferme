class Association {

	static select(element) {

		const amount = parseInt(element.dataset.amount);
		const form = element.firstParent('form');

		Association.unselectAll(form);

		form.qs('[name="amount"]').value = amount;

		form.qsa('.association-amount-block', node => node.classList.remove('selected'));
		element.classList.add('selected');

	}

	static validateCustom(element) {

		const form = element.firstParent('form');

		form.qs('[name="amount"]').value = element.value;

	}

	static customFocus(element) {

		const form = element.firstParent('form');

		Association.unselectAll(form);
		element.classList.add('selected');

	}

	static unselectAll(form) {

		form.qsa('.association-amount-block', node => node.classList.remove('selected'));
		form.qs('[name="amount"]').value = '';
		form.qs('input.association-amount-block').value = '';

	}

	static showDonationForm() {

		qs('#association-join-form-container').hide();
		qs('#association-donate-form-container').removeHide();

	}

	static cleanArgs() {

		setTimeout(() => history.removeArgument('email'), 2500);
		setTimeout(() => history.removeArgument('customer'), 2500);

	}

}
