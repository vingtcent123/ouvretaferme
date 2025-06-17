class Batch {

	static hideSelection() {

		qs('#batch-group').hide();

		qsa('[name="batch[]"]:checked, .batch-all', (field) => field.checked = false);

	}

	static changeSelection(callback) {

		const group = qs('#batch-group');
		const one = qs('#batch-one');
		const selection = qsa('[name="batch[]"]:checked');

		if(one !== null) {

			switch(selection.length) {

				case 0 :
					one.hide();
					group.hide();
					break;

				case 1 :
					one.removeHide();
					selection[0].firstParent('.batch-item').insertAdjacentElement('afterbegin', one);
					group.hide();
					this.updateMenu(selection, callback);
					break;

				default :
					one.hide();
					group.removeHide();
					group.style.zIndex = Lime.getZIndex();
					this.updateMenu(selection, callback);
					break;

			}

		} else {

			if(selection.length === 0)  {
				group.hide();
			} else {
				group.removeHide();
				group.style.zIndex = Lime.getZIndex();
				this.updateMenu(selection, callback);
			}


		}

	}

	static updateMenu(selection, callback) {

		qs('#batch-group-count').innerHTML = selection.length;

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa('.batch-ids', node => node.innerHTML = newIds);

		let actions = callback(selection);

		const list = qsa('.batch-menu-main .batch-menu-item:not(.hide)', node => node.classList.remove('batch-menu-item-last'));

		if(list.length > 0) {
			list[list.length - 1].classList.add('batch-menu-item-last');
		}
	}

}