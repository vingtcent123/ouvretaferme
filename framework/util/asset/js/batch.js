class Batch {

	static hideSelection(groupId) {

		qs(groupId).hide();

		qsa('[data-batch="'+ groupId +'"] [name="batch[]"]:checked, .batch-all', (field) => field.checked = false);

	}

	static changeSelection(groupId, oneId, callback = null) {

		const group = qs(groupId);
		const one = qs(oneId);
		const selection = qsa('[data-batch="'+ groupId +'"] [name="batch[]"]:checked');

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
					this.updateMenu('one', oneId, selection, callback);
					break;

				default :
					one.hide();
					group.removeHide();
					group.style.zIndex = Lime.getZIndex();
					this.updateMenu('group', groupId, selection, callback);
					break;

			}

		} else {

			if(selection.length === 0)  {
				group.hide();
			} else {
				group.removeHide();
				group.style.zIndex = Lime.getZIndex();
				this.updateMenu('group', groupId, selection, callback);
			}


		}

	}

	static updateMenu(type, id, selection, callback) {

		if(type === 'group') {
			qs(id +' .batch-group-count').innerHTML = selection.length;
		}

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa(id +' .batch-ids', node => node.innerHTML = newIds);

		if(callback !== null) {
			callback(selection);
		}

		if(type === 'group') {

			const list = qsa(id +' .batch-menu-main .batch-menu-item:not(.hide)', node => node.classList.remove('batch-menu-item-last'));

			if(list.length > 0) {
				list[list.length - 1].classList.add('batch-menu-item-last');
			}

		}
	}

}