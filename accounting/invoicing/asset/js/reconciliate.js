class Reconciliate {

	static updateSelection(targetTbody) {
		const checkbox = targetTbody.firstParent('tbody').qs('input[type="checkbox"]');

		checkbox.click();
	}
	static toggleGroupSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection(target) {

		return Batch.changeSelection('#batch-reconciliate', null, function(selection) {

			let ids = '';
			let idsList = [];
			let actions = 0;

			let amount = 0.0;

			selection.forEach(node => {

				amount += parseFloat(node.dataset.batchAmount);

				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			qs(
				'.batch-menu-item-number',
				node => {
					node.innerHTML = money(amount, 2);
				}
			);

			return actions;

		});

	}

}
