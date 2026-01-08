document.delegateEventListener('panelClose', '#panel-operation-view', function() {

	if(qs('body').dataset.touch === 'yes') {
		return;
	}

	qs('body').classList.remove('operation-view-panel-open');
});

document.delegateEventListener('panelAfterShow', '#panel-operation-view', function() {

	if(qs('body').dataset.touch === 'yes') {
		return;
	}

	qs('body').classList.add('operation-view-panel-open');
	const style =  document.createElement('style');
	style.id = 'series-selector-style';
	style.innerHTML = ':root { --nav-width: 25rem; }';

	qs('#panel-operation-view').append(style);
})

document.delegateEventListener('focus', '.operation-create [name^="amount"], .operation-create [name^="vat"]', function() {
	this.select();
});

document.delegateEventListener('autocompleteBeforeQuery', '[data-third-party="bank-cashflow-allocate"]', function(e) {
	if(e.detail.input.firstParent('form').qs('[name="id"]') === null) {
		return;
	}
	const cashflowId = e.detail.input.firstParent('form').qs('[name="id"]').value;
	e.detail.body.append('cashflowId', cashflowId);
});


document.delegateEventListener('autocompleteBeforeQuery', '[data-description="journal-operation-create"], [data-description="journal-operation-update"], [data-description="bank-cashflow-allocate"]', function(e) {

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]') === null) {
		return;
	}

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="accountLabel["]') === null) {
		return;
	}

	const thirdParty = e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]').getAttribute('value');
	const accountLabel = e.detail.input.firstParent('div.operation-create').qs('[name^="accountLabel["]').value;
	e.detail.body.append('thirdParty', thirdParty);
	e.detail.body.append('accountLabel', accountLabel);
});

document.delegateEventListener('autocompleteBeforeQuery', '[data-account="journal-operation-create"], [data-account="bank-cashflow-allocate"]', function(e) {
	if(e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]') !== null) {
		const thirdParty = e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]').getAttribute('value');
		e.detail.body.append('thirdParty', thirdParty);
	}
	Array.from(qsa('div.operation-create [name^="account["]')).map(element => e.detail.body.append('accountAlready[]', element.value ? parseInt(element.value) : ''));
});

document.delegateEventListener('autocompleteBeforeQuery', '[data-account-label="journal-operation-create"], [data-account-label="journal-operation-update"], [data-account-label="bank-cashflow-allocate"]', function(e) {

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]') !== null) {
		const thirdParty = e.detail.input.firstParent('div.operation-create').qs('[name^="thirdParty["]').getAttribute('value');
		e.detail.body.append('thirdParty', thirdParty);
	}

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="account["]') !== null) {
		const account = e.detail.input.firstParent('div.operation-create').qs('[name^="account["]').getAttribute('value');
		e.detail.body.append('account', account);
	}

});

document.delegateEventListener('autocompleteBeforeQuery', '[data-asset="journal-operation-create"], [data-asset="journal-operation-update"], [data-asset="bank-cashflow-allocate"]', function(e) {

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="accountLabel["]') !== null) {
		const accountLabel = e.detail.input.firstParent('div.operation-create').qs('[name^="accountLabel["]').getAttribute('value');
		e.detail.body.append('accountLabel', accountLabel);
	}

	if(e.detail.input.firstParent('div.operation-create').qs('[name^="account["]') !== null) {
		const account = e.detail.input.firstParent('div.operation-create').qs('[name^="account["]').getAttribute('value');
		e.detail.body.append('account', account);
	}

});

document.delegateEventListener('autocompleteSource', '[data-account-label="journal-operation-create"], [data-account-label="journal-operation-update"], [data-account-label="bank-cashflow-allocate"]', function(e) {

	if(e.detail.results.length === 1 && e.target.value.length === 0) {
		const index = e.detail.input.getAttribute('data-index');
		const inputElement = qs('input[data-wrapper="accountLabel['+ index +']"]');
		AutocompleteField.apply(inputElement, e.detail.results[0]);
	}

});

