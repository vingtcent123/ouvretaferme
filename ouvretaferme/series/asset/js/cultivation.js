document.delegateEventListener('autocompleteSelect', '#cultivation-create', function(e) {

	new Ajax.Query(this)
		.url('/series/cultivation:addPlant')
		.body({
			series: qs('#cultivation-create input[name="series"]').value,
			plant: e.detail.value
		})
		.fetch();

});

document.delegateEventListener('autocompleteSelect', '#cultivation-update', function(e) {

	Cultivation.changePlant(this, this.qs('[name="id"]').value, e.detail.value);

});

class Cultivation {

	static changeExpectedHarvest(target, type) {

		const form = target.firstParent('form');

		const selectorGroup = form.qs('[name^="harvestPeriodExpected"]');
		const monthGroup = form.qs('[data-wrapper^="harvestMonthsExpected"]');
		const weekGroup = form.qs('[data-wrapper^="harvestWeeksExpected"]');

		selectorGroup.value = type;

		switch(type) {

			case 'month' :
				monthGroup.classList.remove('hide')
				weekGroup.classList.add('hide');
				break;

			case 'week' :
				weekGroup.classList.remove('hide')
				monthGroup.classList.add('hide');
				break;

		}

	}

	static changeUnit(target, change) {

		ref('cultivation-unit-'+ change, (node) => node.innerHTML = target.options[target.selectedIndex].text);
		ref('cultivation-weight-'+ change, (node) => target.value !== 'kg' ? node.hide() : node.removeHide());

	}

	static changeSeedling(target) {

		const wrapper = target.firstParent('.series-write-plant');

		const seeds = wrapper.qs('[data-wrapper^="seedlingSeeds"]');

		if(target.value === 'young-plant') {
			seeds.removeHide();
			seeds.qs('input').value = '1';
		} else {
			seeds.hide();
			seeds.qs('input').value = '';
		}

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

		switch(target.value) {

			case 'young-plant' :
				actionSemisPepiniere.removeHide();
				actionSemisDirect.hide();
				actionPlantation.removeHide();
				break;

			case 'young-plant-bought' :
				actionSemisPepiniere.hide();
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

	static changePlant(target, cultivation, newPlant) {

		const body = target.post();
		body.set('id', cultivation);
		body.set('plant', newPlant);

		new Ajax.Query(target)
			.url('/series/cultivation:changePlant')
			.body(body)
			.fetch();

	}

}