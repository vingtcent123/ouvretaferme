
document.delegateEventListener('autocompleteSelect', '#preaccounting-payment-customer input[data-autocomplete-field="customer"]', function(e) {

	const url = qs('form#preaccounting-payment-customer').getAttribute('action') + '&customer=' + e?.detail?.value;
	Lime.History.replaceState(url);

	new Ajax.Query()
		.url(url)
		.method('get')
		.fetch();

});
document.delegateEventListener('autocompleteUpdate', '#preaccounting-payment-customer input[data-autocomplete-field="customer"]', function(e) {

	if(e.detail.value) {

		const url = qs('form#preaccounting-payment-customer').getAttribute('action') + '&customer=' + e.detail.value;
		Lime.History.replaceState(url);

		new Ajax.Query()
			.url(url)
			.method('get')
			.fetch();
	}
});

class Preaccounting {

	static updatePaymentMethod(target) {

		const invoice = target.dataset.invoice;
		const paymentMethod = target.options[target.selectedIndex].value;
		const paymentStatus = target.dataset.paymentStatus;

		new Ajax.Query()
			.url('/selling/invoice:doUpdatePayment')
			.method('post')
			.body({
					id: invoice, paymentMethod, paymentStatus
			})
			.fetch();
	}

	static export() {

		if(qs('[data-accounting-action="export"]').classList.contains('btn-warning')) {
			return false;
		}

		const url = qs('[data-accounting-action="export"]') + '&from=' + qs('[name="from"]').value + '&to=' + qs('[name="to"]').value;

		new Ajax.Query()
			.url(url)
			.method('get')
			.fetch();
	}

	static toggleGroupSelection(target) {

		CheckboxField.all(target.firstParent('tbody, thead').nextSibling, target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection(target) {

		const type = target.getAttribute('batch-type');

		return Batch.changeSelection('#batch-accounting-' + type, null, function(selection) {

			let ids = '';
			let idsList = [];

			selection.forEach(node => {


				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			return 1;

		});

	}

}