document.delegateEventListener('autocompleteSelect', '[data-account="journal-operation-create"], [data-account="journal-operation-update"], [data-account="bank-cashflow-allocate"]', function(e) {

	const index = e.detail.input.getAttribute('data-index');

	if(e.detail.value.length !== 0) {

		if(e.detail.journalCode) {

			qs('[data-journal-code="journal-code-info"][data-index="' + index + '"]').hide();

			const actualJournalCode = qs('[name="journalCode[' + index + ']"]').value;

			if(actualJournalCode && parseInt(e.detail.journalCode) !== parseInt(actualJournalCode)) {

				const journalName = qs('[name="journalCode[' + index + ']"] option[value="'+ e.detail.journalCode +'"]').text;
				qs('[data-journalCode="journal-name"][data-index="' + index + '"]').innerHTML = journalName;
				qs('[data-journal-code="journal-code-info"][data-index="' + index + '"]').removeHide();
				qs('[data-journal-code="journal-code-info"][data-index="' + index + '"]').dataset.journalSuggested = e.detail.journalCode;

			} else {

				qs('[name="journalCode[' + index + ']"]').value = e.detail.journalCode;

			}
		}
		Operation.updateType(e.detail);

		if(Operation.hasVat()) {

			if(e.detail.vatClass) {

				qs('[data-index="' + index + '"][data-field="vatRate"]').removeAttribute('disabled');
				qs('[data-index="' + index + '"][data-field="vatValue"]').removeAttribute('disabled');

			} else {

				const targetVatValue = qs('[name="vatValue[' + index + ']"');
				CalculationField.setValue(targetVatValue, 0);
				qs('[data-index="' + index + '"][data-field="vatRate"]').value = 0;
				qs('[data-index="' + index + '"][data-field="vatRate"]').setAttribute('disabled', 'disabled');
				qs('[data-index="' + index + '"][data-field="vatValue"]').setAttribute('disabled', 'disabled');

			}

			qs('[data-index="' + index + '"][data-field="vatRate"]').setAttribute('data-vat-rate-recommended', e.detail.vatRate || 0);
			qs('[data-index="' + index + '"][data-field="vatRate"]').setAttribute('data-vat-class-chosen', e.detail.vatClass || 0);

			Operation.updateVAT(index, e.detail);
			
			OperationAmount.calculateFromVatRateAuto(index);


		}

		qs('[data-account-label="' + this.dataset.account + '"][data-index="' + index + '"]').focus();

	} else {

		if(Operation.hasVat()) {
			Operation.resetVat(index);
		}

		Operation.resetAccountLabel(index);
		Operation.resetJournalCode(index);

	}

	// Vérifie si on doit créer une immobilisation

	const accountClass = e.detail.class;
	const isGrant = accountClass?.startsWith('13');
	const isAsset = accountClass?.startsWith('2');

	if(isAsset || isGrant) {

		qs('.operation-create-several-container').setAttribute('data-asset', '');
		qs('[data-wrapper="asset[' + index + ']"] [data-asset-container]').removeHide();

	} else {

		qs('[data-wrapper="asset[' + index + ']"] [data-asset-container]').hide();
		if(qsa('[data-wrapper^="asset"] [data-asset-container]:not(.hide)').length === 0) {
			qs('.operation-create-several-container').removeAttribute('data-asset');
		}

	}

	Operation.checkAutocompleteStatus(e);

	OperationAmount.checkAmounts(index);

});

document.delegateEventListener('autocompleteUpdate', '[data-third-party="journal-operation-create"], [data-third-party="journal-operation-update"], [data-third-party="bank-cashflow-allocate"]', function(e) {
	Operation.checkAutocompleteStatus(e);
});

document.delegateEventListener('autocompleteSelect', '[data-third-party="journal-operation-create"], [data-third-party="journal-operation-update"], [data-third-party="bank-cashflow-allocate"]', function(e) {

	const index = parseInt(this.dataset.index);

	if(this.disabled) {
		return;
	}

	Operation.updateThirdParty(index, e.detail);
	Operation.checkAutocompleteStatus(e);
});

document.delegateEventListener('mouseover', '[data-highlight]', function(e) {
	const highlight = e.delegateTarget.dataset.highlight;
	Operation.highlight(highlight);
});
document.delegateEventListener('mouseout', '[data-highlight]', function(e) {
	const highlight = e.delegateTarget.dataset.highlight;
	Operation.unhighlight(highlight);
});

document.delegateEventListener('change', '[data-date="journal-operation-create"][data-accounting-type="cash"]', function(e) {
	Operation.copyDate(e);
});

document.delegateEventListener('change', '[data-field="amount"]', function() {

	const index = this.dataset.index;

	OperationAmount.calculateFromAmount(index);
	OperationAmount.checkAmounts(index);

});

document.delegateEventListener('change', '[data-field="amountIncludingVAT"]', function() {

	const index = this.dataset.index;

	OperationAmount.calculateFromAmountIncludingVAT(index);
	OperationAmount.checkAmounts(index);

});

document.delegateEventListener('change', '[data-field="vatRate"]', function() {

	const index = this.dataset.index;

	OperationAmount.calculateFromVatRate(index);
	OperationAmount.checkAmounts(index);


});

