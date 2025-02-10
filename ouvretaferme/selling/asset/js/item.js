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

		qs('#item-create-tabs', tabs => {

			const panel = target.firstParent('.tab-panel');
			const products = panel.qsa('[name^="product["]:checked').length;

			tabs.qs('[data-tab="'+ panel.dataset.tab +'"] .tab-item-count').innerHTML = (products > 0) ? products : '';

		});

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
		const checkbox = wrapper.qs('.item-write-checkbox');

		const locked = wrapper.qs('[name^="locked"]').value;

		const basePrice = wrapper.qs('[name^="price"]').value;
		const baseUnitPrice = wrapper.qs('[name^="unitPrice"]').value;
		const baseNumber = wrapper.qs('[name^="number"]').value;

		const packaging = parseFloat(wrapper.qs('[name^="packaging"]')?.value || 1);
		const price = parseFloat(basePrice || 0);
		const unitPrice = parseFloat(baseUnitPrice || 0);
		const number = parseFloat(baseNumber || 0);

		let lockedValue;

		switch(locked) {

			case 'price' :
				lockedValue = (baseNumber !== '' && baseUnitPrice !== '') ? Math.round(100 * packaging * unitPrice * number) / 100 : '';
				wrapper.qs('[name^="price"]').value = lockedValue;
				break;

			case 'unit-price' :
				lockedValue = (baseNumber !== '' && basePrice !== '') ? ((number > 0 && packaging > 0) ? Math.round(100 * price / number / packaging) / 100 : 0) : '';
				wrapper.qs('[name^="unitPrice"]').value = lockedValue;
				break;

			case 'number' :
				lockedValue = (basePrice !== '' && baseUnitPrice !== '') ? ((unitPrice > 0 && packaging > 0) ? Math.round(100 * price / unitPrice / packaging) / 100 : 0) : '';
				wrapper.qs('[name^="number"]').value = lockedValue;
				break;

		}

		if(checkbox) {

			checkbox.checked = (lockedValue !== '');
			this.selectProduct(checkbox);


		}

	}

}