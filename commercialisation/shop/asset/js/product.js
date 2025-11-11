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

			qs(
				'.batch-menu-relation',
				node => {

					node.removeHide();
					node.setAttribute('href', node.dataset.url + idsCollection);

				}
			);

		});

	}

}
