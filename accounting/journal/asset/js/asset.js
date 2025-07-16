document.delegateEventListener('autocompleteSelect', '[data-account="journal-operation-create"], [data-account="bank-cashflow-allocate"]', function(e) {
    Asset.openForm(e);
});

class Asset {

    static openForm(e) {

        const index = e.delegateTarget.dataset.index;
        const accountClass = e.detail.class;

        const isGrant = accountClass?.startsWith('13');
        const isAsset = accountClass?.startsWith('2');

        // assetClass & subventionAssetClass
        if(!accountClass || (!isAsset && !isGrant)) {

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

        if(isAsset) {

            qs('div[data-help="asset"]').removeHide();
            qs('input[name="asset[' + index + '][type]"][value="grant"]').setAttribute('disabled', 'disabled');
            qs('input[name="asset[' + index + '][type]"][value="grant"]').removeAttribute('checked');
            qs('input[name="asset[' + index + '][type]"][value="linear"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][type]"][value="without"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][acquisitionDate]"]').removeAttribute('disabled');
            qs('select[name="asset[' + index + '][grant]"]').removeAttribute('disabled');

        } else if(isGrant) {

            qs('input[name="asset[' + index + '][type]"][value="grant"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][type]"][value="grant"]').setAttribute('checked', 'checked');
            qs('input[name="asset[' + index + '][type]"][value="linear"]').removeAttribute('checked');
            qs('input[name="asset[' + index + '][type]"][value="linear"]').setAttribute('disabled', 'disabled');
            qs('input[name="asset[' + index + '][type]"][value="without"]').removeAttribute('checked');
            qs('input[name="asset[' + index + '][type]"][value="without"]').setAttribute('disabled', 'disabled');
            qs('input[name="asset[' + index + '][acquisitionDate]"]').setAttribute('disabled', 'disabled');
            qs('select[name="asset[' + index + '][grant]"]').setAttribute('disabled', 'disabled');

        }

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
