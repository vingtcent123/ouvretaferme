document.delegateEventListener('autocompleteSelect', '#crop-create', function(e) {

	new Ajax.Query(this)
		.url('/production/crop:addPlant')
		.body({
			sequence: qs('#crop-create input[name="sequence"]').value,
			plant: e.detail.value
		})
		.fetch();

});

document.delegateEventListener('autocompleteSelect', '#crop-update', function(e) {

	Crop.changePlant(this, this.qs('[name="id"]').value, e.detail.value);

});

class Crop {

	static changeSpacing(target, type) {

		const form = target.firstParent('form');

		const densityGroup = form.qs('[data-wrapper^="density"]');
		const spacingGroup = form.qs('.crop-write-spacing');

		form.qs('[name^="distance"]').value = type;

		switch(type) {

			case 'density' :
				densityGroup.classList.remove('hide')

				spacingGroup.classList.add('hide');
				spacingGroup.qsa('input', field => field.value = '');
				break;

			case 'spacing' :
				densityGroup.classList.add('hide');
				densityGroup.value = '';

				spacingGroup.classList.remove('hide');
				break;

		}

		if(target.firstParent('.cultivation-write') != null) {
			Cultivation.updateDensity(target);
		}

	}

	static changeSeedling(target) {

		const wrapper = target.firstParent('form');

		const seeds = wrapper.qs('[data-wrapper^="seedlingSeeds"]');
		seeds.dataset.action = target.qs('input:checked').value;

	}

	static changeUnit(target, change) {

		ref(change, (node) => node.innerHTML = target.options[target.selectedIndex].text);

	}

	static changePlant(target, crop, newPlant) {

		const body = target.post();
		body.set('id', crop);
		body.set('plant', newPlant);

		new Ajax.Query(target)
			.url('/production/crop:changePlant')
			.body(body)
			.fetch();

	}

}