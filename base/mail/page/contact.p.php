<?php
new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canCommunication');

	})
	->get('createCollection', fn($data) => throw new ViewAction($data))
	->post('doCreateCollection', function($data) {

		$eContactReference = new \mail\Contact([
			'farm' => $data->eFarm,
			'newsletter' => (bool)POST('newsletter')
		]);

		$cContact = new Collection();

		$emails = POST('emails') ? preg_split("/\r?\n+/", POST('emails')) : [];

		$fw = new \FailWatch();

		foreach($emails as $email) {

			$eContact = clone $eContactReference;
			$eContact->build(['email'], ['email' => $email], new \Properties('create'));


			$cContact[] = $eContact;

		}

		$fw->validate();

		if($cContact->empty()) {
			throw new FailAction('mail\Contact::email.empty');
		}

		\mail\Contact::model()->beginTransaction();

			foreach($cContact as $eContact) {

				\mail\ContactLib::create($eContact);

 			}

		\mail\Contact::model()->commit();

		throw new RedirectAction(
			$cContact->count() > 1 ?
				\farm\FarmUi::urlCommunicationsContact($data->eFarm).'?success=mail\\Contact::createdCollection' :
				\farm\FarmUi::urlCommunicationsContact($data->eFarm).'?success=mail\\Contact::created'
		);

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

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canCommunication');

		$search = \mail\ContactLib::getSearch($data->eFarm, $_GET);
		$search->set('export', TRUE);

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
