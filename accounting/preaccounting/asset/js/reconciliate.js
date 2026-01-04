class Reconciliate {

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

			let amount = 0.0;

			selection.forEach(node => {
				amount += parseFloat(node.dataset.batchAmount);
			});

			qs(
				'.batch-item-number',
				node => {
					node.innerHTML = money(amount, 2);
				}
			);

		});

	}

}
