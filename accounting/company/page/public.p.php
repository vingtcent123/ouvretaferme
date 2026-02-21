<?php
new Page(function($data) {

	if($data->eFarm->usesAccounting()) {
		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal');
	}

})
	->get('/comptabilite/decouvrir', function ($data) {

		throw new ViewAction($data);

	})

	->get('/comptabilite/parametrer', function ($data) {

		throw new ViewAction($data);

	})
	->post('doInitialize', function($data) {

		\company\CompanyLib::enableAccounting($data->eFarm);

		throw new RedirectAction('/comptabilite/demarrer?farm='.$data->eFarm['id']);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\company\CompanyLib::initializeAccounting($data->eFarm, $_POST);

		$fw->validate();

		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal?onboarding&success=company\\Company::created');

	});

new Page()
	->get('/comptabilite/demarrer', function ($data) {

		throw new ViewAction($data);

	})
	->get('/comptabilite/beta', function ($data) {

		$data->eBetaApplication = \company\BetaApplicationLib::getApplicationByFarm($data->eFarm);

		throw new ViewAction($data, ':beta');

	});


new Page()
	->get('superpdp', function($data) {

		if(FEATURE_PDP === FALSE) {
			throw new NotExistsAction();
		}

		$code = GET('code');
		$state = GET('state');

		$eFarm = \pdp\ConnectionLib::retrieveToken($code, $state);

		throw new RedirectAction(Lime::getUrl().\farm\FarmUi::urlConnected($eFarm).'/pdp/?success=account\\Pdp::connected');

	});
