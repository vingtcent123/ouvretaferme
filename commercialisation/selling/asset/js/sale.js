document.delegateEventListener('autocompleteSelect', '#sale-create', function(e) {

	if(e.detail.value === '') {
		return;
	}

	Sale.refreshCustomerCreate(e.detail.value);

});

class Sale {

	static customize(target) {

		target.classList.add('hide');
		qs('#sale-customize').classList.remove('hide');

	}

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

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"], .batch-all-group');

		this.changeSelection(target);

	}

	static toggleGroupSelection(target) {

		CheckboxField.all(target.firstParent('tbody').nextSibling, target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection() {

		const updateStatus = function(action, number) {

			qs('.batch-preparation-status .batch-'+ action);

		};

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
				'.batch-item-number',
				node => {
					node.innerHTML = money(amount, 2);

					const link = node.firstParent('.batch-amount');
					link.setAttribute('href', link.dataset.url + ids);
				}
			);

			qs(
				'.batch-item-taxes',
				node => node.innerHTML = (taxes === null) ? '' : ((taxes === 'excluding') ? node.dataset.excluding : node.dataset.including)
			);

			qs(
				'.batch-prepare',
				selection.filter('[data-batch~="not-prepare"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();

						node.setAttribute('href', node.dataset.url + idsList[0] +'?prepare='+ idsList.join(','));
						actions++;
					}
			);

		});

	}

	static changePaymentMethod(paymentMethodElement) {
		qs('[data-wrapper="paymentStatus"]').display(paymentMethodElement.value !== '');
	}

}
