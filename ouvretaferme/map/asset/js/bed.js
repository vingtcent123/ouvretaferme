document.delegateEventListener('click', '#bed-create-button', function(e) {

	let number = parseInt(qs('#bed-create-number').value);

	if(isNaN(number)) {
		number = 0;
	}

	let nodeNames = qs('#bed-create-names');

	let already = nodeNames.childNodes.length;

	if(number === already) {
		return;
	}

	qs('#bed-create-form').setAttribute('data-number', number);

	if(number > already) {

		let input = nodeNames.getAttribute('data-input');

		while(already < number) {

			nodeNames.insertAdjacentHTML('beforeend', input);
			nodeNames.qs('.bed-create-one:last-child h5', input => input.innerHTML += ' '+ (already + 1));

			already++;

		}

	} else {

		while(number < already) {

			nodeNames.qs('.bed-create-one:last-of-type', input => input.remove());

			already--;

		}

	}

});

document.delegateEventListener('click', '#bed-create-auto', function(e) {

	const prefix = qs('#bed-create-prefix').value;
	let start = parseInt(qs('#bed-create-start').value);

	qsa('#bed-create-names input[name="names[]"]', input => input.value = prefix + start++);

});

document.delegateEventListener('input', 'div.bed-write-size-form [name="length"], div.bed-write-size-form [name="width"]', function(e) {

	const wrapper = this.firstParent('div.bed-write-size');

	const length = parseInt(wrapper.qs('[name="length"]').value);
	const width = parseInt(wrapper.qs('[name="width"]').value);

	wrapper.qs('div.bed-write-size-area', node => {

		if(isNaN(length) || isNaN(width)) {
			node.setAttribute('data-area', 0);
			return;
		}

		const area = length * width / 100;

		node.setAttribute('data-area', area);
		node.qs('span').innerHTML = area;

	});

});

class Bed {

	static openConfigure(url, plot) {

		qs('#bed-update-selection-'+ plot, (node) => node.remove(), () => new Ajax.Query()
		.url(url)
		.method('get')
		.fetch());

	}

	static toggleSelection(target) {

		CheckboxField.all(target, '[name^="ids[]"]');

		this.changeSelection(target);

	}

	static changeSelection(target) {

		const wrapper = target.firstParent('.bed-update-grid');

		let greenhouses = 0;
		let drawings = 0;

		wrapper.qsa('[name="ids[]"]:checked', input => {
			greenhouses += (input.dataset.greenhouse !== '') ? 1 : 0;
			drawings += (input.dataset.drawn === '1') ? 1 : 0;
		});

		if(greenhouses > 0) {
			wrapper.qs('[data-batch="greenhouse-delete"]', node => node.classList.remove('hide'));
		} else {
			wrapper.qs('[data-batch="greenhouse-delete"]', node => node.classList.add('hide'));
		}

		if(drawings > 0) {
			wrapper.qs('[data-batch="draw-delete"]', node => node.classList.remove('hide'));
		} else {
			wrapper.qs('[data-batch="draw-delete"]', node => node.classList.add('hide'));
		}

	}

}