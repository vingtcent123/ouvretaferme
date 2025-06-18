document.delegateEventListener('autocompleteSelect', '[data-account="journal-operation-create"], [data-account="bank-cashflow-allocate"]', function(e) {
    Asset.openForm(e);
});

class Asset {

    static openForm(e) {

        const index = e.delegateTarget.dataset.index;
        const accountClass = e.detail.class;

        // assetClass & subventionAssetClass
        if(!accountClass || (!accountClass.startsWith('2') && !accountClass.startsWith('13'))) {

            e.delegateTarget.firstParent('.create-operation').classList.remove('is-asset');

            const hasAsset = qsa('.create-operation:not(.create-operation-headers).is-asset').length > 0;

            if(hasAsset === false) {
                qs('.create-operation-headers').classList.remove('is-asset');
                e.delegateTarget.firstParent('.create-operations-container').classList.remove('has-asset')
            }

            return;
        }


        e.delegateTarget.firstParent('.create-operation').classList.add('is-asset');
        e.delegateTarget.firstParent('.create-operations-container').classList.add('has-asset');
        qs('.create-operation-headers').classList.add('is-asset');

        Asset.initializeData(index);

    }

    static initializeData(index) {

        qs('[name="asset[' + index + '][acquisitionDate]"]').setAttribute('value', qs('[name="date[' + index + ']"').value);
        qs('[name="asset[' + index + '][startDate]"]').setAttribute('value', qs('[name="date[' + index + ']"').value);
        qs('[name="asset[' + index + '][value]"]').setAttribute('value', qs('[name="amount[' + index + ']"').value);

        if(typeof Cashflow !== 'undefined') {
            Cashflow.checkValidationValues();
        }

    }
}
