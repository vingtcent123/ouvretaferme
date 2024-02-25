document.delegateEventListener('autocompleteSelect', '#item-add', function(e) {

	e.detail.input.value = '';
	e.detail.input.blur();

	Item.addToForm(e.detail.value);

});
/*
Garder ça pour une recherche par id
document.delegateEventListener('autocompleteSource', '#item-add', function(e) {

	if(
		e.detail.results.length === 1 &&
		e.detail.results[0].shortcut.toUpperCase() === e.detail.query.toUpperCase()
	) {
		AutocompleteField.apply(e.detail.input, e.detail.results[0]);
	}

});*/

document.delegateEventListener('input', '#item-add [name^="packaging"], #item-add [name="unitPrice"], #item-add [name^="number"]', function(e) {

	Item.updateForm();

});

class Item {

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

		const packaging = parseFloat(wrapper.qs('[name^="packaging"]').value || 1);
		const price = parseFloat(wrapper.qs('[name^="price"]').value || 0);
		const unitPrice = parseFloat(wrapper.qs('[name^="unitPrice"]').value || 0);
		const number = parseFloat(wrapper.qs('[name^="number"]').value || 0);

		switch(locked) {

			case 'price' :
				wrapper.qs('[name^="price"]').value = Math.round(100 * packaging * unitPrice * number) / 100;
				break;

			case 'unit-price' :
				wrapper.qs('[name^="unitPrice"]').value = Math.round(100 * price / number / packaging) / 100;
				break;

			case 'number' :
				wrapper.qs('[name^="number"]').value = Math.round(100 * price / unitPrice / packaging) / 100;
				break;

		}

	}

	static addToForm(product) {

		const form = qs('#item-add');

		new Ajax.Query(form)
			.url('/selling/item:one')
			.body({
				id: form.qs('[name="id"]').value,
				product: product
			})
			.fetch();

	}

	static removeFromFrom(selector) {

		selector.firstParent('.item-add-one').remove();

		this.updateForm();
		this.renameFormItems();

	}

	static updateForm() {

		const form = qs('#item-add');

		let countItems = 0;
		let sumPrices = 0;

		form.qsa('.item-add-one', node => {

			const packaging = node.qs('[name^="packaging"]');
			const unitPrice = node.qs('[name^="unitPrice"]');
			const number = node.qs('[name^="number"]');

			if(unitPrice.value !== '' && number.value !== '') {

				let price = parseFloat(unitPrice.value) * parseFloat(number.value);

				if(packaging.value !== '') {
					price = price * parseFloat(packaging.value);
				}

				// Gestion des arrondis
				price = Math.round(price * 100) / 100;

				countItems++;
				sumPrices += price;

				node.ref('product-price', value => value.innerHTML = price);
				node.qs('.item-add-one-price', price => price.style.display = 'block');

			} else {

				node.ref('product-price', value => value.innerHTML = '');
				node.qs('.item-add-one-price', price => price.style.display = 'none');

			}

		})

		if(countItems > 0) {
			form.ref('product-item-count', value => value.innerHTML = countItems);
			form.ref('product-price-sum', value => value.innerHTML = sumPrices);
			form.qs('.item-add-submit-stats').classList.add('visible');
		} else {
			form.qs('.item-add-submit-stats').classList.remove('visible');
		}

	}

	// Remet à zéro le compteur de produits dans le formulaire
	static renameFormItems() {

		const items = qsa('#item-add .item-add-one');

		if(items) {

			let position = items.length - 1;

			items.forEach(item => {

				item.ref('product-number', node => node.innerHTML = position + 1);

				item.qsa('input[name], select[name]', node => {
					node.name = node.name.replace(/\[[0-9]+\]/, '[' + position + ']');
				});

				item.qsa('[data-wrapper]', node => {
					node.dataset.wrapper = node.dataset.wrapper.replace(/\[[0-9]+\]/, '[' + position + ']');
				});

				position--;

			});

		}

	}

}