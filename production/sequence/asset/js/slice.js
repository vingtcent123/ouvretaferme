document.delegateEventListener('change', 'select[data-action="slice-variety-change"]', function(e) {

	const fieldCreate = this.nextElementSibling;

	if(this.value === 'new') {

		this.style.display = 'none';

		fieldCreate.style.display = 'grid';
		fieldCreate.qs('input').focus();

	}

});

document.delegateEventListener('click', '[data-action="slice-variety-create-cancel"]', function(e) {

	const fieldCreate = this.firstParent('.slice-item-create');
	const fieldSelect = fieldCreate.previousElementSibling;

	fieldSelect.value = '';
	fieldSelect.style.display = '';

	fieldCreate.qs('input').value = '';
	fieldCreate.style.display = '';

});

document.delegateEventListener('click', 'a[data-action="slice-add"]', function(e) {

	const id = this.getAttribute('data-id');
	const wrapper = qs(id);

	const clone = qs(id +' .slice-spare').firstChild.cloneNode(true);
	clone.qsa('[name*="spare"]', node => node.setAttribute('name', node.getAttribute('name').replace('spare', 'variety')));

	wrapper.qs('.slice-items > *:last-child').insertAdjacentElement('beforebegin', clone);

});

document.delegateEventListener('click', 'a[data-action="slice-unit"]', function(e) {

	Slice.show(e.target);

});

document.delegateEventListener('click', 'a[data-action="slice-fair"]', function(e) {

	const wrapper = qs(this.getAttribute('data-id'));

	Slice.divide(wrapper);

});

document.delegateEventListener('click', 'a[data-action="slice-remove"]', function(e) {

	const wrapper = this.firstParent('.slice-wrapper');

	this.parentElement.remove();

	Slice.updateSum(wrapper);

});

document.delegateEventListener('input', '.slice-items input[name*=varietyPart]', function(e) {

	const wrapper = this.firstParent('.slice-wrapper');

	Slice.updateSum(wrapper);

});

class Slice {

	static updateCultivation(wrapper, use, density) {

		const area = parseInt(wrapper.dataset.area) || null;
		const length = parseInt(wrapper.dataset.length) || null;
		const sliceUnit = wrapper.qs('input[name^="sliceUnit"]').value;

		let plants;

		switch(use) {

			case 'block' :

				wrapper.qs('[data-action="slice-unit"][data-unit="length"]').classList.add('hide');

				wrapper.qs('[data-action="slice-unit"][data-unit="area"]', node => {

					node.classList.remove('hide');

					if(sliceUnit === 'length') {
						Slice.show(node);
					}

				});

				plants = (area !== null && density !== null) ? Math.round(area * density) : null;

				break;

			case 'bed' :

				wrapper.qs('[data-action="slice-unit"][data-unit="length"]', node => {

					node.classList.remove('hide');

					if(sliceUnit === 'area') {
						Slice.show(node);
					}

				});

				wrapper.qs('[data-action="slice-unit"][data-unit="area"]').classList.add('hide');

				plants = (length !== null && density !== null) ? Math.round(length * density) : null;

				break;

		}

		wrapper.qs('.slice-item-limit[data-unit="length"] .slice-item-max').innerHTML = length ?? '';
		wrapper.qs('.slice-item-limit[data-unit="area"] .slice-item-max').innerHTML = area ?? '';


		wrapper.qs('.slice-item-limit[data-unit="plant"] .slice-item-max').innerHTML = plants ?? '';

		wrapper.qsa('.slice-item-limit[data-unit="tray"]', node => {

			const trays = (plants !== null) ? Math.ceil(plants / parseInt(node.dataset.value)) : null;

			node.qs('.slice-item-max').innerHTML = trays;

		});

		Slice.updateSum(wrapper.qs('.slice-wrapper'));

	}

	static show(target) {
	
		const id = target.getAttribute('data-id');

		const wrapper = qs(id);

		const currentLimit = this.getLimit(wrapper);
		const currentVarieties = this.getVarietiesParts(wrapper);

		const newUnit = target.dataset.unit;
		const newTool = (target.dataset.tool === undefined) ? '' : target.dataset.tool;

		wrapper.qs('[name*="sliceUnit"]').value = newUnit;
		wrapper.qs('[name*="sliceTool"]').value = newTool;

		const newSelector = '[data-unit="'+ newUnit +'"]'+ (newTool !== '' ? '[data-tool="'+ newTool +'"]' : '');

		wrapper.qsa('.slice-item-part', node => node.classList.add('hide'));
		wrapper.qsa('.slice-item-part'+ newSelector, node => node.classList.remove('hide'));

		wrapper.qsa('.slice-item-limit', node => node.classList.add('hide'));
		wrapper.qsa('.slice-item-limit'+ newSelector, node => node.classList.remove('hide'));

		wrapper.qsa('[data-action="slice-unit"]', node => node.classList.remove('selected'));
		wrapper.qsa('[data-action="slice-unit"]'+ newSelector, node => node.classList.add('selected'));

		wrapper.qs('.slice-unit-dropdown').innerHTML = target.dataset.label;

		this.convertSum(
			wrapper,
			currentLimit,
			currentVarieties,
			this.getLimit(wrapper),
			this.getVarietiesParts(wrapper)
		);

		this.updateSum(wrapper);

		target.parentElement.qs('.dropdown-item.selected', node => node.classList.remove('selected'));
		target.classList.add('selected');

	}

