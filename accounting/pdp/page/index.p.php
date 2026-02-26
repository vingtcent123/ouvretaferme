<?php

new Page()
	->get('connect', function($data) {

		if(\pdp\PdpLib::isActive($data->eFarm) === FALSE) {
			throw new NotExistsAction();
		}

		throw new RedirectAction(\pdp\ConnectionLib::getAuthorizeUrl($data->eFarm));

	});

new Page(function($data) {

	if(\pdp\PdpLib::isActive($data->eFarm) === FALSE) {
		throw new NotExistsAction();
	}

})
	->get('onboarding', function($data) {

		if($data->eFarm['hasPdp']) {
			throw new RedirectAction(\farm\FarmUi::urlConnected($data->eFarm).'/pdp/');
		}

		throw new ViewAction($data);

	})
	->get('index', function($data) {

		$data->token = \pdp\ConnectionLib::getValidToken();

		if($data->token !== NULL) {

			\pdp\CompanyLib::synchronize();
			$data->eCompany = \pdp\CompanyLib::getWithAddresses();
			$data->nAddress = \pdp\AddressLib::countValidAddresses();

		} else {

			$data->hadToken = \account\PartnerLib::getByPartner(\account\PartnerSetting::SUPER_PDP)->notEmpty();

		}

		throw new ViewAction($data);

	})
;
