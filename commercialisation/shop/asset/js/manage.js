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

	static changeComment(target) {

		const hasComment = !!parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="commentCaption"]', wrapper => hasComment ?
			wrapper.classList.remove('hide') :
			wrapper.classList.add('hide'));

	}

	static updatePayment(target) {

		const form = target.firstParent('form');

		form.qs('[name="paymentOffline"]:checked', field => (field.value === '1') ? qs('[data-wrapper="paymentOfflineHow"]').classList.remove('hide') : qs('[data-wrapper="paymentOfflineHow"]').classList.add('hide'));
		form.qs('[name="paymentTransfer"]:checked', field => (field.value === '1') ? qs('[data-wrapper="paymentTransferHow"]').classList.remove('hide') : qs('[data-wrapper="paymentTransferHow"]').classList.add('hide'));

	}

    static updatePreview() {
        const form = qs('#shop-customize');

        const iframe = qs('#shop-preview');
        const newSrc = iframe.src
            .setArgument('customize', 1)
            .setArgument('customColor', form.qs('[name="customColor"]').value)
            .setArgument('customBackground', form.qs('[name="customBackground"]').value)
            .setArgument('customFont', form.qs('[name="customFont"]').value)
            .setArgument('customTitleFont', form.qs('[name="customTitleFont"]').value);
        iframe.src = newSrc;
    }

}

document.delegateEventListener('input', '#shop-customize [name="customBackground"], #shop-customize [name="customColor"]', () => {

    ShopManage.updatePreview();

});

document.delegateEventListener('change',
    '#shop-customize [name="customFont"], #shop-customize [name="customTitleFont"]', e => {

		ShopManage.updatePreview();

});

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

			case 'date-catalog' :
				ref('date-direct', node => node.classList.add('hide'));
				ref('date-catalog', node => node.classList.remove('hide'));
				break;

			case 'date-direct' :
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