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

	static checkAvailableFocusIn(input) {

		if(input.value === '') {
			input.removeAttribute('placeholder');
		}

		input.select();

	}

	static checkAvailableFocusOut(input) {

		if(input.value === '') {
			input.setAttribute('placeholder', input.dataset.placeholder);
		}

	}

	static changeSource(target) {

		const source = target.value;

		switch(source) {

			case 'catalog' :
				ref('date-direct', node => node.classList.add('hide'));
				ref('date-catalog', node => node.classList.remove('hide'));
				break;

			case 'direct' :
				ref('date-direct', node => node.classList.remove('hide'));
				ref('date-catalog', node => node.classList.add('hide'));
				break;

		}

	}

	static selectProduct(target) {

		const parent = target.firstParent('.date-products');

		if(target.checked === false) {
			parent.classList.remove('selected');
		} else {
			parent.classList.add('selected');
		}


		qs('#item-create-tabs', tabs => {

			const panel = target.firstParent('.tab-panel');
			const products = panel.qsa('[name^="products["]:checked').length;

			tabs.qs('[data-tab="'+ panel.dataset.tab +'"] .tab-item-count').innerHTML = (products > 0) ? products : '';

		});

	}

}