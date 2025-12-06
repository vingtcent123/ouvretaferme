<?php
new Page(function($data) {
	\user\UserSetting::checkFeature(\user\UserSetting::$featureSignUp);
})
	/**
	 * Display sign up form
	 */
	->get('index', function($data) {

		$data->eUser = new \user\User([
			'invoiceCountry' => \user\UserLib::getDefaultCountry(),
			'cCountry' => \user\CountryLib::getForSignUp()
		]);

		$data->cRole = \user\RoleLib::getForSignUp();

		if(
			get_exists('role') and
			$data->cRole->offsetExists(GET('role'))
		) {
			$data->eRole = $data->cRole[GET('role')];
		} else {
			$data->eRole = new \user\Role();
		}

		user\ConnectionLib::checkAnonymous();

		user\ConnectionLib::loadSignUp($data);

		throw new ViewAction($data, path: \user\UserSetting::$signUpView);

	})
	/**
	 * Run sign up
	 */
	->post('doCreate', function($data) {

		$fw = new FailWatch;

		$redirect = POST('redirect', '?string');

		$eUser = new \user\User();

		user\SignUpLib::match(user\UserAuth::BASIC, $eUser, $_POST);
		user\SignUpLib::matchBasicPassword('create', $eUser, $_POST);
		user\SignUpLib::checkTos($_POST);

		$fw->validate();

		user\SignUpLib::create($eUser, FALSE);

		$fw->validate();

		user\ConnectionLib::logInUser($eUser, POST('remember', 'bool'));

		if($redirect) {
			if(strpos($redirect, '?') === FALSE) {
				$redirect .= '?';
			}
			throw new RedirectAction($redirect.'&success=user:User::welcomeCreate');
		}

		throw new RedirectAction(Lime::getUrl().'?success=user:User::welcomeCreate');

	});
?>
