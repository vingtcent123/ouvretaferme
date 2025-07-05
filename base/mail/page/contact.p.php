<?php
new \mail\ContactPage()
	->doUpdateProperties('doUpdateOptOut', ['optOut'], fn($data) => throw new ViewAction($data));

new Page()
	->get('/ferme/{id}/optIn', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canRead');
		$data->consent = GET('consent', 'bool', TRUE);

		$data->email = get_exists('input') ?
			\main\CryptLib::decrypt(GET('input'), 'mail') :
			NULL;

		throw new ViewAction($data);

	})
	->post('doUpdateOptInByEmail', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('id'))->validate('canRead');
		$data->consent = POST('consent', 'bool');

		\mail\ContactLib::updateOptInByEmail($eFarm, POST('email'), $data->consent);

		throw new ViewAction($data);

	})
	->get('updateOptIn', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->cContact = \mail\ContactLib::getByUser($data->eUserOnline);

		throw new ViewAction($data);

	})
	->post('doUpdateOptIn', function($data) {

		\user\ConnectionLib::checkLogged();

		\mail\ContactLib::updateOptIn($data->eUserOnline, POST('farms', 'array'));

		throw new ReloadAction('selling', 'Customer::optInUpdated');

	});
?>
