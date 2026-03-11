class FinancialYear {

	static checkLegalCategory(target, legalCategoryFarm) {

		if(parseInt(target.value) !==  parseInt(legalCategoryFarm)) {
			target.firstParent('.form-control-field').qs('.form-info ').removeHide();
		} else {
			target.firstParent('.form-control-field').qs('.form-info ').hide();
		}

	}

	static changeLegalCategory(target) {

		const category = parseInt(target.value);

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="associates"], [data-wrapper="gaecFormat"]', wrapper => (category === 6533) ? // Pour les GAEC uniquement
			wrapper.removeHide() :
			wrapper.hide());

	}

	static changeTaxSystem(target) {

		const taxSystem = qs('[name="taxSystem"]:checked').value;

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="accountingMode"]', wrapper => (taxSystem === 'micro-ba') ?
			wrapper.removeHide() :
			wrapper.hide()
		);

		form.qsa('[data-wrapper="accountingType"]', wrapper => (taxSystem === 'micro-ba') ?
			wrapper.removeHide() :
			wrapper.hide()
		);

	}

	static changeAccountingMode(target) {

		const accountingMode = qs('[name="accountingMode"]:checked').value;

		const form = target.firstParent('form');

		form.qsa('[data-wrapper="accountingType"]', wrapper => (accountingMode === 'cash-receipts') ?
			wrapper.hide() :
			wrapper.removeHide()
		);

	}

}
