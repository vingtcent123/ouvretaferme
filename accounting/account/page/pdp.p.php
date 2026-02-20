<?php

new Page()
	->get('connect', function($data) {

		if(FEATURE_PDP === FALSE) {
			throw new NotExistsAction();
		}

		throw new RedirectAction(\account\SuperPdpLib::getAuthorizeUrl($data->eFarm));

	});

new Page(function($data) {

	if(FEATURE_PDP === FALSE) {
		throw new NotExistsAction();
	}

})
	->get('index', function($data) {

		$data->token = \account\SuperPdpLib::getValidToken();

		$data->company = \account\SuperPdpLib::getCompany();

		throw new ViewAction($data);

	})
	->get('getInvoices', function($data) {

		$accessToken = \account\SuperPdpLib::getValidToken();

		$invoices = \account\SuperPdpLib::getInvoices($accessToken);
		$invoice = \account\SuperPdpLib::getInvoice($accessToken, 513);
		dd($invoice, $invoices);
	});
