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
					selection[0].firstParent('.batch-checkbox').insertAdjacentElement('afterbegin', one);
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

	/**
	 * data-batch-contains
	 * - muted : opacité à 0.25 si non disponible
	 * - hide : caché si non disponible
	 * - count : insérer un compteur de disponibilités
	 * - post : ajouter des ids en post (via data-ajax-body)
	 */
	static updateMenu(type, id, selection, callback) {

		if(type === 'group') {

			qs(id +' .batch-group-count').innerHTML = selection.length;

			qsa('[data-batch-test]', node => {

				const count = selection.length;

				const selectionFiltered = selection.filter('[data-batch~="'+ node.dataset.batchTest +'"]');
				const countFitered = selectionFiltered.length;

				const contains = selectionFiltered.length > 0;
				const notContains = selectionFiltered.length === 0;
				const only = selectionFiltered.length === selection.length;
				const notOnly = selectionFiltered.length !== selection.length;

				const testContains = (value) => (contains && node.dataset.batchContains?.includes(value));
				const testNotContains = (value) => (notContains && node.dataset.batchNotContains?.includes(value));
				const testOnly = (value) => (only && node.dataset.batchOnly?.includes(value));
				const testNotOnly = (value) => (notOnly && node.dataset.batchNotOnly?.includes(value));

				const test = (value) => (testContains(value) || testNotContains(value) || testOnly(value) || testNotOnly(value));

				if(test('count')) {
					node.innerHTML = countFitered +' / '+ count;
				}

				if(countFitered > 0) {
					node.classList.add('batch-active');
					node.classList.remove('batch-inactive');
				} else {
					node.classList.remove('batch-active');
					node.classList.add('batch-inactive');
				}

				if(test('post') || test('get')) {

					let ids = [];

					selectionFiltered.forEach(node => {
						ids[ids.length] = ['ids[]', node.value];
					});

					if(test('post')) {
						node.setAttribute('data-ajax-body', JSON.stringify(ids));
					}

					if(test('get')) {
						node.setAttribute('data-ajax-query-string', JSON.stringify(ids));
					}

				}

				if(test('hide')) {
					node.classList.add('hide');
				} else {
					node.classList.remove('hide');
				}

				if(test('not-visible')) {
					node.classList.add('not-visible');
				} else {
					node.classList.remove('not-visible');
				}

				if(test('mute')) {
					node.classList.add('batch-mute');
				} else {
					node.classList.remove('batch-mute');
				}

			});

		}

		let newIds = '';
		selection.forEach((field) => newIds += '<input type="checkbox" name="ids[]" value="'+ field.value +'" checked/>');

		qsa(id +' .batch-ids', node => node.innerHTML = newIds);

		if(callback !== null) {
			callback(selection);
		}

		if(type === 'group') {

			const list = qsa(id +' .batch-main .batch-item:not(.hide)', node => node.classList.remove('batch-item-last'));

			if(list.length > 0) {
				list[list.length - 1].classList.add('batch-item-last');
			}

		}
	}

}