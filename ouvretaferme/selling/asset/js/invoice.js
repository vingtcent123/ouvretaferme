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

		const menu = qs('#batch-group');
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

		qs('#batch-group').hide();

		qsa('[name="batch[]"]:checked, .batch-all', (field) => field.checked = false);

	}

	static updateBatchMenu(selection) {

		qs('#batch-menu-count').innerHTML = selection.length;

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa('.batch-ids', node => node.innerHTML = newIds);

		qsa(
			'.batch-menu-send',
			selection.filter('[data-batch~="not-sent"]').length > 0 ?
				node => node.hide() :
				node => {
					node.removeHide();
				}
		);

		const list = qsa('.batch-menu-main .batch-menu-item:not(.hide)', node => node.classList.remove('batch-menu-item-last'));

		if(list.length > 0) {
			list[list.length - 1].classList.add('batch-menu-item-last');
		}

	}

}