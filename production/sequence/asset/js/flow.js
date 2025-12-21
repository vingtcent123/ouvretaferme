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

	new Ajax.Query(this)
		.url('/sequence/flow:getFields')
		.body({
			farm: e.target.dataset.farm,
			action: this.value
		})
		.fetch()
		.then((json) => {

			form.ref('tools', wrapper => wrapper.renderInner(json.tools));
			form.ref('methods', wrapper => wrapper.renderInner(json.methods));

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

	static changeWeekSelection(selector) {

		const wrapper = selector.parentElement;

		wrapper.dataset.checked = (wrapper.dataset.checked === '0') ? '1' : '0';

		qsa('#flow-wrapper [name="batch[]"][data-week="'+ wrapper.dataset.week +'"]', node => node.checked = (wrapper.dataset.checked === '1'));

		this.changeSelection();

	}

	static changeSelection() {

		return Batch.changeSelection('#batch-flow', '#batch-flow-one',function(selection) {

		if(selection.length > 1) {
			qsa('.batch-update', node => node.hide());
		} else {
			qsa('.batch-update', (node) => {
				node.setAttribute('href', '/sequence/flow:update?id='+ selection[0].value);
				node.removeHide();
			});
		}

		});

	}


}