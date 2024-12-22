document.delegateEventListener('autocompleteSelect', '#product-create, #product-update', function(e) {

	const fieldName = this.qs('[name="name"]');

	fieldName.value = e.detail.itemText;

});

class Product {

	static changeUnit(target, change) {

		ref(change, (node) => node.innerHTML = target.value === '' ? '' : target.qs('option[value="'+ target.value +'"]').innerHTML);

	}

	static changeType(target, type) {

		const wrapper = target.firstParent('[data-wrapper="'+ type +'-block"]');

		wrapper.qsa('[name^="'+ type +'Price"], [name^="'+ type +'Packaging"], [name^="'+ type +'Step"]', (node) => {

			if(target.checked === false) {
				node.setAttribute('disabled', 'disabled');
			} else {
				node.removeAttribute('disabled');
			}

			node.value = '';

		});

	}

	static toggleSelection(target) {

		CheckboxField.all(target, '[name^="batch[]"]', undefined, 'table');

		this.changeSelection(target);

	}

	static changeSelection() {

		return Batch.changeSelection(() => {});

	}

}