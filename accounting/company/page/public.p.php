<?php
new \company\CompanyPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
	}
)
	->create(function ($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'));

		throw new ViewAction($data);

});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->canManage();
	}
)
	->post('doCreate', function($data) {

		$data->eCompany = \company\CompanyLib::getByFarm($data->eFarm);

		if($data->eCompany->notEmpty()) {
			throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/operations');
		}

		$fw = new FailWatch();

		\company\CompanyLib::createCompanyAndFinancialYear($data->eFarm, $_POST);

		$fw->validate();

		throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/operations?success=company:Company::created');

	});

