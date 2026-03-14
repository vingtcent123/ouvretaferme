document.delegateEventListener('input', '#invoice-dates [name="date"]', function(e) {

	const inputDate = e.target;
	const inputDueDate = qs('#invoice-dates [name="dueDate"]');

	if(inputDate.value === '') {
		inputDueDate.value = '';
		qs('#invoice-dates').dataset.user = '0';
		return;
	}

	if(qs('#invoice-dates').dataset.user === '1') {
		return;
	}

	const dueDays = inputDueDate.dataset.dueDays === '' ? null : parseInt(inputDueDate.dataset.dueDays);
	const dueMonth = inputDueDate.dataset.dueMonth;

	const date = new Date(inputDate.value);

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

document.delegateEventListener('input', '#invoice-create-sales [name="sales[]"]', function(e) {

	Invoice.checkSales();

});

class Invoice {

	static checkSales() {

		const selectedSales = qsa('#invoice-create-sales [name="sales[]"]:checked');

		switch(selectedSales.length) {

			case 0 :
				this.acceptSales();
				break;

			case 1 :
				this.updateSales(selectedSales[0]);
				break;

		}

	}

	static acceptSales(target) {

		qsa('#invoice-create-sales [name="sales[]"]', (sale) => {

			const wrapper = sale.firstParent('tr');

			sale.disabled = false;
			wrapper.classList.remove('invoice-create-sales-disabled');

		});

	}

	static updateSales(selectedSale) {

		qsa('#invoice-create-sales [name="sales[]"]', (sale) => {

			if(sale === selectedSale) {
				return;
			}

			const wrapper = sale.firstParent('tr');
			const selectedWrapper = selectedSale.firstParent('tr');

			if(
				wrapper.dataset.vat !== selectedWrapper.dataset.vat ||
				wrapper.dataset.profile !== selectedWrapper.dataset.profile ||
				wrapper.dataset.month !== selectedWrapper.dataset.month ||
				wrapper.dataset.taxes !== selectedWrapper.dataset.taxes ||
				wrapper.dataset.paymentStatus !== selectedWrapper.dataset.paymentStatus ||
				(wrapper.dataset.paymentStatus !== 'not-paid' && wrapper.dataset.paymentStatus !== '') ||
				(selectedWrapper.dataset.paymentStatus !== 'not-paid' && selectedWrapper.dataset.paymentStatus !== '')
			) {

				sale.disabled = true;
				wrapper.classList.add('invoice-create-sales-disabled');

			} else {
				sale.disabled = false;
				wrapper.classList.remove('invoice-create-sales-disabled');
			}

		});

	}

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

		return Batch.changeSelection('#batch-invoice');

	}

}