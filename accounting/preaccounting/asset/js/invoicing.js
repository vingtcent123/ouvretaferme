class Invoicing {

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

		return Batch.changeSelection('#batch-invoice', null, function(selection) {

			let ids = '';
			let idsList = [];
			let actions = 0;

			let taxes = null;
			let amountIncluding = 0.0;
			let amountExcluding = 0.0;

			selection.forEach(node => {

				amountIncluding += parseFloat(node.dataset.batchAmountIncluding);
				amountExcluding += parseFloat(node.dataset.batchAmountExcluding);

				if(node.dataset.batchTaxes !== '') {

					if(taxes === null) {
						taxes = node.dataset.batchTaxes;
					} else if(taxes !== node.dataset.batchTaxes) {
						taxes = 'excluding';
					}

				}

				ids += '&ids[]='+ node.value;
				idsList[idsList.length] = node.value;

			});

			const amount = (taxes === 'excluding') ? amountExcluding : amountIncluding;

			qs(
				'.batch-item-number',
				node => {
					node.innerHTML = money(amount, 2);
				}
			);

			qs(
				'.batch-import',
				selection.filter('[data-batch~="not-import"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-ignore',
				selection.filter('[data-batch~="not-ignore"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			return actions;

		});

	}

}
