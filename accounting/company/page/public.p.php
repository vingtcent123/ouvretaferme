<?php
new Page(function($data) {
	\user\ConnectionLib::checkLogged();
	$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'));
	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting()) {
		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal');
	}

})
	->get('/comptabilite/inactive', function($data) {
		throw new ViewAction($data);
	})
	->get('/comptabilite/decouvrir', function ($data) {

		$data->eBetaApplication = \company\BetaApplicationLib::getApplicationByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get('/comptabilite/parametrer', function ($data) {

		throw new ViewAction($data);

	})
	->post('doInitialize', function($data) {

		\company\CompanyLib::initialize($data->eFarm);

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/company/configuration?success=company:Company::initialized');

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\company\CompanyLib::initializeAccounting($data->eFarm, $_POST);

		$fw->validate();

		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal?success=company:Company::created');

	});


