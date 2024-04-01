class ShopManage {

	static changeFqnAuto(fieldAuto) {

		const fieldFqn = fieldAuto.firstParent('.shop-write-fqn').qs('[name="fqn"]');

		if (fieldAuto.checked) {
			fieldFqn.classList.add('disabled');
			this.updateFqnAuto();
		} else {
			fieldFqn.classList.remove('disabled');
		}

	}

	static updateFqnAuto(value) {

		const fieldName = qs('#shop-create [name="name"]');
		const fieldFqn = qs('#shop-create [name="fqn"]');

		if (fieldFqn.classList.contains('disabled') === false) {
			return;
		}

		fieldFqn.value = fieldName.value.toFqn();

	}

	static updatePayment(target) {

		const form = target.firstParent('form');

		form.qs('[name="paymentOffline"]:checked', field => (field.value === '1') ? qs('[data-wrapper="paymentOfflineHow"]').classList.remove('hide') : qs('[data-wrapper="paymentOfflineHow"]').classList.add('hide'));
		form.qs('[name="paymentTransfer"]:checked', field => (field.value === '1') ? qs('[data-wrapper="paymentTransferHow"]').classList.remove('hide') : qs('[data-wrapper="paymentTransferHow"]').classList.add('hide'));

	}

}

document.delegateEventListener('input', '#shop-create [name="name"]', () => {

	ShopManage.updateFqnAuto();

});

class DateManage {

	static checkStockFocusIn(input) {

		if(input.value === '') {
			input.removeAttribute('placeholder');
		}

	}

	static checkStockFocusOut(input) {

		if(input.value === '') {
			input.setAttribute('placeholder', input.dataset.placeholder);
		}

	}

	static selectProduct(input) {

		const parent = input.firstParent('.date-products-item');
		const fields = parent.qsa('.date-products-item-stock, .date-products-item-price');

		if(input.checked === false) {
			fields.forEach(field => field.classList.add('hidden'));
			parent.classList.remove('selected');
		} else {
			fields.forEach(field => field.classList.remove('hidden'));
			parent.classList.add('selected');
		}

	}

}