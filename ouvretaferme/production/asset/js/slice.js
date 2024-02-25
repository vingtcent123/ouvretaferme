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

	const id = this.getAttribute('data-id');

	const wrapper = qs(id);

	const currentUnit = wrapper.qs('[name*="sliceUnit"]').value;
	const newUnit = this.dataset.unit;

	wrapper.qsa('.slice-item-part', node => node.classList.add('hide'));
	wrapper.qsa('.slice-item-part[data-unit="'+ this.dataset.unit +'"]', node => node.classList.remove('hide'));

	wrapper.qsa('.slice-action-limit', node => node.classList.add('hide'));
	wrapper.qsa('.slice-action-limit[data-unit="'+ this.dataset.unit +'"]', node => node.classList.remove('hide'));

	wrapper.qsa('[data-action="slice-unit"]', node => node.classList.remove('hide'));
	wrapper.qsa('[data-action="slice-unit"][data-unit="'+ this.dataset.unit +'"]', node => node.classList.add('hide'));
	wrapper.qs('[name*="sliceUnit"]').value = newUnit;

	Slice.convertSum(wrapper, currentUnit, newUnit);

});

document.delegateEventListener('click', 'a[data-action="slice-fair"]', function(e) {

	const id = this.getAttribute('data-id');
	const wrapper = qs(id);

	Slice.divide(wrapper);

});

document.delegateEventListener('click', 'a[data-action="slice-remove"]', function(e) {

	const wrapper = this.firstParent('.slice-wrapper');

	this.parentElement.remove();

	wrapper.dataset.n = wrapper.qsa('.slice-items > .slice-item').length;

	Slice.updateSum(wrapper);

});

document.delegateEventListener('input', '.slice-items .slice-item-part input[type="range"]', function(e) {

	const wrapper = this.firstParent('.slice-wrapper');

	Slice.updateSum(wrapper);

});

class Slice {

	static divide(wrapper) {

		let unit, cake;

		wrapper.qs('[name*="sliceUnit"]', node => unit = node.value, () => unit = 'percent');

		const limit = wrapper.qs('.slice-action-limit[data-unit="'+ unit +'"]');

		switch(unit) {

			case 'percent' :
				cake = 100;
				break;

			case 'area' :
			case 'length' :
				cake = parseInt(limit.qs('.slice-action-max').innerHTML);
				break;

		}

		const slices = wrapper.qsa('.slice-items .slice-item-part[data-unit="'+ unit +'"] input[type="range"]');
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

		let unit;

		wrapper.qs('[name*="sliceUnit"]', node => unit = node.value, () => unit = 'percent');

		const limit = wrapper.qs('.slice-action-limit[data-unit="'+ unit +'"]');

		let sum = 0;
		wrapper.qsa('.slice-items .slice-item-part[data-unit="'+ unit +'"] input[type="range"]', node => sum += parseInt(node.value));

		limit.qs('.slice-action-sum').innerHTML = sum;

		// Excès de répartition
		if(
			(unit === 'percent' && sum > 100) ||
			(['area', 'length'].includes(unit) && sum > parseInt(limit.qs('.slice-action-max').innerHTML))
		) {
			limit.classList.add('color-danger');
		} else {
			limit.classList.remove('color-danger');
		}

	}

	// Mise à jour des valeurs par défaut quand on passe d'une unité à une autre
	static convertSum(wrapper, currentUnit, newUnit) {

		const newSum = parseInt(wrapper.qs('.slice-action-limit[data-unit="'+ newUnit +'"] .slice-action-sum').innerHTML);

		if(newSum > 0) {
			return;
		}

		const currentSum = parseInt(wrapper.qs('.slice-action-limit[data-unit="'+ currentUnit +'"] .slice-action-sum').innerHTML);

		if(currentSum === 0) {
			return;
		}

		const newMax = (newUnit === 'percent') ? 100 : parseInt(wrapper.qs('.slice-action-limit[data-unit="'+ newUnit +'"] .slice-action-max').innerHTML);

		const currentList = wrapper.qsa('.slice-item-part[data-unit="'+ currentUnit +'"] [type="range"]');
		const newList = wrapper.qsa('.slice-item-part[data-unit="'+ newUnit +'"] [type="range"]');

		let newCalculatedSum = 0;

		currentList.forEach((currentRange, key) => {

			const newRange = newList.item(key);

			const newValue = Math.floor((currentRange.value / currentSum) * newMax);

			newCalculatedSum += newValue;
			newRange.value = newValue;

		});

		// Distribution des arrondis manquants
		for(let key = 0; key < (newMax - newCalculatedSum); key++) {
			newList.item(key).value = parseInt(newList.item(key).value) + 1;
		}

		newList.forEach(newRange => {
			newRange.dispatchEvent(new Event("input"));
		});


	}

}