document.delegateEventListener('change', '[data-field="vatValue"]', function() {

	const index = this.dataset.index;

	OperationAmount.checkAmounts(index);

});

document.delegateEventListener('change', '[data-field="type"]', function () {

	const index = this.dataset.index;

	OperationAmount.checkAmounts(index);

});

document.delegateEventListener('change', '[data-field="document"]', function(e) {

	const value = e.delegateTarget.value;
	e.delegateTarget.value = value.toFqn();

});


class Operation {

	static toggleCorrect(index) {

		const isChecked = qs('[data-check-amount="0"][data-index="' + index + '"]').isHidden();

		if(isChecked) {

			qs('[data-check-amount="0"][data-index="' + index + '"]').removeHide();
			qs('[data-check-amount="1"][data-index="' + index + '"]').hide();

		} else {

			qs('[data-check-amount="0"][data-index="' + index + '"]').hide();
			qs('[data-check-amount="1"][data-index="' + index + '"]').removeHide();

		}

	}

	static open(id) {
		qs('a[data-view-operation="' + id + '"]').click();
	}

	static hasVat() {
		return parseBool(qs('form[data-has-vat]').dataset.hasVat);
	}

	static highlight(selector) {
		selector.indexOf('linked') > 0
			? qs('[name-linked="' + selector + '"]').classList.add('row-highlight')
			: qs('[name="' + selector + '"]').classList.add('row-highlight');
	}

	static unhighlight(selector) {
		selector.indexOf('linked') > 0
			? qs('[name-linked="' + selector + '"]').classList.remove('row-highlight')
			: qs('[name="' + selector + '"]').classList.remove('row-highlight');
	}

	static applyJournal(index) {

		const journalCodeId = qs('[data-journal-code="journal-code-info"][data-index="' + index + '"]').dataset.journalSuggested;
		qs('[name="journalCode[' + index + ']"]').value = journalCodeId;
		qs('[data-journal-code="journal-code-info"][data-index="' + index + '"]').hide();
	}

	static preFillNewOperation(index) {

		qs('[name="date[' + index + ']"]').setAttribute('value', qs('[name="date[' + (index - 1) + ']"]').value)
		qs('[name="document[' + index + ']"]').setAttribute('value', qs('[name="document[' + (index - 1) + ']"]').value)
		qs('[name="description[' + index + ']"]').setAttribute('value', qs('[name="description[' + (index - 1) + ']"]').value)

		if(qs('[name="paymentMethod[' + (index - 1) + ']"]') && qs('[name="paymentMethod[' + (index - 1) + ']"]').value) {
			qs('[name="paymentMethod[' + index + ']"]').value = qs('[name="paymentMethod[' + (index - 1) + ']"]').value;
		}

		if(qs('[name="paymentDate[' + index + ']"]') && qs('[name="paymentDate[' + (index - 1) + ']"]')) {
			qs('[name="paymentDate[' + index + ']"]').setAttribute('value', qs('[name="paymentDate[' + (index - 1) + ']"]').value)
		}

		if(qs('[name="thirdParty[' + index + ']"]') && qs('[name="thirdParty[' + (index - 1) + ']"]')) {
			qs('[name="thirdParty[' + index + ']"]').setAttribute('value', qs('[name="thirdParty[' + (index - 1) + ']"]').value || null)
		}

	}

	static resetJournalCode(index) {
		qs('[data-field="journalCode"][data-index="' + index + '"]').value = '';
	}

	static resetAccountLabel(index) {

		const element = qs('[data-wrapper="accountLabel['+ index +']"] a[class="autocomplete-empty"]');
		AutocompleteField.empty(element);

	}

	static resetVat(index) {

		qs('[data-wrapper="vatRate['+ index +']"] input').value = '';

	}

	static checkAutocompleteStatus(e) {

		const field = e.delegateTarget.dataset.autocompleteField;
		qs('[data-wrapper="' + field + '"]', node => node.classList.remove('form-error-wrapper'));
		if(e.detail.value === undefined) {
			qs('[data-wrapper="' + field + '"]', node => node.classList.add('form-error-wrapper'));
		}
	}

	static updateThirdParty(index, detail) {

		const columns = qs('[data-columns]').getAttribute('data-columns');
		detail.input.firstParent('form').qs('#add-operation').setAttribute('post-third-party', detail.value);

		for(let i = 0; i < columns; i++) {
			if(i !== index && qs('[data-third-party][data-index="' + i + '"]').getAttribute('disabled') === '1') {
				const dropdown = qs('[data-third-party][data-index="' + i + '"]');
				AutocompleteField.apply(dropdown, detail);
			}
		}

	}