	static divide(wrapper) {

		const cake = this.getMax(wrapper);

		const slices = this.getVarietiesParts(wrapper);
		const part = Math.floor(cake / slices.length);
		let rest = cake - part * slices.length;

		slices.forEach(slice => {

			slice.value = part + (rest > 0 ? 1 : 0);
			slice.dispatchEvent(new Event("input"));
			rest--;

		});

		this.updateSum(wrapper);

	}

	static updateSum(wrapper) {

		const unit = this.getUnit(wrapper);
		const limit = this.getLimit(wrapper);

		const sum = this.calculateSum(wrapper);

		limit.qs('.slice-item-sum').innerHTML = sum;

		// Excès de répartition
		let newColor = null;

		if(unit === 'percent') {

			if(sum > 100) {
				newColor = 'color-danger';
			} else if(sum < 100) {
				newColor = 'color-warning';
			} else {
				newColor = 'color-success';
			}

		} else if(limit.qs('.slice-item-max').innerHTML !== '') {

			const max = parseInt(limit.qs('.slice-item-max').innerHTML);

			if(sum > max) {
				newColor = 'color-danger';
			} else if(sum < max) {
				newColor = 'color-warning';
			} else {
				newColor = 'color-success';
			}

		}

		limit.classList.remove('color-danger');
		limit.classList.remove('color-warning');
		limit.classList.remove('color-success');

		if(newColor !== null) {
			limit.classList.add(newColor);
		}

	}

	static getUnit(wrapper) {
		return wrapper.qs('[name*="sliceUnit"]').value;
	}

	static getTool(wrapper) {
		return wrapper.qs('[name*="sliceTool"]').value;
	}

	static getSum(wrapper) {
		return this.getLimit(wrapper).qs('.slice-item-sum');
	}

	static getMax(wrapper) {

		const unit = this.getUnit(wrapper);

		if(unit === 'percent') {
			return 100;
		} else {

			const max = this.getLimit(wrapper).qs('.slice-item-max');

			return max.innerHTML !== '' ? parseInt(max.innerHTML) : 0;

		}

	}

	static getLimit(wrapper) {
		const unit = this.getUnit(wrapper);
		const tool = this.getTool(wrapper);
		return wrapper.qs('.slice-item-limit[data-unit="'+ unit +'"]'+ (tool !== '' ? '[data-tool="'+ tool +'"]' : ''));
	}

	static getVarietiesParts(wrapper) {
		const unit = this.getUnit(wrapper);
		const tool = this.getTool(wrapper);
		return wrapper.qsa('.slice-items .slice-item-part[data-unit="'+ unit +'"]'+ (tool !== '' ? '[data-tool="'+ tool +'"]' : '') +' input[name*=varietyPart]');
	}

	static calculateSum(wrapper) {

		let sum = 0;

		this.getVarietiesParts(wrapper).forEach(node => {
			const value = parseFloat(node.value);
			if(isNaN(value) === false) {
				sum += value;
			}
		});

		return sum;

	}

	// Mise à jour des valeurs par défaut quand on passe d'une unité à une autre
	static convertSum(wrapper, currentLimit, currentVarieties, newLimit, newVarieties) {

		const newSum = parseFloat(newLimit.qs('.slice-item-sum').innerHTML);

		if(newSum > 0) {
			return;
		}

		const currentSum = parseFloat(currentLimit.qs('.slice-item-sum').innerHTML);

		if(currentSum === 0) {
			return;
		}

		const newMax = this.getMax(wrapper);

		let newCalculatedSum = 0;

		currentVarieties.forEach((currentRange, key) => {

			const newRange = newVarieties.item(key);

			const newValue = Math.floor((currentRange.value / currentSum) * newMax);

			newCalculatedSum += newValue;
			newRange.value = newValue;

		});

		// Distribution des arrondis manquants
		for(let key = 0; key < (newMax - newCalculatedSum); key++) {
			newVarieties.item(key).value = parseInt(newVarieties.item(key).value) + 1;
		}

		newVarieties.forEach(newRange => {
			newRange.dispatchEvent(new Event("input"));
		});


	}

}
