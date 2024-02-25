<?php
(new Page(function($data) {

		user\ConnectionLib::checkLogged();

		$data->eUserOnline = \user\UserLib::getById($data->eUserOnline['id']);
		$data->can = \user\DropLib::canClose();

	}))
	/**
	 * Form to close its account
	 */
	->get('index', fn($data) => throw new ViewAction($data))
	/**
	 * Close account
	 */
	->post('do', function($data) {

		if($data->eUserOnline['deletedAt'] === NULL and $data->can === FALSE) {
			throw new NotExpectedAction('Too late to close account', new RedirectAction('/user/close'));
		}

		user\DropLib::changeClose($data->eUserOnline);

		throw new ReloadAction();

	});
?>
