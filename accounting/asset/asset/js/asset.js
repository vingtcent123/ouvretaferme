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
