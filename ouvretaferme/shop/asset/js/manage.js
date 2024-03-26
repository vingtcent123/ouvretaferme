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
		const paymentMethods = form.qsa('[name="paymentCard"][value="1"]:checked').length;

		form.qs('[data-wrapper="paymentOnlineOnly"]', wrapper => (paymentMethods > 0) ? wrapper.classList.remove('hide') : wrapper.classList.add('hide'));

	}

	static updatePaymentOnlineOnly(target) {

		const form = target.firstParent('form');

		form.ref('payment-online', node => parseBool(target.value) ? node.removeHide() : node.hide());
		form.ref('payment-offline', node => parseBool(target.value) ? node.hide() : node.removeHide());
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