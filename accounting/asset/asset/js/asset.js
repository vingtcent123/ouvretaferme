document.delegateEventListener('autocompleteBeforeQuery', '[data-account-label="asset-asset-create"], [data-account-label="asset-asset-update"]', function(e) {

    if(e.detail.input.firstParent('form').qs('[name^="account"]') !== null) {
        const account = e.detail.input.firstParent('form').qs('[name^="account"]').getAttribute('value');
        e.detail.body.append('account', account);
    }

});

document.delegateEventListener('autocompleteSource', '[data-account-label="asset-asset-create"], [data-account-label="asset-asset-update"]', function(e) {

    if(e.detail.results.length === 1 && e.target.value.length === 0) {
        const inputElement = qs('[data-wrapper="accountLabel"] input');
        AutocompleteField.apply(inputElement, e.detail.results[0]);
    }

});

document.delegateEventListener('change', '[data-wrapper="value-calculation"] input', function() {
    const targetDepreciableBase = qs('[name="depreciableBase"]');
    const depreciableBase = CalculationField.getValue(targetDepreciableBase);

    if(isNaN(depreciableBase)) {
        const targetValue = qs('[name="value"]');
        const value = CalculationField.getValue(targetValue);
        CalculationField.setValue(targetDepreciableBase, value);
    }
});

document.delegateEventListener('change', '[data-field="economicMode"]', function() {

    const selectedEconomicMode = qs('[name="economicMode"]:checked').value;
    if(qs('[name="fiscalMode"]:checked') === null) {
        qs('[name="fiscalMode"][value="' + selectedEconomicMode + '"]').checked = true;
    }

});

document.delegateEventListener('change', '[name="economicDuration"]', function() {

    if(!qs('[name="fiscalDuration"]').value) {
        qs('[name="fiscalDuration"]').value = qs('[name="economicDuration"]').value;
    }

});

class Asset {

    static onchangeStatus(element) {

        const form = element.firstParent('form');
        const amountElement = form.qs('[name="amount"]');

        const value = element.value;

        if(value === 'scrapped') {
            amountElement.setAttribute('value', 0);
            amountElement.setAttribute('disabled', 'disabled');
            qs('#dispose-scrap-warning').removeHide();
            qsa('[type="sold"]', soldElementWarning => soldElementWarning.hide());
        } else if(value === 'sold') {
            amountElement.removeAttribute('disabled');
            qs('#dispose-scrap-warning').hide();
            qsa('[type="sold"]', soldElementWarning => soldElementWarning.removeHide());
        }

    }
}

class DepreciationList {

    static scrollTo(assetId) {

        if(parseInt(assetId) > 0) {
            const { top: mainTop} = qs('main').getBoundingClientRect();
            const stickyHeight = qs('[name="asset-' + assetId + '"]').firstParent('table')?.qs('.thead-sticky')?.scrollHeight || 0;
            const { top: divTop } = qs('#asset-list [name="asset-' + assetId + '"]').getBoundingClientRect();

            window.scrollTo({top: divTop - mainTop - stickyHeight, behavior: 'smooth'});
        }

    }

}
