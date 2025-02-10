document.delegateEventListener('autocompleteSelect', '#item-create', function(e) {

	if(e.detail.value === '') {
		return '';
	}

	new Ajax.Navigation(this)
		.url('/selling/item:create?sale='+ e.target.dataset.sale +'&product='+ e.detail.value)
		.method('get')
		.fetch();

	e.detail.input.value = '';

});

class Item {

	static selectProduct(target) {

		const wrapper = target.firstParent('.item-write');

		if(target.checked === false) {
			wrapper.classList.remove('selected');
		} else {
			wrapper.classList.add('selected');
		}

		// Présence d'onglets
		if(qs('#date-products-tabs')) {

			const panel = target.firstParent('.tab-panel');
			const products = panel.qsa('[name^="productsList["]:checked').length;

			qs('#date-products-tabs [data-tab="'+ panel.dataset.tab +'"] .tab-item-count').innerHTML = (products > 0) ? products : '';

		}

	}

	static addPackaging(target) {

		const wrapper = target.firstParent('.item-write');

		wrapper.qs('.item-write-packaging-label').classList.toggle('hide');
		wrapper.qs('.item-write-unit-label').classList.toggle('hide');
		wrapper.qs('.item-write-packaging-link').classList.toggle('hide');
		wrapper.qs('.item-write-packaging-field').classList.toggle('hide');

	}

	static removePackaging(target) {

		const wrapper = target.firstParent('.item-write');

		wrapper.qs('.item-write-packaging-label').classList.toggle('hide');
		wrapper.qs('.item-write-unit-label').classList.toggle('hide');
		wrapper.qs('.item-write-packaging-link').classList.toggle('hide');
		wrapper.qs('.item-write-packaging-field').classList.toggle('hide');

		const field = target.firstParent('[data-wrapper^="packaging"]');
		field.qs('input').value = '';

	}

	static lock(target) {

		const property = target.dataset.locked;

		const wrapper = target.firstParent('.item-write');

		wrapper.qsa('[data-locked]', node => {
			node.classList.remove('item-write-locked');
			node.nextElementSibling.classList.remove('disabled');
		});

		target.classList.add('item-write-locked');
		target.nextElementSibling.classList.add('disabled');

		wrapper.qs('[name^="locked"]').value = property;

	}

	static recalculateLock(target) {

		const wrapper = target.firstParent('.item-write');

		const locked = wrapper.qs('[name^="locked"]').value;

		const packaging = parseFloat(wrapper.qs('[name^="packaging"]')?.value || 1);
		const price = parseFloat(wrapper.qs('[name^="price"]').value || 0);
		const unitPrice = parseFloat(wrapper.qs('[name^="unitPrice"]').value || 0);
		const number = parseFloat(wrapper.qs('[name^="number"]').value || 0);

		switch(locked) {

			case 'price' :
				wrapper.qs('[name^="price"]').value = Math.round(100 * packaging * unitPrice * number) / 100;
				break;

			case 'unit-price' :
				wrapper.qs('[name^="unitPrice"]').value = (number > 0 && packaging > 0) ? Math.round(100 * price / number / packaging) / 100 : 0;
				break;

			case 'number' :
				wrapper.qs('[name^="number"]').value = (unitPrice > 0 && packaging > 0) ? Math.round(100 * price / unitPrice / packaging) / 100 : 0;
				break;

		}

	}

}