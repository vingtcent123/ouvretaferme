document.delegateEventListener('autocompleteSelect', '#sale-create', function(e) {

	if(e.detail.value === '') {
		return;
	}

	Sale.refreshCreateCustomer(e.detail.value);

});

class Sale {

	static toggleMoney(sale) {
		qs('#sale-money-'+ sale).toggle();
	}

	static updateCustomerMoney(sale, value, target) {

		const input = parseFloat(target.value);

		if(isNaN(input) || input < value) {
			qs('#sale-money-'+ sale +'-custom').innerHTML = '';
		} else {
			qs('#sale-money-'+ sale +'-custom').innerHTML = money(input - value, 2);
		}

	}

	static refreshCreateCustomer(customer) {

		const form = qs('#sale-create');

		const shopDate = form.qs('input[name="shopDate"]');

		new Ajax.Query()
			.url('/selling/sale:create?farm='+ form.qs('input[name="farm"]').value + (shopDate ? '&shopDate='+ shopDate.value : '') +'&customer='+ customer)
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

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static toggleDaySelection(target) {

		CheckboxField.all(target.firstParent('tbody').nextSibling, target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection() {

		return Batch.changeSelection(function(selection) {

			let actions = 0;

			let amount = 0.0;
			let ids = '';
			selection.forEach(node => {
				amount += parseFloat(node.dataset.batchAmount);
				ids += '&ids[]='+ node.value;
			});

			qs(
				'.batch-menu-item-number',
				node => {
					node.innerHTML = money(amount, 2);
					node.parentElement.setAttribute('href', node.parentElement.dataset.url + ids);
				}
			);

			qs(
				'.batch-menu-cancel',
				selection.filter('[data-batch~="not-canceled"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-menu-confirmed',
				selection.filter('[data-batch~="not-confirmed"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-menu-delivered',
				selection.filter('[data-batch~="not-delivered"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-menu-delete',
				selection.filter('[data-batch~="not-delete"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			return actions;

		});

	}

}