<?php
// Invitation d'un client
(new \farm\InvitePage(function($data) {

		$data->eCustomer = \selling\CustomerLib::getById(REQUEST('customer'))->validate('canManage');

	}))
	->getCreateElement(function($data) {

		return new \farm\Invite([
			'farm' => $data->eCustomer['farm'],
			'customer' => $data->eCustomer,
			'type' => \farm\Invite::CUSTOMER
		]);

	})
	->create(fn($data) => throw new ViewAction($data), page: 'createCustomer')
	->doCreate(function($data) {
		throw new ReloadAction('farm', 'Invite::customerCreated');
	}, page: 'doCreateCustomer');

(new \farm\InvitePage())
	->write('doExtends', function($data) {

		$data->e['farm'] = \farm\FarmLib::getById($data->e['farm']);

		\farm\InviteLib::extends($data->e);

		throw new ReloadAction('farm', 'Invite::extended');

	})
	->doDelete(fn() => throw new ReloadAction());

(new Page())
	->get('check', function($data) {

		$data->eInvite = \farm\InviteLib::getByKey(GET('key'));

		if($data->eInvite->empty()) {
			throw new ViewAction($data, ':check');
		}

		$data->eInvite['farm'] = \farm\FarmLib::getById($data->eInvite['farm']);

		if(\farm\InviteLib::accept($data->eInvite, \user\ConnectionLib::getOnline())) {
			throw new ViewAction($data, ':accept');
		} else {
			throw new ViewAction($data);
		}

	})
	->post('doAcceptUser', function($data) {

		$data->eInvite = \farm\InviteLib::getByKey(POST('key'));

		if(
			$data->eInvite->empty() or
			$data->eInvite['farmer']['user']->empty()
		) {
			throw new NotExpectedAction();
		}

		$data->eInvite['farm'] = \farm\FarmLib::getById($data->eInvite['farm']);

		$eUser = $data->eInvite['farmer']['user'];

		$fw = new FailWatch();

		$eUser->build(['email', 'password'], $_POST);

		user\SignUpLib::matchBasicPassword('create', $eUser, $_POST);

		$fw->validate();

		if(\farm\InviteLib::acceptUser($data->eInvite, $eUser)) {

			\user\ConnectionLib::logInUser($eUser);

			throw new RedirectAction('/');

		}

	});
?>
