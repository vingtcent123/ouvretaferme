
document.delegateEventListener('autocompleteSelect', '#journal-operation-create-payment [data-third-party="journal-operation-create-payment"]', function(e) {

	PaymentOperation.updateThirdParty(e.detail);
	PaymentOperation.loadWaitingOperations();

});

class PaymentOperation {

	static updateThirdParty(detail) {

		// Indication du label
		qs('#journal-operation-create-payment [name="accountLabel"]').setAttribute('data-client', detail.clientAccountLabel);
		qs('#journal-operation-create-payment [name="accountLabel"]').setAttribute('data-supplier', detail.supplierAccountLabel);

		PaymentOperation.updateAccountLabel();

	}

	static updateAccountLabel() {

		const paymentType = qs('#journal-operation-create-payment [name="paymentType"]:checked')?.getAttribute('value') || null;

		if(paymentType.indexOf('client') > 0 && qs('#journal-operation-create-payment [name="accountLabel"]').getAttribute('data-client')) {

			qs('#journal-operation-create-payment [name="accountLabel"]').setAttribute('value', qs('#journal-operation-create-payment [name="accountLabel"]').getAttribute('data-client') || '');

		} else if(paymentType.indexOf('supplier') > 0 && qs('#journal-operation-create-payment [name="accountLabel"]').getAttribute('data-supplier')) {

			qs('#journal-operation-create-payment [name="accountLabel"]').setAttribute('value', qs('#journal-operation-create-payment [name="accountLabel"]').getAttribute('data-supplier') || '');

		} else {

			qs('#journal-operation-create-payment [name="accountLabel"]').setAttribute('value', '');

		}
		PaymentOperation.loadWaitingOperations();
	}

	static loadWaitingOperations() {

		const paymentType = qs('#journal-operation-create-payment [name="paymentType"]:checked')?.getAttribute('value') || null;
		const thirdParty = qs('#journal-operation-create-payment [name="thirdParty"]')?.getAttribute('value') || null;

		if(paymentType === null || thirdParty === null) {
			return;
		}

		const farm = qs('#journal-operation-create-payment [name="farm"]').getAttribute('value');

		new Ajax.Query()
			.url(farm + '/journal/operation:getWaiting')
			.body({
				thirdParty, paymentType, farm
			})
			.fetch();

	}

}
