
document.delegateEventListener('autocompleteSelect', '#journal-stock-create [data-autocomplete-field="account"]', function(e) {

	if(e.detail.value.length !== 0) { // Else : l'utilisateur a supprimé la classe
		Stock.cbSelectAccount(e.detail);
	}

});

document.delegateEventListener('autocompleteBeforeQuery', '#journal-stock-create [data-autocomplete-field="accountLabel"]', function(e) {

	if(qs('#journal-stock-create [name="account"]') !== null) {
		const account = qs('#journal-stock-create [name="account"]').value;
		e.detail.body.append('account', account);
	}

});

document.delegateEventListener('autocompleteBeforeQuery', '#journal-stock-create [data-autocomplete-field="variationAccount"]', function(e) {

	if(qs('#journal-stock-create [name="account"]') !== null) {
		const account = qs('#journal-stock-create [name="account"]').value;
		e.detail.body.append('stock', account);
	}

});

document.delegateEventListener('autocompleteBeforeQuery', '#journal-stock-create [data-autocomplete-field="variationAccountLabel"]', function(e) {

	if(qs('#journal-stock-create [name="variationAccount"]') !== null) {
		const account = qs('#journal-stock-create [name="variationAccount"]').value;
		e.detail.body.append('account', account);
	}

});

class Stock {

	static cbSelectAccount(detail) {

		if(qs('#journal-stock-create [name="type"]').value.length === 0) {
			qs('#journal-stock-create [name="type"]').setAttribute('value', detail.description);
		}

	}

	static computeStock() {

		const initialStock = qs('input[name="initialStock"]').value;
		const finalStock = qs('input[name="finalStock"]').value;

		if(initialStock && finalStock) {
			CalculationField.setValue(qs('input[name="variation"]'), Math.round(finalStock - initialStock, 2));
		}

	}

	static updateStock(initialStock) {

		const finalStock = qs('input[name="finalStock"]').value;

		if(initialStock && finalStock) {
			CalculationField.setValue(qs('input[name="variation"]'), Math.round(finalStock - initialStock, 2));
		}

	}
}
