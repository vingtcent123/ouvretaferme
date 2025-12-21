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
	 * data-batch-behavior
	 * - muted : opacité à 0.25 si non disponible
	 * - hide : caché si non disponible
	 * - count : insérer un compteur de disponibilités
	 * - post : ajouter des ids en post (via data-ajax-body)
	 */
	static updateMenu(type, id, selection, callback) {

		if(type === 'group') {

			qs(id +' .batch-group-count').innerHTML = selection.length;

			qsa('[data-batch-active]', node => {

				const filter = selection.filter('[data-batch~="'+ node.dataset.batchActive +'"]');
				const count = filter.length;

				if(node.dataset.batchBehavior.includes('count')) {
					node.innerHTML = count;
				}

				if(count > 0) {
					node.classList.add('batch-active');
					node.classList.remove('batch-inactive');
				} else {
					node.classList.remove('batch-active');
					node.classList.add('batch-inactive');
				}

				if(node.dataset.batchBehavior.includes('post')) {

					let ids = [];

					filter.forEach(node => {
						ids[ids.length] = ['ids[]', node.value];
					});

					node.setAttribute('data-ajax-body', JSON.stringify(ids));

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