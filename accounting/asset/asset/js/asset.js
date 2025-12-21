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
    const targetamortizableBase = qs('[name="amortizableBase"]');
    const amortizableBase = CalculationField.getValue(targetamortizableBase);

    if(isNaN(amortizableBase)) {
        const targetValue = qs('[name="value"]');
        const value = CalculationField.getValue(targetValue);
        CalculationField.setValue(targetamortizableBase, value);
    }
});

document.delegateEventListener('change', '[data-field="economicMode"]', function() {

    const selectedEconomicMode = qs('[name="economicMode"]:checked').value;
    if(qs('[name="fiscalMode"]:checked') === null) {
        qs('[name="fiscalMode"][value="' + selectedEconomicMode + '"]').checked = true;
    }

    if(selectedEconomicMode === 'without') {
        qs('[name="economicDuration"]').value = '';
        qs('[name="economicDuration"]').setAttribute('disabled', 'disabled');
    } else {
        qs('[name="economicDuration"]').removeAttribute('disabled');
    }

});

document.delegateEventListener('change', '[data-field="fiscalMode"]', function() {

    const selectedFiscalMode = qs('[name="fiscalMode"]:checked').value;

    if(selectedFiscalMode === 'without') {
        qs('[name="fiscalDuration"]').value = '';
        qs('[name="fiscalDuration"]').setAttribute('disabled', 'disabled');
    } else {
        qs('[name="fiscalDuration"]').removeAttribute('disabled');
    }

});

document.delegateEventListener('change', '[name="economicDuration"]', function() {

    if(!qs('[name="fiscalDuration"]').value) {
        qs('[name="fiscalDuration"]').value = qs('[name="economicDuration"]').value;
    }

    Asset.checkEconomicDuration();

});

document.delegateEventListener('autocompleteSelect', '[data-account="asset-asset-create"]', function(e) {

    if(e.detail.value.length === 0) {

        const element = qs('[data-wrapper="accountLabel"] a[class="autocomplete-empty"]');
        AutocompleteField.empty(element);
        Asset.checkEconomicDuration();
    }

});

document.delegateEventListener('autocompleteSelect', '[data-account-label="asset-asset-create"]', function(e) {

    Asset.setRecommendations(e.detail.value);
    Asset.checkEconomicDuration();

});

document.delegateEventListener('focusout', '[data-account-label="asset-asset-create"]', function(e) {

    const value = qs('[data-account-label="asset-asset-create"]').value;

    Asset.setRecommendations(value);
    Asset.checkEconomicDuration();

});


class Asset {

    static durations = null;
    static tolerance = 0;

    static onchangeStatus(element) {

        const value = element.value;

        if(value === 'scrapped') {
            qs('#dispose-scrap-warning').removeHide();
            qsa('[type="sold"]', soldElementWarning => soldElementWarning.hide());
        } else if(value === 'sold') {
            qs('#dispose-scrap-warning').hide();
            qsa('[type="sold"]', soldElementWarning => soldElementWarning.removeHide());
        }

    }

    static initFiscalDurations(json, tolerance) {

        Asset.durations = json;
        Asset.tolerance = tolerance;
    }

    static setRecommendations(accountLabel) {

        const account4 = accountLabel.substring(0, 4);
        const account3 = accountLabel.substring(0, 3);

        const fiscalDuration = Asset.durations[account4] !== undefined ? Asset.durations[account4] : Asset.durations[account3];

        qs('[data-economic-duration-suggested]').hide();

        if(fiscalDuration === undefined) {
            qs('[name="fiscalDuration"]').setAttribute('min', 12); // pas d'immo de moins de 12 mois
            qs('[name="fiscalDuration"]').removeAttribute('max');
            qs('[name="fiscalDuration"]').value = '';
            return;
        }

        qsa('[data-min-year]', node => node.innerHTML = fiscalDuration.durationMin);
        qsa('[data-max-year]', node => node.innerHTML = fiscalDuration.durationMax);
        qsa('[data-min-month]', node => node.innerHTML = parseInt(fiscalDuration.durationMin * 12 * (1 - Asset.tolerance)));
        qsa('[data-max-month]', node => node.innerHTML = parseInt(fiscalDuration.durationMax * 12 * (1 + Asset.tolerance)));

        qs('[name="fiscalDuration"]').setAttribute('min', parseInt(fiscalDuration.durationMin * 12));
        qs('[name="fiscalDuration"]').setAttribute('max', parseInt(fiscalDuration.durationMax * 12));

        if(parseInt(fiscalDuration.durationMin) === parseInt(fiscalDuration.durationMax)) {
            qs('[name="fiscalDuration"]').value = parseInt(fiscalDuration.durationMax * 12);
        }

        const url = qs('#amortization-duration-recommandation').dataset.url;
        new Ajax.Query()
          .url(url)
          .body({ accountLabel })
          .fetch();


    }

    static showAlreadyAmortizePart() {
        const isOpen = qs('.asset-icon-chevron-right').classList.contains('hide');
        qs('[data-already-amortize-part]').toggle();

        if(isOpen) {
            qs('a[data-already-amortize-icon] .asset-icon-chevron-down').hide();
            qs('a[data-already-amortize-icon] .asset-icon-chevron-right').removeHide();
        } else {
            qs('a[data-already-amortize-icon] .asset-icon-chevron-down').removeHide();
            qs('a[data-already-amortize-icon] .asset-icon-chevron-right').hide();
        }
    }

    static checkEconomicDuration() {

        qs('[data-economic-duration-suggested]').hide();

        const economicDuration = parseInt(qs('[name="economicDuration"]').value);
        if(!economicDuration) {
            return;
        }
        const min = parseInt(qs('[data-min-month]').innerHTML);
        const max = parseInt(qs('[data-max-month]').innerHTML);

        if(economicDuration < min || economicDuration > max) {

            qs('[data-economic-duration-suggested]').removeHide();
            qs('[data-suggestion-one-year]').hide();
            qs('[data-suggestion-several-years]').hide();

            if(parseInt(qs('[data-min-year]').innerHTML) === parseInt(qs('[data-max-year]').innerHTML)) {
                qs('[data-suggestion-one-year]').removeHide();
            } else {
                qs('[data-suggestion-several-years]').removeHide();
            }
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