	static deleteOperation(target) {

		target.firstParent('.operation-create').remove();
		const index = Number(qs('#add-operation').getAttribute('post-index'));
		qs('#add-operation').setAttribute('post-index', index - 1);
		qs('#add-operation').classList.remove('not-visible');

		Operation.showOrHideDeleteOperation();
		Operation.updateSingularPluralText();

	}

	// Seulement pour la création
	static showOrHideDeleteOperation() {

		const operations = qsa('#operation-create-list .operation-create:not(.operation-create-headers):not(.operation-create-validation)').length;

		if(operations > 1) {
			qsa('[data-operation-delete]', node => (Number(node.getAttribute('data-index')) === operations - 1) ? node.classList.remove('hide') : node.classList.add('hide'));
		}

		qs('#operation-create-list').setAttribute('data-columns', operations);

		Operation.updateSingularPluralText();

	}

	// Seulement pour la création
	static updateSingularPluralText() {

		const operations = qsa('#operation-create-list .operation-create:not(.operation-create-headers)').length;

		qs('button[type="submit"]').innerHTML = qs('button[type="submit"]').getAttribute(operations > 1 ? 'data-text-plural' : 'data-text-singular');

		if(qs('#panel-journal-operation-create-title')) {
			qs('#panel-journal-operation-create-title').innerHTML = qs('#panel-journal-operation-create-title').getAttribute(operations > 1 ? 'data-text-plural' : 'data-text-singular');
		}

	}

	static updateType(accountDetail) {

		const index = accountDetail.input.getAttribute('data-index');

		if(qs('[name="type[' + index + ']"]:checked') !== null) {
			return;
		}
		const classValue = parseInt(accountDetail.itemText.substring(0, 1));
		const value = [2, 4, 6].includes(classValue) ? 'debit' : 'credit';
		qs('[name="type[' + index + ']"][value="' + value + '"]').setAttribute('checked', true);

	}

	static updateVAT(index, accountDetail) {

		// Non soumis à la TVA
		if(Operation.hasVat() === false) {
			return;
		}

		qs('[name="vatRate[' + index + ']"]').value = accountDetail.vatRate;

	}

	static copyDate(e) {

		if(e.delegateTarget.value === null) {
			return;
		}

		const index = e.delegateTarget.getAttribute('data-index');
		const paymentDateElement = e.delegateTarget.firstParent('div.operation-create').qs('[name="paymentDate[' + index + ']"]');

		paymentDateElement.setAttribute('value', e.delegateTarget.value);

	}

	static selectAccount(index, accountId, vatRate) {

		new Ajax.Query()
			.url('/' + qs('input[name="company"]').getAttribute('value') + '/journal/operation:selectAccount')
			.method('post')
			.body({
				index, account: accountId, vatRate
			})
			.fetch();
	}

	static prefillThirdParty(index, id, name, vatNumber) {

		if(id !== null) {

			new Ajax.Query()
				.url('/' + qs('input[name="company"]').getAttribute('value') + '/journal/operation:selectThirdParty')
				.method('post')
				.body({
					index, thirdParty: id,
					name, vatNumber
				})
				.fetch();

		} else {

			qs('input[data-third-party][data-index="' + index + '"]').setAttribute('value', name);
			qs('input[name="thirdPartyName[' + index + ']"]').setAttribute('value', name);
			qs('input[name="thirdPartyVatNumber[' + index + ']"]').setAttribute('value', vatNumber);

			const autocompleteId = qs('input[data-third-party][data-index="' + index + '"]').getAttribute('id');
			qs('input[data-third-party][data-index="' + index + '"]').focus();

			AutocompleteField.start(qs('#'+ autocompleteId));

		}

	}

	static resetAmount(type, index) {

		OperationAmount.updateAmount(index, type, 0);
		OperationAmount.checkAmounts(index);

	}

	// Appelé uniquement lors d'un warning
	static setVatValue(index) {

		const newVatValue = parseFloat(qs('[data-vat-value-vat-warning-calculated-value][data-index="' + index + '"]').dataset.value);

		OperationAmount.updateAmount(index, 'vatValue', newVatValue);

		OperationAmount.checkAmounts(index);
	}

	// Appelé uniquement lors d'un warning
	static setAmountIncludingVat(index) {

		const newAmountIncludingVat = parseFloat(qs('[data-amount-including-vat-warning-calculated-value][data-index="' + index + '"]').dataset.value);

		OperationAmount.updateAmount(index, 'amountIncludingVAT', newAmountIncludingVat);

		OperationAmount.checkAmounts(index);
	}

}
