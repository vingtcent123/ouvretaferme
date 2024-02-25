<?php
(new Page(fn() => Privilege::check('user\admin')))
	->match(['get', 'post'], 'index', function($data) {

		$data->page = REQUEST('page', 'int');

		$data->search = new Search([
			'account' => GET('account', '?string'),
			'id' => GET('id'),
			'lastName' => GET('lastName'),
			'email' => GET('email'),
		], REQUEST('sort'));

		[$data->cUser, $data->nUser] = \user\AdminLib::getUsers($data->page, $data->search);

		$data->cRole = \user\RoleLib::getByFqns(Setting::get('user\statsRoles'));
		$data->cUserDaily = (new \user\UserLib())->getDailyUsersStats($data->cRole);
		$data->cUserActive = (new \user\UserLib())->getActiveUsersStats($data->cRole);

		$data->isExternalConnected = \session\SessionLib::exists('userOld');

		throw new ViewAction($data);

	});

(new \user\UserPage(
		function($data) {
			Privilege::check('user\admin');
		},
		propertiesUpdate: ['email', 'birthdate', 'firstName', 'lastName']
	))
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
