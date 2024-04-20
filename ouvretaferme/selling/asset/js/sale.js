document.delegateEventListener('autocompleteSelect', '#sale-create', function(e) {
	Sale.refreshCreateCustomer(e.detail.value);
});

class Sale {

	static refreshCreateCustomer(customer) {

		new Ajax.Query()
			.url('/selling/sale:create?'+ new URLSearchParams({
				farm: qs('#sale-create').form().get('farm'),
				customer: customer
			}))
			.method('get')
			.fetch();

	}

	static submitInvoiceSearch(target) {

		const wrapper = target.firstParent('div');

		let request = document.location.href;
		request = request.setArgument('delivered', wrapper.qs('input[type="number"]').value);

		new Ajax.Query(target)
			.method('get')
			.url(request)
			.fetch();

	}

}