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
	clone.qs('option[value=""]').remove();

	wrapper.qs('.slice-items > *:last-child').insertAdjacentElement('beforebegin', clone);
	wrapper.dataset.n = wrapper.qsa('.slice-items > .slice-item').length;

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

	wrapper.dataset.n = wrapper.qsa('.slice-items > .slice-item').length;

	Slice.updateSum(wrapper);

});

document.delegateEventListener('input', '.slice-items input[name*=varietyPart]', function(e) {

	const wrapper = this.firstParent('.slice-wrapper');

	Slice.updateSum(wrapper);

});

class Slice {

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

		wrapper.qs('.slice-unit-dropdown').innerHTML = target.dataset.label;

		Slice.convertSum(
			wrapper,
			currentLimit,
			currentVarieties,
			this.getLimit(wrapper),
			this.getVarietiesParts(wrapper)
		);

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

		limit.qs('.slice-action-sum').innerHTML = sum;

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

		} else if(limit.qs('.slice-action-max').innerHTML !== '') {

			const max = parseInt(limit.qs('.slice-action-max').innerHTML);

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
		return this.getLimit(wrapper).qs('.slice-action-sum');
	}

	static getMax(wrapper) {

		const unit = this.getUnit(wrapper);

		if(unit === 'percent') {
			return 100;
		} else {

			const max = this.getLimit(wrapper).qs('.slice-action-max');

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
			const value = parseInt(node.value);
			if(isNaN(value) === false) {
				sum += value;
			}
		});

		return sum;

	}

	// Mise à jour des valeurs par défaut quand on passe d'une unité à une autre
	static convertSum(wrapper, currentLimit, currentVarieties, newLimit, newVarieties) {

		const newSum = parseInt(newLimit.qs('.slice-action-sum').innerHTML);

		if(newSum > 0) {
			return;
		}

		const currentSum = parseInt(currentLimit.qs('.slice-action-sum').innerHTML);

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
