document.delegateEventListener('change', '#series-create-tasks select', function(e) {

	const wrapper = qs('#series-create-tasks');

	new Ajax.Query()
		.url('/series/series:getTasksFromSequence')
		.body({
			farm: wrapper.dataset.farm,
			season: wrapper.dataset.season,
			sequence: wrapper.dataset.sequence,
			startYear: wrapper.qs('[name="startYear"]').value,
			startWeek: wrapper.qs('[name="startWeek"]').value
		})
		.fetch();

});


document.delegateEventListener('change', '[data-action="series-cycle-change"] input', function(e) {

	const lifetime = qs('#series-write-perennial-lifetime');

	if(this.value === 'perennial') {
		lifetime.style.display = '';
		lifetime.qs('input').value = '';
	} else {
		lifetime.style.display = 'none';
	}

});

document.delegateEventListener('autocompleteSelect', '#series-create-add-plant', function(e) {

	if(e.detail.value === '') {
		return;
	}

	new Ajax.Query(this)
		.url('/series/series:addPlant')
		.body({
			farm: qs('#series-create-plant input[name="farm"]').value,
			use: qs('#series-create-plant input[name="use"]:checked').value,
			season: qs('#series-create-plant input[name="season"]').value,
			cycle: qs('#series-create-plant').getAttribute('data-cycle'),
			index: qs('#series-create-plant input[name="index"]').value,
			plant: e.detail.value,
		})
		.fetch()
		.then(() => {

			Series.updateName();

		});

});

document.delegateEventListener('change', '#series-create-plant input[name="use"], #series-create-sequence input[name="use"]', function(e) {

	const form = this.firstParent('form');

	const fieldRows = form.qsa('[class~="crop-write-rows"]');
	const fieldRowSpacing = form.qsa('[class~="crop-write-row-spacing"]');

	const fieldAreaTarget = form.qsa('[data-wrapper="areaTarget"]');
	const fieldLengthTarget = form.qsa('[data-wrapper="lengthTarget"]');
	const fieldBedTarget = form.qsa('[data-wrapper="bedWidth"], [data-wrapper="alleyWidth"]');

	if(this.value === 'bed') {

		fieldRows.forEach(node => node.classList.remove('hide'));
		fieldRowSpacing.forEach(node => {
			node.qs('input').value = '';
			node.classList.add('hide');
		});

		fieldLengthTarget.forEach(node => node.classList.remove('hide'));
		fieldBedTarget.forEach(node => node.classList.remove('hide'));
		fieldAreaTarget.forEach(node => {
			node.qs('input').value = '';
			node.classList.add('hide');
		});

	} else {

		fieldRows.forEach(node => {
			node.qs('input').value = '';
			node.classList.add('hide');
		});
		fieldRowSpacing.forEach(node => node.classList.remove('hide'));

		fieldLengthTarget.forEach(node => {
			node.qs('input').value = '';
			node.classList.add('hide');
		});
		fieldBedTarget.forEach(node => node.classList.add('hide'));
		fieldAreaTarget.forEach(node => node.classList.remove('hide'));

	}

});

class Series {

	static selectCreateSeason(target) {
		qsa('#series-create-from input[name="season"]', node => node.value = target.value);
	}

	static changeNameAuto(fieldAuto) {

		const fieldName = fieldAuto.firstParent('.input-group').qs('[name="name"]');

		if(fieldAuto.checked) {
			fieldName.classList.add('disabled');
			fieldName.value = fieldName.getAttribute('data-default');
		} else {
			fieldName.classList.remove('disabled');
		}

	}

	static refreshUpdateUse(target) {

		const form = target.firstParent('form');

		switch(target.value) {

			case 'block' :
				form.qs('.series-update-block', node => node.classList.remove('hide'));
				form.qs('.series-update-bed', node => node.classList.add('hide'));
				break;

			case 'bed' :
				form.qs('.series-update-block', node => node.classList.add('hide'));
				form.qs('.series-update-bed', node => node.classList.remove('hide'));
				break;

		}

	}

	static updateName() {

		const name = qs('#series-create-plant [name="name"]');
		const nameAuto = qs('#series-create-plant [name="nameAuto"]');

		let defaultName = '';
		qsa('#series-create-plant [data-plant-name]', (plant) => defaultName += (defaultName === '' ? '' : ' + ') + plant.dataset.plantName);

		name.dataset.default = defaultName;

		if(nameAuto.checked) {
			name.value = defaultName;
		}

	}

	static deletePlant(target) {

		target.firstParent('.series-create-plant').remove();

		this.showOrHideDeletePlant();
		this.updateName();

	}

	static showOrHideDeletePlant() {

		const plants = qsa('#series-create-plant-list .series-create-plant').length;

		qsa('#series-create-plant-list .series-create-plant-delete', node => (plants > 1) ? node.classList.remove('hide') : node.classList.add('hide'));

	}

	static changeDuplicateSeason(target, fromSeason) {

		const toSeason = parseInt(target.value);

		const form = target.firstParent('form');

		const timesheetField = form.qs('[data-field="copyTimesheet"]');
		const timesheetInfo = form.qs('.series-duplicate-timesheet');
		const seasonInfo = form.qs('.series-duplicate-season');

		if(toSeason !== fromSeason) {
			seasonInfo.classList.remove('hide');
			timesheetInfo.classList.remove('hide');
			timesheetField.classList.add('disabled');
			timesheetField.qs('input[value="0"]').checked = true;
		} else {
			seasonInfo.classList.add('hide');
			timesheetInfo.classList.add('hide');
			timesheetField.classList.remove('disabled');
		}

	}

	static changeDuplicateTasks(target) {

		const hasTasks = !!parseInt(target.value);
		const form = target.firstParent('form')

		const timesheetWrapper = form.qs('[data-wrapper="copyTimesheet"]');
		const actionsWrapper = form.qs('[data-wrapper="copyActions"]');

		if(hasTasks) {
			timesheetWrapper.classList.remove('hide');
			actionsWrapper.classList.remove('hide');
		} else {
			timesheetWrapper.classList.add('hide');
			actionsWrapper.classList.add('hide');
		}

	}

}