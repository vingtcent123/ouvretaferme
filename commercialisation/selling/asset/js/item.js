document.delegateEventListener('autocompleteSelect', '[id^="item-create"]', function(e) {

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

		qs('#item-create-tabs', tabs => {

			const panel = target.firstParent('.tab-panel');
			const products = panel.qsa('[name^="product["]:checked').length;

			tabs.qs('[data-tab="'+ panel.dataset.tab +'"] .tab-item-count').innerHTML = (products > 0) ? products : '';

		});

		this.updateSummary();

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

		Item.recalculateLock(target);

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

		Item.recalculateLock(target);

	}

	static recalculateLock(target) {

		const wrapper = target.firstParent('.item-write');

		const locked = wrapper.qs('[name^="locked"]').value;

		const basePrice = wrapper.qs('[name^="price"]').value;
		const baseUnitPrice = wrapper.qs('[name^="unitPrice"]').value;
		const baseUnitPriceDiscount = wrapper.qs('[name^="unitPriceDiscount"]').value;
		const baseNumber = wrapper.qs('[name^="number"]').value;

		const packaging = parseFloat(wrapper.qs('[name^="packaging"]')?.value || 1);
		const price = parseFloat(basePrice || 0);
		const unitPrice = parseFloat(baseUnitPriceDiscount || baseUnitPrice || 0);
		const number = parseFloat(baseNumber || 0);

		switch(locked) {

			case 'price' :
				wrapper.qs('[name^="price"]').value = (baseNumber !== '' && baseUnitPrice !== '') ? Math.round(100 * packaging * unitPrice * number) / 100 : '';
				break;

			case 'unit-price' :
				wrapper.qs('[name^="unitPrice"]').value = (baseNumber !== '' && basePrice !== '') ? ((number > 0 && packaging > 0) ? Math.round(100 * price / number / packaging) / 100 : 0) : '';
				if(baseUnitPriceDiscount) {
					wrapper.qs('[name^="unitPriceDiscount"]').value = '';
				}
				break;

			case 'number' :
				wrapper.qs('[name^="number"]').value = (basePrice !== '' && baseUnitPrice !== '') ? ((unitPrice > 0 && packaging > 0) ? Math.round(100 * price / unitPrice / packaging) / 100 : 0) : '';
				break;

		}

		this.updateSummary();

	}

	static updateSummary() {

		const wrapper = qs('#item-create-tabs');

		// Pas dans le tableau des produits
		if(wrapper === null) {
			return;
		}

		const list = wrapper.qsa('input[name^="product["]:checked');

		let amount = 0;
		list.forEach(node => amount += parseFloat(node.firstParent('.items-products').qs('input[name^="price["]').value) || 0.0);


		if(qs('#items-submit-articles')) {
			qs('#items-submit-articles').innerHTML = list.length;
		}

		if(qs('#items-submit-price')) {
			qs('#items-submit-price').innerHTML = money(amount);
		}

	}

}
