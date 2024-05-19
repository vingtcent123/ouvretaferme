document.delegateEventListener('click', 'a[data-action="flow-period-interval"]', function(e) {

	e.preventDefault();

	qs('#flow-period-only').style.display = 'none';
	qs('#flow-period-interval').style.display = 'block';

	qs('#flow-period').value = 'interval';

	qs('#flow-period-interval [name="weekStart"]').value = qs('#flow-period-only [name="weekOnly"]').value;
	qs('#flow-period-interval [name="yearStart"]').value = qs('#flow-period-only [name="yearOnly"]').value;

	qs('#flow-period-interval [name="weekStop"]').value = '';
	qs('#flow-period-interval [name="yearStop"]').value = qs('#flow-period-only [name="yearOnly"]').value;

	qs('#flow-period-interval [name="frequency"]').value = 'w1';

	qs('#flow-period-only [name="weekOnly"]').value = '';

	DateField.updateWeeks(qs('#flow-period-interval [name="weekStart"]'));

});

document.delegateEventListener('click', 'a[data-action="flow-period-only"]', function(e) {

	e.preventDefault();

	qs('#flow-period-only').style.display = 'block';
	qs('#flow-period-interval').style.display = 'none';

	qs('#flow-period').value = 'only';

	qs('#flow-period-only [name="weekOnly"]').value = qs('#flow-period-interval [name="weekStart"]').value;
	qs('#flow-period-only [name="yearOnly"]').value = qs('#flow-period-interval [name="yearStart"]').value;

	qs('#flow-period-interval [name="weekStart"], #flow-period-interval [name="weekStop"]').value = '';

	DateField.updateWeeks(qs('#flow-period-only [name="weekOnly"]'));

});

document.delegateEventListener('click', 'a[data-action="flow-season-interval"]', function(e) {

	e.preventDefault();

	qs('#flow-season-only').style.display = 'none';
	qs('#flow-season-interval').style.display = 'block';

	qs('#flow-season').value = 'interval';

	qs('#flow-season-interval [name="seasonStart"]').value = qs('#flow-season-only [name="seasonOnly"]').value;
	qs('#flow-season-interval [name="seasonStop"]').value = '';

	qs('#flow-season-only [name="seasonOnly"]').value = '';

});

document.delegateEventListener('click', 'a[data-action="flow-season-only"]', function(e) {

	e.preventDefault();

	qs('#flow-season-only').style.display = 'block';
	qs('#flow-season-interval').style.display = 'none';

	qs('#flow-season').value = 'only';

	qs('#flow-season-only [name="seasonOnly"]').value = qs('#flow-season-interval [name="seasonStart"]').value;

	qs('#flow-season-interval [name="seasonStart"], #flow-season-interval [name="seasonStop"]').value = '';

});

document.delegateEventListener('click', 'input[data-action="flow-write-action-change"]', function(e) {

	const form = this.firstParent('form');

	if(this.dataset.fqn === 'fertilisation') {
		form.qs('[data-wrapper="fertilizer"]', node => node.classList.remove('hide'))
	} else {
		form.qs('[data-wrapper="fertilizer"]', node => node.classList.add('hide'))
	}

	this.firstParent('form').ref('tools', wrapper => {

		new Ajax.Query(this)
			.url('/production/flow:getToolsField')
			.body({
				farm: wrapper.dataset.farm,
				action: this.value
			})
			.fetch()
			.then((json) => {

				wrapper.renderInner(json.field);

			});

	});

});

class Flow {

	static createSelectCultivation(target) {

		const form = target.firstParent('form');
		const harvest = form.qs('[name="action"][data-fqn="recolte"]')

		if(target.value) {
			harvest.disabled = false;
		} else {
			harvest.disabled = true;
			harvest.checked = false;
		}

	}

	static changeAllSelection(selector) {

		qsa('#flow-wrapper [name="batch[]"]', node => node.checked = selector.checked);

		this.changeSelection();

	}

	static changeSelection() {

		const menu = qs('#batch-several');
		const one = qs('#batch-one');
		const selection = qsa('[name="batch[]"]:checked');

		switch(selection.length) {

			case 0 :
				one.hide();
				menu.hide();
				break;

			case 1 :
				one.removeHide();
				selection[0].firstParent('.batch-item').insertAdjacentElement('afterbegin', one);
				menu.hide();
				return this.updateBatchMenu(selection);

			default :
				one.hide();
				menu.removeHide();
				menu.style.zIndex = Lime.getZIndex();
				return this.updateBatchMenu(selection);

		}

	}

	static updateBatchMenu(selection) {

		qs('#batch-menu-count').innerHTML = selection.length;

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa('.batch-ids', node => node.innerHTML = newIds);

		if(selection.length > 1) {
			qsa('.batch-menu-update', node => node.hide());
		} else {
			qsa('.batch-menu-update', (node) => {
				node.setAttribute('href', '/production/flow:update?id='+ selection[0].value);
				node.removeHide();
			});
		}

	}

	static hideSelection() {

		qs('#batch-several').hide();

		qsa('[name="batch[]"]:checked, #batch-all', (field) => field.checked = false);

	}

}