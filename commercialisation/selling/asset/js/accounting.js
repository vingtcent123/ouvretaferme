class SellingAccounting {

	static toggleExportDetails() {

		if(qs('[data-export-detail="content"]').classList.contains('hide')) {
			qs('[data-export-detail="content"]').removeHide();
			qs('[data-export-detail="less"]').removeHide();
			qs('[data-export-detail="more"]').hide();
		} else {
			qs('[data-export-detail="content"]').hide();
			qs('[data-export-detail="less"]').hide();
			qs('[data-export-detail="more"]').removeHide();
		}
	}

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

		const isCollapsed = qs('a[data-step="' + step + '"][data-toggle-item="collapsed"]').classList.contains('hide') === false;

		if(isCollapsed) {

			qs('a[data-toggle-item="collapsed"][data-step="' + step + '"]').hide();
			qs('a[data-toggle-item="expanded"][data-step="' + step + '"]').removeHide();

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

			const url = qs('a[data-toggle-item="collapsed"][data-step="' + step + '"]').dataset.url + '?from=' + from + '&to=' + to;

			new Ajax.Query()
				.url(url)
				.method('get')
				.fetch();

		} else {

			qs('a[data-toggle-item="collapsed"][data-step="' + step + '"]').removeHide();
			qs('a[data-toggle-item="expanded"][data-step="' + step + '"]').hide();

			qs('div[data-step="' + step + '"]').innerHTML = '';

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
