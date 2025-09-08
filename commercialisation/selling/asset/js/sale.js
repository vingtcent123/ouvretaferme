document.delegateEventListener('click', 'a[data-action="sale-payment-method-add"]', function() {

const clone = qs('#sale-payment-method-wrapper .sale-payment-method-spare').firstChild.cloneNode(true);
clone.qsa('[name*="spare"]', node => node.setAttribute('name', node.getAttribute('name').replace('spare', 'method')));
clone.classList.remove('sale-payment-method-spare-item');

qs('#sale-payment-method-wrapper').qs('.sale-payment-methods > *:last-child').insertAdjacentElement('afterend', clone);

	const lastElement = Array.from(qsa('.sale-payment-method:not(.sale-payment-method-spare-item)')).at(-1);
	Sale.fillLastPaymentMethod(lastElement);

});

document.delegateEventListener('click', 'a[data-action="sale-payment-method-remove"]', function() {

	this.parentElement.remove();

	Sale.updatePaymentMethod();

});
document.delegateEventListener('autocompleteSelect', '#sale-create', function(e) {

	if(e.detail.value === '') {
		return;
	}

	Sale.refreshCustomerCreate(e.detail.value);

});

class Sale {

	static updatePaymentMethod() {

		const calculatedSum = Array.from(qsa('[name^="amountIncludingVat"]'))
			.reduce((acc, value) => acc + parseFloat(value.value || 0), 0);

		const totalSum = qs('.sale-payment-method-total-sum').innerHTML;

		qs('.sale-payment-method-calculated-sum').innerHTML = Math.round(calculatedSum * 100) / 100;

		if(Math.round(calculatedSum * 100) !== Math.round(totalSum * 100)) {

			qs('.sale-payment-method-calculated-sum').classList.remove('color-success');
			qs('.sale-payment-method-calculated-sum').classList.add('color-danger');

		} else {

			qs('.sale-payment-method-calculated-sum').classList.add('color-success');
			qs('.sale-payment-method-calculated-sum').classList.remove('color-danger');

		}

		// S'il ne reste plus qu'un moyen de paiement on ne permet pas de le supprimer
		const elements = qsa('.sale-payment-method:not(.sale-payment-method-spare-item)');
		if(Array.from(elements).length === 1) {

			qs('.sale-payment-method:not(.sale-payment-method-spare-item) a.sale-payment-method-remove').classList.add('hide');

		} else {

			qsa('.sale-payment-method:not(.sale-payment-method-spare-item) a.sale-payment-method-remove', element => element.classList.remove('hide'));
		}

	}

	static fillLastPaymentMethod(element) {

		const calculatedSum = Array.from(qsa('[name^="amountIncludingVat"]'))
			.reduce((acc, value) => acc + parseFloat(value.value || 0), 0);

		const totalSum = qs('.sale-payment-method-total-sum').innerHTML;

		if(totalSum > calculatedSum) {

			element.qs('[name^="amountIncludingVat"]').value = totalSum - calculatedSum;

		}

		Sale.updatePaymentMethod();

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

			let taxes = null;
			let amountIncluding = 0.0;
			let amountExcluding = 0.0;
			let ids = '';

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

			return actions;

		});

	}

	static changePaymentMethod(paymentMethodElement) {
		qs('[data-wrapper="paymentStatus"]').display(paymentMethodElement.value !== '');
	}

}
