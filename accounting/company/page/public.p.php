<?php
new \company\CompanyPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
	}
)
	->create(function ($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

		throw new ViewAction($data);

});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm = \farm\FarmLib::getById(POST('farm'));
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

/**
 * Callback après la connexion du compte Dropbox
 */
new Page()
	->get('dropbox', function($data) {

		$code = GET('code');
		$eFarm = \farm\FarmLib::getById(GET('state'));

		if($code === NULL or $eFarm->empty()) {
			throw new NotExistsAction();
		}

		\company\CompanyLib::connectSpecificDatabaseAndServer($eFarm);
		\account\DropboxLib::getAccessToken($eFarm, $code);

		throw new RedirectAction(\company\CompanyUi::url($eFarm).'/company:update?id='.$eFarm['id'].'&success=account:Partner::Dropbox.connected');
	});

