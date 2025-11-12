class ShopProduct {

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection() {

		return Batch.changeSelection('#batch-catalog', null, (selection) => {

			let idsCollection = '';

			selection.forEach(node => {
				idsCollection += '&products[]='+ node.value;
			});

			qsa(
				'.batch-menu-relation',
				selection.filter('[data-batch~="not-relation"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						node.setAttribute('href', node.dataset.url + idsCollection);
					}
			);

		});

	}

}
