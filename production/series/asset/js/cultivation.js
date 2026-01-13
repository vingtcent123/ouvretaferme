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

	if(e.detail.value === '') {
		return;
	}

	Cultivation.changePlant(this, this.qs('[name="id"]').value, e.detail.value);

});

class Cultivation {

	static changeExpectedHarvest(target, type) {

		const form = target.firstParent('.cultivation-periods-wrapper');

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
		seeds.dataset.action = target.value;

		if(target.value !== 'young-plant-bought') {
			seeds.qs('input').value = '1';
		} else {
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

	static updateDensity(target) {

		const wrapper = target.matches('.cultivation-write') ? target : target.firstParent('.cultivation-write');
		const form = wrapper.firstParent('form');

		const use = wrapper.dataset.use || form.qs('input[name="use"]:checked').value;
		const distance = wrapper.qs('input[name^="distance"]').value;

		let density;

		if(distance === 'spacing') {

			const rowSpacing = parseInt(wrapper.qs('input[name^="rowSpacing"]').value) || null;
			const plantSpacing = parseInt(wrapper.qs('input[name^="plantSpacing"]').value) || null;
			const rows = parseInt(wrapper.qs('input[name^="rows"]').value) || null;

			switch(use) {

				case 'block' :
					density = (rowSpacing !== null && plantSpacing !== null) ? (100 / rowSpacing * 100 / plantSpacing) : null;
					break;

				case 'bed' :
					const bedWidth = parseInt(wrapper.dataset.bedWidth) || parseInt(form.qs('input[name="bedWidth"]').value) || null;
					density = (rows !== null && plantSpacing !== null && bedWidth !== null) ? ((100 / plantSpacing) * rows) : null;
					break;

			}

			wrapper.qs('input[name^="density"]').value = density ? Math.ceil(density * 100) / 100 : '';

		} else {
			density = parseInt(wrapper.qs('input[name^="density"]').value) || null;
		}

		Slice.updateCultivation(wrapper, use, density);

	}

}