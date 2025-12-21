class Reconciliate {

	static updatePaymentMethod(target) {

		const suggestionId = target.dataset.suggestion;
		const paymentMethod = target.options[target.selectedIndex].value;

		new Ajax.Query()
			.url('/7/preaccounting/reconciliate:doUpdatePaymentMethod')
			.method('post')
			.body({
					id: suggestionId, paymentMethod
			})
			.fetch();
	}

	static updateSelection(targetTbody) {
		const checkbox = targetTbody.firstParent('tbody').qs('input[type="checkbox"]');

		checkbox.click();
	}
	static toggleGroupSelection(target) {

		const currentConfidence = target.dataset.confidence;

		CheckboxField.all(target.firstParent('table'), target.checked, 'input[type="checkbox"][data-confidence="' + currentConfidence + '"][name^="batch[]"]');

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

			qs(
				'.batch-menu-reconciliate',
				selection.filter('[data-batch~="not-reconciliate"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
						actions++;
					}
			);

			qs(
				'.batch-menu-ignore',
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
