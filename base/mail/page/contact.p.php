<?php
new \mail\ContactPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \mail\Contact([
			'farm' => $data->eFarm
		]);

	})
	->create()
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCommunicationsMailing($data->eFarm).'?success=mail:Contact::created');
	});

new \mail\ContactPage()
	->doUpdateProperties('doUpdateActive', ['active'], fn($data) => throw new ViewAction($data))
	->doUpdateProperties('doUpdateNewsletter', ['newsletter'], fn($data) => throw new ViewAction($data))
	->doDelete(fn() => throw new ReloadAction());

new Page()
	->get('/ferme/{id}/optIn', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canRead');
		$data->consent = GET('consent', 'bool', TRUE);

		$data->email = get_exists('input') ?
			\main\CryptLib::decrypt(GET('input'), 'mail') :
			NULL;

		throw new ViewAction($data);

	})
	->get('export', function($data) {

		$search = \mail\ContactLib::getSearch();
		$search->set('export', TRUE);

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canRead');
		$data->cContact = \mail\ContactLib::getByFarm($data->eFarm, search: $search);

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

	})
	->post('query', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm', '?int'));

		if($data->eFarm->notEmpty()) {
			$data->eFarm->validate('canWrite');
		}

		$data->cContact = \mail\ContactLib::getFromQuery(POST('query'), $data->eFarm);

		$data->hasNew = post_exists('new');

		throw new \ViewAction($data);

	});
?>
