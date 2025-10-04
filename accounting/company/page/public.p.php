<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm = \farm\FarmLib::getById(REQUEST('farm'));
		$data->eFarm->validate('canManage');
		if($data->eFarm['hasAccounting']) {
			throw new RedirectAction(\company\CompanyUi::urlJournal($data->eFarm).'/operations');
		}
	}
)
	->get('inactive', function($data) {
		throw new ViewAction($data);
	})
	->get('create', function ($data) {

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\company\CompanyLib::initializeAccounting($data->eFarm, $_POST);

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

