class Association {

	static getLegalForm(siret) {

		new Ajax.Query()
			.method('get')
			.url('https://suggestions.pappers.fr/v2?cibles=siret&q='+ siret)
			.fetch()
			.then((response) => {
				if(response.resultats_siret[0] === undefined) {
					return;
				}
				qs('[name="legalForm"]').value = response.resultats_siret[0].forme_juridique;
				qs('[data-field="legalForm"]').innerHTML = response.resultats_siret[0].forme_juridique;
			});

	}

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
