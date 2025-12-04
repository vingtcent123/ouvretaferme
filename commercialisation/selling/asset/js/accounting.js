class SellingAccounting {

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
	static toggle(step) {

		const isCollapsed = qs('[data-step-container="' + step + '"]').classList.contains('hide');

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

				new Ajax.Query()
					.url(url + '?from=' + from + '&to=' + to)
					.method('get')
					.fetch();

			}

			qs('div[data-step="' + step + '"]').removeHide();
			qs('div[data-step-container="' + step + '"]').removeHide();

		} else {

			qs('div[data-step="' + step + '"]').hide();
			qs('div[data-step-container="' + step + '"]').hide();

		}

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
