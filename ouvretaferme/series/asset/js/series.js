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

	static changeAllSelection(target) {

		qsa('#series-wrapper .series-item-planning-checkbox', node => node.checked = target.checked);

		this.changeSelection();

	}

	static changePlantSelection(target, plantId) {

		qsa('#series-wrapper [data-plant="'+ plantId +'"][name="batch[]"]', node => node.checked = target.checked);

		this.changeSelection();

	}

	static changeSelection() {

		return Batch.changeSelection(function(selection) {

			if(selection.filter('[data-batch~="not-open"]').length > 0) {
				qsa('.batch-menu-open', button => button.hide());
			} else {
				qsa('.batch-menu-open', button => button.removeHide());
			}

			if(selection.filter('[data-batch~="not-close"]').length > 0) {
				qsa('.batch-menu-close', button => button.hide());
			} else {
				qsa('.batch-menu-close', button => button.removeHide());
			}

			if(selection.filter('[data-batch~="not-duplicate"]').length > 0) {
				qsa('.batch-menu-duplicate', button => button.hide());
			} else {
				qsa('.batch-menu-duplicate', button => button.removeHide());
			}

			if(selection.filter('[data-batch~="not-season"]').length > 0) {
				qsa('.batch-menu-season', button => button.hide());
			} else {
				qsa('.batch-menu-season', button => button.removeHide());
			}

		});

	}

	static hideSelection() {

		qs('#batch-group').hide();

		qsa('#series-wrapper .series-item-planning-checkbox:checked', (field) => field.checked = false);

	}

	static selectCreateSeason(target) {
		qsa('#series-create-from input[name="season"]', node => node.value = target.value);
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

	static changeNameAuto(target) {

		target.dataset.auto = 'false';

	}

	static updateName() {

		const name = qs('#series-create-plant [name="name"]');

		if(name.dataset.auto === 'false') {
			return;
		}

		let defaultName = '';
		qsa('#series-create-plant [data-plant-name]', (plant) => defaultName += (defaultName === '' ? '' : ' + ') + plant.dataset.plantName);

		name.value = defaultName;

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

	static changeDuplicateCopies(target, increment) {

		const form = target.firstParent('form');
		const wrapper = target.firstParent('.series-duplicate-copies');
		const input = wrapper.qs('input');

		const value = parseInt(input.value) + increment;
		const limit = parseInt(wrapper.dataset.limit);

		if(value > 0 && value <= limit) {
			input.value = value;
			wrapper.qs('.series-duplicate-copies-value').innerHTML = value;
		} else {
			return;
		}

		wrapper.qsa('.series-duplicate-copies-disabled', node => node.classList.remove('series-duplicate-copies-disabled'));

		if(value === 1) {
			wrapper.qs('.series-duplicate-copies-minus').classList.add('series-duplicate-copies-disabled');
		}

		if(value >= limit) {
			wrapper.qs('.series-duplicate-copies-plus').classList.add('series-duplicate-copies-disabled');
		}

		const list = form.qs('.series-duplicate-list');

		list.dataset.copies = value;

		if(increment === 1) {

			list.qsa('.series-duplicate-one', node => {

				const lastCopy = node.qs('.series-duplicate-copy:last-child');
				const newContent = lastCopy.outerHTML;

				lastCopy.insertAdjacentHTML('afterend', newContent);

				const newLastCopy = node.qs('.series-duplicate-copy:last-child');

				newLastCopy.dataset.wrapper = 'series-'+ node.dataset.series +'-'+ (value - 1);

				newLastCopy.qs('input[name^="name"]').dataset.copy = value;
				newLastCopy.qs('input[name^="name"]').name = 'name['+ node.dataset.series +']['+ (value - 1) +']';
				newLastCopy.qs('input[name^="name"]').value = node.dataset.name.replace('@copy', value);

				newLastCopy.qs('input[name^="taskInterval"]').name = 'taskInterval['+ node.dataset.series +']['+ (value - 1) +']';
				newLastCopy.qs('input[name^="taskInterval"]').value = '';

				newLastCopy.qs('.series-duplicate-copy-number').innerHTML = value;


			});

		} else if(increment === -1) {

			list.qsa('.series-duplicate-one', node => node.qs('.series-duplicate-copy:last-child').remove());

		}

	}

	static toggleDuplicateInterval(target) {

		const list = target.firstParent('.series-duplicate-list');

		if(list.dataset.interval === '0') {

			list.qsa('input[name^="interval"]', node => node.value = '');
			list.dataset.interval = '1';

			target.qs('svg').renderOuter(Lime.Asset.icon('chevron-contract'));

		} else {

			list.dataset.interval = '0';

			target.qs('svg').renderOuter(Lime.Asset.icon('chevron-expand'));

		}

	}

	static changeDuplicateSeason(target) {

		const toSeason = parseInt(target.value);

		const form = target.firstParent('form');
		const fromSeason = parseInt(form.dataset.season);

		const timesheetField = form.qs('[data-field="copyTimesheet"]');
		const timesheetWrapper = form.qs('[data-wrapper="copyTimesheet"]');
		const harvestField = form.qs('input[data-fqn="recolte"]');
		const harvestWrapper = harvestField?.firstParent('label');
		const seasonInfo = form.qs('.series-duplicate-season');

		if(toSeason !== fromSeason) {

			seasonInfo.classList.remove('hide');

			timesheetWrapper?.classList.add('hide');
			if(timesheetField) {
				timesheetField.qs('input[value="0"]').checked = true;
			}

			harvestWrapper?.classList.add('hide');
			if(harvestField) {
				harvestField.checked = false;
			}

		} else {

			seasonInfo.classList.add('hide');

			timesheetWrapper?.classList.remove('hide');
			harvestWrapper?.classList.remove('hide');

		}

	}

	static updateArea(target) {

		const form = target.firstParent('form');

		const use = form.qs('input[name="use"]:checked').value;
		const bedWidth = parseInt(form.qs('input[name="bedWidth"]').value) || null;
		const alleyWidth = parseInt(form.qs('input[name="alleyWidth"]').value) || 0;

		let area, length;

		switch(use) {

			case 'block' :
				area = parseInt(form.qs('input[name="areaTarget"]').value || null);
				length = null;
				break;

			case 'bed' :

				length = parseInt(form.qs('input[name="lengthTarget"]').value) || null;

				if(length === null || bedWidth === null) {
					area = null;
				} else {
					area = length * (bedWidth + alleyWidth) / 100;
				}

				break;

		}

		qsa('.cultivation-write', node => {

			node.dataset.area = area;
			node.dataset.length = length;

			Cultivation.updateDensity(node);

		})

	}

}