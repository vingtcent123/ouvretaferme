document.delegateEventListener('autocompleteSelect', '[data-account="journal-operation-create"], [data-account="bank-cashflow-allocate"]', function(e) {
    Asset.openForm(e);
});

class Asset {

    static openForm(e) {

        const index = e.delegateTarget.dataset.index;
        const accountClass = e.detail.class;

        const isGrant = accountClass?.startsWith('13');
        const isAsset = accountClass?.startsWith('2');

        // assetClass & grantAssetClass
        if(!accountClass || (!isAsset && !isGrant)) {

            e.delegateTarget.firstParent('.operation-create').classList.remove('is-asset');

            const hasAsset = qsa('.operation-create:not(.operation-create-headers).is-asset').length > 0;

            if(hasAsset === false) {
                qs('.operation-create-headers').classList.remove('is-asset');
                e.delegateTarget.firstParent('.operation-create-several-container').classList.remove('has-asset')
            }

            return;
        }


        e.delegateTarget.firstParent('.operation-create').classList.add('is-asset');
        e.delegateTarget.firstParent('.operation-create-several-container').classList.add('has-asset');
        qs('.operation-create-headers').classList.add('is-asset');

        Asset.initializeData(index);

        if(isAsset) {

            qs('div[data-help="grant"]').hide();
            qs('div[data-help="asset"]').removeHide();
            qs('input[name="asset[' + index + '][type]"][value="grant-recovery"]').parentElement
            qs('input[name="asset[' + index + '][type]"][value="grant-recovery"]').setAttribute('disabled', 'disabled');
            qs('input[name="asset[' + index + '][type]"][value="grant-recovery"]').removeAttribute('checked');

            qs('input[name="asset[' + index + '][type]"][value="without"]').removeAttribute('disabled');

            qs('input[name="asset[' + index + '][type]"][value="linear"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][startDate]"]').removeAttribute('disabled');
            qs('select[name="asset[' + index + '][grant]"]').removeAttribute('disabled');

            qsa('[data-asset-link="asset"]', element => element.hide())
            qsa('[data-asset-link="grant"]', element => element.removeHide())

        } else if(isGrant) {

            qs('div[data-help="grant"]').removeHide();
            qs('div[data-help="asset"]').hide();

            qs('input[name="asset[' + index + '][type]"][value="grant-recovery"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][type]"][value="grant-recovery"]').removeAttribute('checked');
            qs('input[name="asset[' + index + '][type]"][value="without"]').removeAttribute('disabled');
            qs('input[name="asset[' + index + '][type]"][value="without"]').removeAttribute('checked');

            qs('input[name="asset[' + index + '][type]"][value="linear"]').removeAttribute('checked');
            qs('input[name="asset[' + index + '][type]"][value="linear"]').setAttribute('disabled', 'disabled');
            qs('input[name="asset[' + index + '][startDate]"]').setAttribute('disabled', 'disabled');
            qs('select[name="asset[' + index + '][grant]"]').setAttribute('disabled', 'disabled');

            qsa('[data-asset-link="asset"]', element => element.removeHide());
            qsa('[data-asset-link="grant"]', element => element.hide());
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
