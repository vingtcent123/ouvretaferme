<?php
new \selling\PaymentLinkPage()
	->create(function($data) {

		if(get_exists('sale')) {
			$eElement = \selling\SaleLib::getById(GET('sale'))->validate('acceptStripeLink');
		} else if(get_exists('invoice')) {
			$eElement = \selling\InvoiceLib::getById(GET('invoice'))->validate('acceptStripeLink');
		} else {
			throw new NotExistsAction();
		}

		$eElement['farm']->validate('canManage');

		$data->cPaymentLink = \selling\PaymentLinkLib::getValidByElement($eElement);

		$data->eElement = $eElement;

		throw new ViewAction($data);

	})
	->doCreate(fn($data) => throw new ReloadAction( 'selling', 'PaymentLink::created'))
	->read('success', function($data) {

		throw new ViewAction($data);

	})
;

?>
