document.delegateEventListener('panelAfterPaint', '#panel-bank-cashflow-allocate', function() {
    Cashflow.checkValidationValues();
});

class Cashflow {

    static recalculateAmounts(excludeIndex) {

        const operationNumber = qs('#add-operation').getAttribute('post-index');

        let sum = 0;
        for(let index = 0; index < operationNumber; index++) {

            if(excludeIndex !== undefined && excludeIndex === index) {
                continue;
            }

            const targetAmount = qs('[name="amount[' + index + ']"');
            const amount = CalculationField.getValue(targetAmount);

            const targetVatValue = qs('[name="vatValue[' + index + ']"');
            const vatValue = CalculationField.getValue(targetVatValue);

            const type = Array.from(qsa('#create-operation-list [name="type[' + index + ']"]')).find((checkboxType) => checkboxType.checked === true);

            const amountToAdd = Math.abs(isNaN(amount) ? 0 : amount);
            const vatAmountToAdd = Math.abs(isNaN(vatValue) ? 0 : vatValue);

            const totalAmountToAdd = amountToAdd + vatAmountToAdd;

            sum += (type.value === 'credit' ? totalAmountToAdd : totalAmountToAdd * -1);
        }

        return round(sum);

    }

    // Remplit les valeurs de l'écriture en fonction des autres écritures créées et du montant total attendu
    static fillIndexAccordingly(index) {

        const totalAmountIncludingVat = parseFloat(qs('span[name="cashflow-amount"]').innerHTML);

        const sum = Cashflow.recalculateAmounts(index);

        const missingAmountIncludingVATValue = round(totalAmountIncludingVat - sum);

        const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index + ']"');
        CalculationField.setValue(targetAmountIncludingVAT, Math.abs(missingAmountIncludingVATValue));

        const targetAmount = qs('[name="amount[' + index + ']"');
        const vatRate = qs('[name="vatRate[' + index + ']"]').valueAsNumber;
        const missingAmountValue = round(missingAmountIncludingVATValue / (1 + vatRate / 100));
        CalculationField.setValue(targetAmount, Math.abs(missingAmountValue));

        const missingVatValue = round(missingAmountIncludingVATValue - missingAmountValue);
        const targetVatValue = qs('[name="vatValue[' + index + ']"');
        CalculationField.setValue(targetVatValue, Math.abs(missingVatValue));

