class Preaccounting {

	static export() {

		if(qs('[data-accounting-action="export"]').classList.contains('btn-warning')) {
			return false;
		}

		const url = qs('[data-accounting-action="export"]') + '&from=' + qs('[name="from"]').value + '&to=' + qs('[name="to"]').value;

		new Ajax.Query()
			.url(url)
			.method('get')
			.fetch();
	}
	static toggle(step, tab) {

		const isSuccess = qs('a[data-step="' + step + '"]').classList.contains('success');
		if(isSuccess && step !== 'export') {
			return;
		}

		const isCollapsed = qs('div[data-step="' + step + '"]').classList.contains('hide');

		qsa('div[data-step]', node => node.hide());
		qsa('a.step', node => node.classList.remove('selected'));

		if(isCollapsed) {

			const url = qs('a[data-url][data-step="' + step + '"]')?.dataset?.url ?? null;

			if(url) {

				const from = qs('#form-search input[name="from"]').value;
				const to = qs('#form-search input[name="to"]').value;

				if(!from) {
					qs('#form-search input[name="from"]').classList.add('form-error-field');
				} else {
					qs('#form-search input[name="from"]').classList.remove('form-error-field');
				}

				if(!to) {
					qs('#form-search input[name="to"]').classList.add('form-error-field');
				} else {
					qs('#form-search input[name="to"]').classList.remove('form-error-field');
				}

				const currentUrl = window.location.origin + window.location.pathname + '?type=' + step;
				Lime.History.replaceState(currentUrl);

				new Ajax.Query()
					.url(url + '?from=' + from + '&to=' + to)
					.method('get')
					.fetch()
					.then(() => Preaccounting.load(step, tab));

			}

			qs('div[data-step="' + step + '"]').removeHide();
			qs('a.step[data-step="' + step + '"]').classList.add('selected');

		}

	}

	static load(step, tab) {

		if(!tab) {
			return;
		}

		const target = qs('[data-step="' +  step + '"][data-tab="' + tab + '"]');
		const url = target.dataset.url;

		const currentUrl = window.location.origin + window.location.pathname + '?type=' + step + '&tab=' + tab;
		Lime.History.replaceState(currentUrl);

		new Ajax.Query()
			.url(url)
			.method('get')
			.fetch();

	}

	static toggleGroupSelection(target) {

		CheckboxField.all(target.firstParent('tbody, thead').nextSibling, target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection(target) {

		const type = target.getAttribute('batch-type');

		return Batch.changeSelection('#batch-accounting-' + type, null, function(selection) {

			let ids = '';
			let idsList = [];

			selection.forEach(node => {


				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			return 1;

		});

	}

}
