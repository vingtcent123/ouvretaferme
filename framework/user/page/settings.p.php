<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eUserOnline['canUpdate'] = user\SignUpLib::canUpdate($data->eUserOnline);

	}))
	->get('updateUser', function($data) {
		throw new ViewAction($data);
	})
	->get('updateEmail', function($data) {

		if($data->eUserOnline['canUpdate']['email'] === FALSE) {
			throw new NotExpectedAction('Can\'t update email');
		}

		throw new ViewAction($data);

	})
	->get('updatePassword', function($data) {

		if($data->eUserOnline['canUpdate']['password'] === FALSE) {
			throw new NotExpectedAction();
		}

		throw new ViewAction($data);

	})
	->get('dropAccount', function($data) {

		if($data->eUserOnline['canUpdate']['drop'] === FALSE) {
			throw new NotExpectedAction();
		}

		$data->canCloseDelay = \user\DropLib::canClose();

		throw new ViewAction($data);

	});
?>
