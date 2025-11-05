document.delegateEventListener('panelClose', '#panel-operation-view', function(e) {

    if(qs('body').dataset.touch === 'yes') {
        return;
    }

    qs('body').classList.remove('operation-view-panel-open');
});

document.delegateEventListener('panelAfterShow', '#panel-operation-view', function(e) {

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

document.delegateEventListener('autocompleteSource', '[data-account-label="journal-operation-create"], [data-account-label="journal-operation-update"], [data-account-label="bank-cashflow-allocate"]', function(e) {

    if(e.detail.results.length === 1 && e.target.value.length === 0) {
        const index = e.detail.input.getAttribute('data-index');
        const inputElement = qs('input[data-wrapper="accountLabel['+ index +']"]');
        AutocompleteField.apply(inputElement, e.detail.results[0]);
    }

});

document.delegateEventListener('autocompleteSelect', '[data-account="journal-operation-create"], [data-account="journal-operation-update"], [data-account="bank-cashflow-allocate"]', function(e) {

    const index = e.detail.input.getAttribute('data-index');

    if(e.detail.value.length !== 0) { // Else : l'utilisateur a supprimé la classe

        if(e.detail.vatClass) {
            qs('[data-index="' + index + '"][data-vat="account-info"]').removeHide();
            qs('[data-index="' + index + '"][data-vat="account-value"]').innerHTML = e.detail.vatClass;
        } else {
            qs('[data-index="' + index + '"][data-vat="account-info"]').hide();
        }
        Operation.updateType(e.detail);
        Operation.refreshVAT(e.detail);
    }

    Operation.checkAutocompleteStatus(e);

    Operation.resetAccountLabel(index);
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

document.delegateEventListener('change', '[data-field="amountIncludingVAT"], [data-field="amount"]', function() {

    const index = this.dataset.index;

    Operation.lockAmount(this.dataset.field === 'amountIncludingVAT' ? 'amount' : 'amountIncludingVAT', index);
    Operation.updateAmountValue(index, this.dataset.field);
    Asset.initializeData(index);

    Operation.updateVatValue(index);
    Operation.checkVatConsistency(index);
});

document.delegateEventListener('change', '[data-field="vatRate"]', function() {

    const index = this.dataset.index;
    Operation.setIsWrittenAmount(this.dataset.field, index);
    Operation.updateAmountValue(index, this.dataset.field);
    Operation.updateVatValue(index);
    Operation.checkVatConsistency(index);

});

document.delegateEventListener('change', '[data-field="vatValue"]', function() {

    const index = this.dataset.index;
    Operation.setIsWrittenAmount(this.dataset.field, index);
    Operation.updateAmountValue(index, this.dataset.field);
    Operation.checkVatConsistency(index);

});

document.delegateEventListener('change', '[data-field="type"]', function () {

    const index = this.dataset.index;

    Operation.updateAmountValue(index);
    Operation.checkVatConsistency(index);

});

document.delegateEventListener('input', '[data-field="document"]', function(e) {

    const value = e.delegateTarget.value;
    e.delegateTarget.value = value.toFqn();

});


class Operation {

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

    static preFillNewOperation(index) {

        qs('[name="date[' + index + ']"]').setAttribute('value', qs('[name="date[' + (index - 1) + ']"]').value)
        qs('[name="document[' + index + ']"]').setAttribute('value', qs('[name="document[' + (index - 1) + ']"]').value)
        qs('[name="description[' + index + ']"]').setAttribute('value', qs('[name="description[' + (index - 1) + ']"]').value)

        if(qs('[name="paymentMethod[' + (index - 1) + ']"]:checked')) {
            const checked = qs('[name="paymentMethod[' + (index - 1) + ']"]:checked')?.value || '';
            qs('[name="paymentMethod[' + index + ']"][value="' + checked + '"]').setAttribute('checked', 'checked');
        }

        if(qs('[name="paymentDate[' + index + ']"]') && qs('[name="paymentDate[' + (index - 1) + ']"]')) {
            qs('[name="paymentDate[' + index + ']"]').setAttribute('value', qs('[name="paymentDate[' + (index - 1) + ']"]').value)
        }

        if(qs('[name="thirdParty[' + index + ']"]') && qs('[name="thirdParty[' + (index - 1) + ']"]')) {
            qs('[name="thirdParty[' + index + ']"]').setAttribute('value', qs('[name="thirdParty[' + (index - 1) + ']"]').value || null)
        }

    }

    static resetAccountLabel(index) {

        const element = qs('[data-wrapper="accountLabel['+ index +']"] a[class="autocomplete-empty"]');
        AutocompleteField.empty(element);

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

    static refreshVAT(accountDetail) {

        // Non soumis à la TVA
        if(Operation.hasVat() === false) {
            return;
        }

        const index = parseInt(accountDetail.input.getAttribute('data-index'));

        // Si le taux de TVA était à 0, on va re-calculer le montant HT pour éviter d'avoir à le ressaisir.
        const targetAmount = qs('[name="amount[' + index + ']"');
        const amount = CalculationField.getValue(targetAmount);

        if(isNaN(amount)) {
            return;
        }

        const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

        // On ne surcharge pas ce qui a déjà été saisi par l'utilisateur
        if(vatRate !== 0.0) {
            if(vatRate !== accountDetail.vatRate) {
                qs('[data-vat-rate-warning][data-index="' + index + '"]').removeHide();
                qs('span[data-vat-rate-default][data-index="' + index + '"]').innerHTML = accountDetail.vatRate;
                qs('span[data-vat-rate-class][data-index="' + index + '"]').innerHTML = accountDetail.class;
                qs('a[data-vat-rate-link][data-index="' + index + '"]').setAttribute('onclick', 'Operation.updateVatRate(' + index + ', ' + accountDetail.vatRate + ')');
            }
            return;
        }

        if(isNaN(amount) === false) {
            const newAmount = round(amount / (1 + accountDetail.vatRate / 100));
            CalculationField.setValue(targetAmount, Math.abs(newAmount));
        }

        // On remplit ensuite le taux de TVA
        qs('[name="vatRate[' + index + ']"]').value = accountDetail.vatRate;

        Operation.recalculateVAT(index);

    }

    static recalculateVAT(index) {

        if(Operation.hasVat() === false) {
            return;
        }

        const targetAmount = qs('[name="amount[' + index + ']"');
        const amount = CalculationField.getValue(targetAmount);

        const vatRate = qs('[name="vatRate[' + index + ']"]').value;

        const targetVatValue = qs('[name="vatValue[' + index +']"');
        const vatValue = round(amount * vatRate / 100);

        CalculationField.setValue(targetVatValue, vatValue);

        if(Operation.isLocked('amountIncludingVAT', index)) {

            const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
            CalculationField.setValue(targetAmountIncludingVAT, round(vatValue + amount));

        }

        if(typeof Cashflow !== 'undefined') {
            Cashflow.fillVatValue(index);
        }

        // On vérifie les calculs de TVA
        Operation.checkVatConsistency(index);

        if(typeof Cashflow !== 'undefined') {
            Cashflow.checkValidationValues();
        }

    }

    static updateVatRate(index, vatRate) {

        qs('[name="vatRate[' + index + ']"]').value = vatRate;
        Operation.recalculateVAT(index);

    }

    static updateVatValue(index) {

        Operation.recalculateVAT(index);

        if(typeof Cashflow !== 'undefined') {
            Cashflow.checkValidationValues();
        }

    }

    static checkVatConsistency(index) {

        if(Operation.hasVat() === false) {
            return;
        }

        if(typeof Cashflow !== 'undefined') {
            Cashflow.checkValidationValues();
        }

        const targetAmount = qs('[name="amount[' + index + ']"');
        const amount = CalculationField.getValue(targetAmount);

        const vatRate = parseFloat(qs('[name="vatRate[' + index + ']"').valueAsNumber || 0);

        const targetVatValue = qs('[name="vatValue[' + index + ']"');
        const vatValue = CalculationField.getValue(targetVatValue);

        if(isNaN(amount) || isNaN(vatValue)) {
            return;
        }

        const expectedVatValue = round(amount * vatRate / 100);

        if(Math.abs(round(vatValue - expectedVatValue)) > 0.01) {
            qs('[data-vat-warning][data-index="' + index + '"]').removeHide();
            qs('[data-wrapper="vatValue[' + index + ']"]', node => node.classList.add('form-warning-wrapper'));
            qs('[data-vat-warning-value][data-index="' + index + '"]').innerHTML = money(expectedVatValue);
            if(typeof Cashflow !== 'undefined') {
                Cashflow.vatWarning(true);
            }
        } else {
            qs('[data-vat-rate-warning][data-index="' + index + '"]').hide();
            qs('[data-wrapper="vatValue[' + index + ']"]', node => node.classList.remove('form-warning-wrapper'));
            qs('[data-vat-warning][data-index="' + index + '"]').hide();
            if(typeof Cashflow !== 'undefined') {
                Cashflow.vatWarning(false);
            }
        }

    }

    static copyDate(e) {

        if(e.delegateTarget.value === null) {
            return;
        }

        const index = e.delegateTarget.getAttribute('data-index');
        const paymentDateElement = e.delegateTarget.firstParent('div.operation-create').qs('[name="paymentDate[' + index + ']"]');

        if(!paymentDateElement.value) {
            paymentDateElement.setAttribute('value', e.delegateTarget.value);
        }

    }

    // Manipulation des montants

    static updateAmountValue(index, lastFieldFilled) {

        // Montant HT
        const targetAmount = qs('[name="amount[' + index + ']"');
        const amount = CalculationField.getValue(targetAmount);
        const isAmountLocked = Operation.isLocked('amount', index);
        if(isNaN(amount) === false) {
            Operation.setIsWrittenAmount('amount', index);
        }

        // Montant TTC
        const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
        const amountIncludingVAT = CalculationField.getValue(targetAmountIncludingVAT);
        const isAmountIncludingVATLocked = Operation.isLocked('amountIncludingVAT', index);
        if(isNaN(amountIncludingVAT) === false) {
            Operation.setIsWrittenAmount('amountIncludingVAT', index);
        }

        // Pas de TVA
        if(Operation.hasVat() === false) {

            if(isNaN(targetAmount) === false) {
                CalculationField.setValue(targetAmountIncludingVAT, amount);
            } else {
                CalculationField.setValue(targetAmount, amountIncludingVAT);
            }

        } else {

            const vatRate = qs('[name="vatRate[' + index + ']"]')?.valueAsNumber || 0;
            const targetVatValue = qs('[name="vatValue[' + index + ']"');
            const vatValue = CalculationField.getValue(targetVatValue);

            if(isAmountLocked && isNaN(amountIncludingVAT) === false) {
                let newAmount = null;
                // Cet ordre doit être conservé et non factorisé (pour ne pas avoir à "deviner" quel champ de TVA utiliser : taux ou valeur)
                if(lastFieldFilled === 'vatValue') {
                    newAmount = round(amountIncludingVAT - vatValue);
                } else if(lastFieldFilled === 'vatRate' || lastFieldFilled === 'amountIncludingVAT') {
                    newAmount = round(amountIncludingVAT / (1 + vatRate / 100));
                } else if(isNaN(vatValue) === false) {
                    newAmount = round(amountIncludingVAT - vatValue);
                } else if(isNaN(vatRate) === false) {
                    newAmount = round(amountIncludingVAT / (1 + vatRate / 100));
                }
                if(newAmount !== null) {
                    CalculationField.setValue(targetAmount, newAmount);
                }
            } else if(isAmountIncludingVATLocked && isNaN(amount) === false) {
                let newAmountIncludingVAT = null;
                // Cet ordre doit être conservé et non factorisé (pour ne pas avoir à "deviner" quel champ de TVA utiliser : taux ou valeur)
                if(lastFieldFilled === 'vatValue') {
                    newAmountIncludingVAT = round(amount + vatValue);
                } else if(lastFieldFilled === 'vatRate' || lastFieldFilled === 'amount') {
                    newAmountIncludingVAT = round(amount * (1 + vatRate / 100));
                } else if(isNaN(vatValue) === false) {
                    newAmountIncludingVAT = round(amount + vatValue);
                } else if(isNaN(vatRate) === false) {
                    newAmountIncludingVAT = round(amount * (1 + vatRate / 100));
                }
                if(newAmountIncludingVAT !== null) {
                    CalculationField.setValue(targetAmountIncludingVAT, newAmountIncludingVAT);
                }
            }

        }
    }

    static resetAmount(type, index) {
        Operation.unlockAmount(type, index, true);

        switch(type) {

            case 'amountIncludingVAT':
                Operation.unlockAmount('amount', index, true);
                break;

            case 'amount':
                Operation.unlockAmount('amountIncludingVAT', index, true);
                break;

        }
    }

    static lockAmount(type, index) {

        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-lock').removeHide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-erase').hide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-write').hide();

        qs('[name="' + type + '[' + index + ']-calculation"')?.classList?.add('disabled');

        qs('[name="' + type + '[' + index + ']"]')?.classList?.add('disabled');

    }

    static unlockAmount(type, index, empty) {

        const target = qs('[name="' + type + '[' + index + ']"]');

        // vatRate is not a CalculationField
        if(type === 'vatRate') {
            if(empty === true) {
                qs('[name="' + type + '[' + index + ']"]').value = '';
            }
        } else {
            if(empty === true) {
                CalculationField.setValue(target, '');
            }
        }

        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-lock').hide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-erase').hide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-write').removeHide();


        if(qs('[name="' + type + '[' + index + ']-calculation"')) {
            qs('[name="' + type + '[' + index + ']-calculation"').classList.remove('disabled');
            qs('[name="' + type + '[' + index + ']-calculation"').removeAttribute('disabled');
        }
        qs('[name="' + type + '[' + index + ']"')?.classList?.remove('disabled');

    }

    static setIsWrittenAmount(type, index) {

        if(qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-lock').classList.contains('hide') === false) {
            return;
        }

        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-lock').hide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-write').hide();
        qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-erase').removeHide();

    }
    
    static isLocked(type, index) {

        return !qs('[data-wrapper="' + type + '[' + index + ']"] .merchant-lock').classList.contains('hide');

    }

    // Seulement pour la création
    static openInvoiceFileForm() {

        const columns = qs('#operation-create-list').getAttribute('data-columns');

        if(columns > 1) {
            return false;
        }

        qs('#read-invoice input[name="columns"]').setAttribute('value', columns);
        qs('#read-invoice input[type="file"]').click();
    }

    static submitReadInvoice() {
        qs('#read-invoice-submit').click();
    }

    static deactivateInvoiceImport() {

        qs('.import-invoice-button > label').classList.add('disabled');
        qs('.import-invoice-button > label').setAttribute('onclick', 'void(0);');

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
}
