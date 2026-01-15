<?php
new Page(fn() => \user\ConnectionLib::getOnline()->checkIsAdmin())
	->match(['get', 'post'], 'index', function($data) {

		$data->page = REQUEST('page', 'int');

		$data->search = new Search([
			'account' => GET('account', '?string'),
			'id' => GET('id'),
			'lastName' => GET('lastName'),
			'email' => GET('email'),
		], REQUEST('sort'));

		[$data->cUser, $data->nUser] = \user\AdminLib::getUsers($data->page, $data->search);

		$data->cRole = \user\RoleLib::getByFqns(\user\UserSetting::$statsRoles);
		$data->cUserDaily = new \user\UserLib()->getDailyUsersStats($data->cRole);
		$data->cUserActive = new \user\UserLib()->getActiveUsersStats($data->cRole);

		$data->cAssociationHistory = \association\HistoryLib::countByYears([date('Y'), date('Y', strtotime('next year'))]);

		$data->isExternalConnected = \session\SessionLib::exists('userOld');

		throw new ViewAction($data);

	});

new \user\UserPage(
		fn() => \user\ConnectionLib::getOnline()->checkIsAdmin(),
		propertiesUpdate: ['email', 'firstName', 'lastName']
	)
	->read('forgottenPassword', function($data) {

		$data->expires = 7;
		$data->eUserAuth = user\UserLib::checkForgottenPasswordLink($data->e['email'], $data->expires);

		if($data->eUserAuth) {
			user\UserLib::updateForgottenPasswordLink($data->eUserAuth);
		}

		throw new ViewAction($data);

	})
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('user', 'User::adminUpdated');
	});
?>
