document.delegateEventListener('input', '#invoice-dates [name="date"]', function(e) {

	const inputDate = e.target;
	const inputDueDate = qs('#invoice-dates [name="dueDate"]');

	if(e.target.value === '') {
		inputDueDate.value = '';
		qs('#invoice-dates').dataset.user = '0';
		return;
	}

	if(qs('#invoice-dates').dataset.user === '1') {
		return;
	}

	const dueDays = inputDueDate.dataset.dueDays === '' ? null : parseInt(inputDueDate.dataset.dueDays);
	const dueMonth = inputDueDate.dataset.dueMonth;

	const date = new Date(e.target.value);

	if(dueDays !== null) {
		date.setDate(date.getDate() + dueDays);
	}

	if(dueMonth === '1') {
		date.setMonth(date.getMonth() + 1);
		date.setDate(0);
	}

	inputDueDate.value = date.toISOString().split('T')[0];


});

document.delegateEventListener('input', '#invoice-dates [name="dueDate"]', function(e) {

	qs('#invoice-dates').dataset.user = '1';

});

class Invoice {

	static customize(target) {

		target.classList.add('hide');
		qs('#invoice-customize').classList.remove('hide');

	}

	static toggleSelection(target) {

		CheckboxField.all(target.firstParent('table'), target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static toggleDaySelection(target) {

		CheckboxField.all(target.firstParent('tbody').nextSibling, target.checked, '[name^="batch[]"]');

		this.changeSelection(target);

	}

	static changeSelection() {

		return Batch.changeSelection('#batch-invoice', null, function(selection) {

			qsa(
				'.batch-menu-send',
				selection.filter('[data-batch~="not-sent"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
					}
			);

			return 1;

		});

	}

	static changePaymentMethod(paymentMethodElement) {
		qs('[data-wrapper="paymentStatus"]').display(paymentMethodElement.value !== '');
	}

}