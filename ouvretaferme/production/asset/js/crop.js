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
		const value = target.qs('input:checked').value;

		const seeds = wrapper.qs('[data-wrapper^="seedlingSeeds"]');
		seeds.qs('input').value = '1';
		seeds.dataset.action = value;

		const actionSemisPepiniere = wrapper.qs('[data-wrapper*="semis-pepiniere"]');
		const actionSemisDirect = wrapper.qs('[data-wrapper*="semis-direct"]');
		const actionPlantation = wrapper.qs('[data-wrapper*="plantation"]');

		if(
			actionSemisPepiniere === null ||
			actionSemisDirect === null ||
			actionPlantation === null
		) {
			return;
		}

		switch(value) {

			case 'young-plant' :
				actionSemisPepiniere.removeHide();
				actionSemisDirect.hide();
				actionPlantation.removeHide();
				break;

			case 'sowing' :
				actionSemisPepiniere.hide();
				actionSemisDirect.removeHide();
				actionPlantation.hide();
				break;

			default :
				actionSemisPepiniere.hide();
				actionSemisDirect.hide();
				actionPlantation.hide();
				break;

		}

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