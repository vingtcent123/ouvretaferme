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

		return Batch.changeSelection(function(selection) {

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