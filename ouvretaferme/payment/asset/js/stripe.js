class Stripe {

    static createAccount(farm) {

        if(qs('#stripe-create-account').classList.contains('disabled')) {
            return;
        }

        qs('#stripe-create-account').classList.add('stripe-button-loading', 'disabled');
        qs('#stripe-create-account').setAttribute('disabled', 'disabled');

        new Ajax.Query()
            .url('/payment/stripe:create')
            .body({farm})
            .fetch();

    }

}
