class Invoice {

	static toggleSelection(target) {

		CheckboxField.all(target, '[name^="batch[]"]', undefined, 'table');

		this.changeSelection(target);

	}

	static toggleDaySelection(target) {

		CheckboxField.all(target, '[name^="batch[]"]', undefined, 'tbody');

		this.changeSelection(target);

	}

	static changeSelection() {

		const menu = qs('#batch-several');
		const selection = qsa('[name="batch[]"]:checked');

		if(selection.length === 0)  {
			menu.hide();
		} else {
			menu.removeHide();
			menu.style.zIndex = Lime.getZIndex();
			this.updateBatchMenu(selection);
		}

	}

	static hideSelection() {

		qs('#batch-several').hide();

		qsa('[name="batch[]"]:checked, .batch-all', (field) => field.checked = false);

	}

	static updateBatchMenu(selection) {

		qs('#batch-menu-count').innerHTML = selection.length;

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa('.batch-ids', node => node.innerHTML = newIds);

		let actions = 0;

		qsa(
			'.batch-menu-cancel',
			selection.filter('[data-batch~="not-canceled"]').length > 0 ?
				node => node.hide() :
				node => {
					node.removeHide();
					actions++;
				}
		);

		qsa(
			'.batch-menu-confirmed',
			selection.filter('[data-batch~="not-confirmed"]').length > 0 ?
				node => node.hide() :
				node => {
					node.removeHide();
					actions++;
				}
		);

		qsa(
			'.batch-menu-delivered',
			selection.filter('[data-batch~="not-delivered"]').length > 0 ?
				node => node.hide() :
				node => {
					node.removeHide();
					actions++;
				}
		);

		qsa(
			'.batch-menu-delete',
			selection.filter('[data-batch~="not-delete"]').length > 0 ?
				node => node.hide() :
				node => {
					node.removeHide();
					actions++;
				}
		);

		qsa(
			'.batch-menu-empty',
			(actions > 0) ?
				node => node.hide() :
				node => node.removeHide()
		)

	}

}