        if(missingAmountValue > 0) {
            qs('[name="type[' + index + ']"][value="credit"]').setAttribute('checked', true);
        } else {
            qs('[name="type[' + index + ']"][value="debit"]').setAttribute('checked', true);
        }

    }

    static updateNewOperationLine(index) {

        Operation.preFillNewOperation(index); // On copie ce qu'on peut copier

        Cashflow.fillIndexAccordingly(index); // On remplit les trous

        Cashflow.checkValidationValues();

    }

    static copyDocument(target) {

        const documentValue = target.value;

        const operations = qsa('#create-operation-list [name^="document"]');
        Array.from(operations).forEach((operation) => {
            if(operation.getAttribute('value') !== '' && operation.getAttribute('value') !== null) {
                return;
            }
            operation.setAttribute('value', documentValue);
        })

        return true;

    }

    // Recalcule le montant TTC / HT en fonction de la TVA et des montants des autres écritures déjà remplies.
    static recalculate(index) {

        Cashflow.fillIndexAccordingly(index); // On remplit les trous
        Cashflow.checkValidationValues();

    }

    static sumType(type) {

        const allValues = Array.from(qsa('[type="hidden"][name^="' + type + '["]', element => element.value));

        return round(allValues.reduce(function (acc, value) {
            const index = value.firstParent('.input-group').qs('input[data-index]').dataset.index;

            const creditType = qs('[name="type[' + index + ']"]:checked').getAttribute('value');

            if(creditType === 'credit') {
                return acc - parseFloat(value.value || 0);
            } else {
                return acc + parseFloat(value.value || 0);
            }
        }, 0));
    }

    static fillVatValue(index) {

        if(qs('form#bank-cashflow-allocate') === null) {
            return;
        }

        const sum = Cashflow.recalculateAmounts(index);
        // Ce n'est pas la seule écriture : on ne bricole pas automatiquement en cas d'écart de centime.
        if(sum !== 0.0) {
            return;
        }

        const totalAmountIncludingVat = Math.abs(parseFloat(qs('span[name="cashflow-amount"]').innerHTML));

        const targetAmount = qs('[name="amount[' + index + ']"');
        const amount = CalculationField.getValue(targetAmount);

        const vatRate = qs('[name="vatRate[' + index + ']"]').value;

        const targetVatValue = qs('[name="vatValue[' + index +']"');
        const vatValue = round(amount * vatRate / 100);

        if(amount + vatValue === totalAmountIncludingVat) {
            return;
        }

        if(Math.abs(round(totalAmountIncludingVat - amount - vatValue)) <= 0.01) {
            const newVatValue = round(totalAmountIncludingVat - amount);
            CalculationField.setValue(targetVatValue, newVatValue);

            const targetAmountIncludingVAT = qs('[name="amountIncludingVAT[' + index +']"');
            CalculationField.setValue(targetAmountIncludingVAT, round(amount + newVatValue));
        }

    }

    static checkValidationValues() {

        if(qs('form#bank-cashflow-allocate') === null) {
            return;
        }

        const sum = this.recalculateAmounts();
        const totalAmount = parseFloat(qs('span[name="cashflow-amount"]').innerHTML);
        const cashflowType = qs('input[type="hidden"][name="type"]').value;

        // Pour une lecture plus facile, crédit et débit doivent être affichés en positif
        const multiplier = cashflowType === 'credit' ? -1 : 1;

        const amountIncludingVAT = Cashflow.sumType('amountIncludingVAT') * multiplier;
        const amount = Cashflow.sumType('amount') * multiplier;
        const vatValue = Cashflow.sumType('vatValue') * multiplier;
        const assetValue = Array.from(qsa('[type="number"][name^="asset["]')).map(function(node) {
            if(node.name.match(/asset\[\d+\]\[value\]/)) {
                return node;
            }
        }).filter(asset => asset)
            .reduce((acc, value) => acc + parseFloat(value.value || 0), 0);

        qs('.create-operation-validate[data-field="amountIncludingVAT"] [data-type="value"]').innerHTML = money(amountIncludingVAT);
        qs('.create-operation-validate[data-field="amount"] [data-type="value"]').innerHTML = money(amount);
        qs('.create-operation-validate[data-field="vatValue"] [data-type="value"]').innerHTML = money(vatValue);
        qs('.create-operation-validate[data-field="assetValue"] [data-type="value"]').innerHTML = money(assetValue);

        if(sum !== totalAmount) {
            var difference = round(totalAmount - sum);
            qs('.create-operation-validate[data-field="amountIncludingVAT"]').classList.add('danger');
            qs('.create-operation-validate[data-field="amountIncludingVAT"]').previousSibling.classList.add('danger');
            qs('#cashflow-allocate-difference-warning').classList.remove('hide');
            qs('#cashflow-allocate-difference-value').innerHTML = money(Math.abs(difference));
            qs('#submit-save-operation').setAttribute('data-confirm', qs('#submit-save-operation').getAttribute('data-confirm-text'));
        } else {
            qs('.create-operation-validate[data-field="amountIncludingVAT"]').previousSibling.classList.remove('danger');
            qs('.create-operation-validate[data-field="amountIncludingVAT"]').classList.remove('danger');
            qs('#cashflow-allocate-difference-warning').classList.add('hide');
            qs('#submit-save-operation').removeAttribute('data-confirm');
        }
    }

    static vatWarning(on) {

        if(qs('form#bank-cashflow-allocate') === null) {
            return;
        }

        if(on === true) {
            qs('.create-operation-validate[data-field="vatValue"]').classList.add('warning');
        } else {
            qs('.create-operation-validate[data-field="vatValue"]').classList.remove('warning');
        }
    }
}

class CashflowList {
    static scrollTo(cashflowId) {

        if(parseInt(cashflowId) > 0) {
            const { top: mainTop} = qs('main').getBoundingClientRect();
            const stickyHeight = qs('[name="cashflow-' + cashflowId + '"]').firstParent('table')?.qs('.thead-sticky')?.scrollHeight || 0;
            const { top: divTop } = qs('#cashflow-list [name="cashflow-' + cashflowId + '"]').getBoundingClientRect();
            window.scrollTo({top: divTop - mainTop - stickyHeight, behavior: 'smooth'});
        }

    }
}


document.delegateEventListener('click', '#cashflow-doAttach input[type="checkbox"]', function() {
    CashflowAttach.updateTotal();
});
class CashflowAttach {

    static updateTotal() {

        let total = 0;
        qsa('input[type="checkbox"][name="operation[]"]:checked', operation => total += parseFloat(qs('span[data-operation="' + operation.value + '"][name="amount"]').innerHTML));
        total = round(total);
        qs('span[data-field="totalAmount"]').innerHTML = money(total);

        const cashflowAmount = parseFloat(qs('span[name="cashflow-amount"]').innerHTML);

        if(Math.abs(cashflowAmount) !== Math.abs(total)) {
            qs('#cashflow-attach-difference-warning').classList.remove('hide');
        } else {
            qs('#cashflow-attach-difference-warning').classList.add('hide');
        }

    }
}
