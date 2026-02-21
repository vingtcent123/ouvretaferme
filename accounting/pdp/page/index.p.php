<?php

new Page()
	->get('connect', function($data) {

		if(FEATURE_PDP === FALSE) {
			throw new NotExistsAction();
		}

		throw new RedirectAction(\pdp\ConnectionLib::getAuthorizeUrl($data->eFarm));

	});

new Page(function($data) {

	if(FEATURE_PDP === FALSE) {
		throw new NotExistsAction();
	}

})
	->get('index', function($data) {

		$data->token = \pdp\ConnectionLib::getValidToken();

		if($data->token !== NULL) {

			\pdp\CompanyLib::synchronize();
			$data->eCompany = \pdp\CompanyLib::getWithAddresses();

		} else {

			$data->hadToken = \account\PartnerLib::getByPartner(\account\PartnerSetting::SUPER_PDP)->notEmpty();

		}

		throw new ViewAction($data);

	})
;
