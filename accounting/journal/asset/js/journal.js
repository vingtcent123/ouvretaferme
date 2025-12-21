class Journal {

	static changeSelection(journalCode) {

		qsa('[data-journal-code="'+ journalCode +'"][data-operation-parent]', element => element.checked = false);

		let linkedOperations = 0;

		qsa('[data-journal-code="'+ journalCode +'"][data-operation][name="batch[]"]:checked', function(element) {
			const operationId = element.getAttribute('data-operation');
			qsa('[data-journal-code="'+ journalCode +'"][data-operation-parent="'+ operationId +'"]', element => element.checked = true);
			linkedOperations += qsa('[data-journal-code="'+ journalCode +'"][data-operation-parent="'+ operationId +'"]').length;

		});

		qs('#batch-title-detail')?.remove();

		if(linkedOperations > 0) {

			const textSelector = linkedOperations === 1 ? '[data-batch-title-more-singular]' : '[data-batch-title-more-plural]';

			qs('[data-batch-title-more-value]').innerHTML = linkedOperations;
			const span = document.createElement("span");
			span.setAttribute('id', 'batch-title-detail');
			span.innerHTML = qs(textSelector).innerHTML;

			qs('#batch-journal .batch-group-count').after(span);

		} else {

			qs('#batch-title-detail')?.remove();

		}

		return Batch.changeSelection('#batch-journal', null, function(selection) {

			let actions = 0;

			let amountCredit = 0.0;
			let amountDebit = 0.0;
			let ids = '';

			selection.forEach(node => {

				if(node.dataset.batchType === 'credit') {
					amountCredit += parseFloat(node.dataset.batchAmount);
				} else {
					amountDebit += parseFloat(node.dataset.batchAmount);
				}

				ids += '&ids[]='+ node.value;

			});

			qs(
				'.batch-item-number-debit',
				node => {
					node.innerHTML = money(amountDebit , 2);

					const link = node.firstParent('.batch-amount');
					link.setAttribute('href', link.dataset.url + ids);
				}
			);
			qs(
				'.batch-item-number-credit',
				node => {
					node.innerHTML = money(amountCredit , 2);

					const link = node.firstParent('.batch-amount');
					link.setAttribute('href', link.dataset.url + ids);
				}
			);

			qs(
				'.batch-payment-method',
				selection.filter('[data-batch~="not-update-payment"]').length > 0 ?
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
