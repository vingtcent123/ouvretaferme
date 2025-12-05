<?php
new Page()
	/**
	 * Handle the post page which send the email confirmation mail.
	 */
	->post('doSend', function($data) {

		// Check that user is logged in
		user\ConnectionLib::checkLogged();

		$fw = new FailWatch();

		if(POST('user')) {
			$eUser = \user\UserLib::getById(POST('user'), ['id', 'email']);
		} else {
			$eUser = new \user\User();
		}

		if($eUser->empty()) {
			$eUser = $data->eUserOnline;
		}

		\user\UserLib::triggerSendVerifyEmail($eUser, FALSE);

		$fw->validate();

		throw new ReloadAction('user', 'User::forgottenPasswordSend');

	})
	/**
	 * Handle get call to /mail/verify:check
	 */
	->get('check', function($data) {

		/***
		 * Strange case where email is NULL but user has got a hash to verify the email
		 **/
		if(\user\ConnectionLib::isLogged() and $data->eUserOnline['email'] === NULL) {
			throw new RedirectAction('/');
		}

		$fw = new FailWatch();

		\user\EmailLib::validate(GET('hash'));

		if($fw->ok()) {

			throw new ViewAction($data);

		} else {

			$error = first($fw->get());
			$key = array_shift($error).':'.array_shift($error);
			throw new RedirectAction('/?error='.$key);

		}

	});

?>
