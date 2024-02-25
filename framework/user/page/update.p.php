<?php
(new Page(function($data) {
	
		user\ConnectionLib::checkLogged();
	
	}))
	->post('doUpdate', function($data) {

		$eUser = \user\ConnectionLib::getOnline();
		$properties = \user\UserLib::getPropertiesUpdate();

		$fw = new \FailWatch();

		$eUser->build($properties, $_POST, for: 'update');

		$fw->validate();

		\user\UserLib::update($eUser, $properties);

		$fw->validate();

		throw new ReloadAction('user', 'User::updated');

	})
	/**
	 * Form to change its email
	 */
	->get('email', function($data) {

		if(user\SignUpLib::canUpdate($data->eUserOnline)['email'] === FALSE) {
			throw new NotExpectedAction('Can not update email for this user');
		}

		match(Route::getRequestedWith()) {
			'api' => throw new ViewAction($data, path: ':email.api'),
			default => throw new ViewAction($data)
		};

	})
	/**
	 * E-mail address verified!
	 */
	->get('emailVerified', fn($data) => throw new ViewAction($data))
	/**
	 * Change email
	 */
	->post('doEmail', function($data) {

		if(user\SignUpLib::canUpdate($data->eUserOnline)['email'] === FALSE) {
			throw new NotExpectedAction('Can not update email for this user');
		}

		$fw = new FailWatch;

		$eUser = user\ConnectionLib::getOnline();
		$eUser->buildEmail(user\UserAuth::BASIC, $_POST);

		$fw->validate();

		user\SignUpLib::updateEmail($eUser);

		$fw->validate();

		throw new ReloadAction('user', 'User::emailUpdated');

	})
	/**
	 * Form to change its password
	 */
	->get('password', function($data) {

		if(user\SignUpLib::canUpdate($data->eUserOnline)['password'] === FALSE) {
			throw new NotExpectedAction('Can not update password for this user');
		}

		throw new ViewAction($data);

	})
	/**
	 * Change password
	 */
	->post('doPassword', function($data) {

		$fw = new FailWatch;

		$eUser = \user\ConnectionLib::getOnline();

		user\SignUpLib::matchBasicPassword('update', $eUser, $_POST);

		$fw->validate();

		user\SignUpLib::updatePassword($eUser);

		$fw->validate();

		throw new ReloadAction('user', 'User::passwordUpdated');

	});
?>
