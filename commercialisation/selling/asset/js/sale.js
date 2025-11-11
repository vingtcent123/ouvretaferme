document.delegateEventListener('autocompleteSelect', '#sale-create', function(e) {

	if(e.detail.value === '') {
		return;
	}

	Sale.refreshCustomerCreate(e.detail.value);

});

class Sale {

	static refreshCustomerCreate(customer) {

		let request = document.location.href;
		request = request.setArgument('customer', customer);

		new Ajax.Query()
			.url(request)
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

		return Batch.changeSelection('#batch-sale', null, function(selection) {

			let actions = 0;

			let taxes = null;
			let amountIncluding = 0.0;
			let amountExcluding = 0.0;
			let ids = '';
			let idsList = [];

			selection.forEach(node => {

				amountIncluding += parseFloat(node.dataset.batchAmountIncluding);
				amountExcluding += parseFloat(node.dataset.batchAmountExcluding);

				if(node.dataset.batchTaxes !== '') {

					if(taxes === null) {
						taxes = node.dataset.batchTaxes;
					} else if(taxes !== node.dataset.batchTaxes) {
						taxes = 'excluding';
					}

				}

				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			const amount = (taxes === 'excluding') ? amountExcluding : amountIncluding;

			qs(
				'.batch-menu-item-number',
				node => {
					node.innerHTML = money(amount, 2);

					const link = node.firstParent('.batch-menu-amount');
					link.setAttribute('href', link.dataset.url + ids);
				}
			);

			qs(
				'.batch-menu-item-taxes',
				node => node.innerHTML = (taxes === null) ? '' : ((taxes === 'excluding') ? node.dataset.excluding : node.dataset.including)
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
				'.batch-menu-prepared',
				selection.filter('[data-batch~="not-prepared"]').length > 0 ?
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

			qs(
				'.batch-menu-payment-method',
				selection.filter('[data-batch~="not-update-payment"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-menu-prepare',
				selection.filter('[data-batch~="not-prepare"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();

						node.setAttribute('href', node.dataset.url + idsList[0] +'?prepare='+ idsList.join(','));
						actions++;
					}
			);

			return actions;

		});

	}

	static changePaymentMethod(paymentMethodElement) {
		qs('[data-wrapper="paymentStatus"]').display(paymentMethodElement.value !== '');
	}

}
