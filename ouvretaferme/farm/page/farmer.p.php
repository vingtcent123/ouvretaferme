<?php
(new Page())
	->get('manage', function($data) {

		$farm = GET('farm', '?int');

		$data->eFarm = \farm\FarmLib::getById($farm)->validate('canManage');

		\farm\FarmerLib::register($data->eFarm);
		\farm\FarmerLib::setView('viewPlanning', $data->eFarm, \farm\Farmer::TEAM);

		$data->cFarmer = \farm\FarmerLib::getByFarm($data->eFarm);
		$data->cFarmerInvite = \farm\FarmerLib::getByFarm($data->eFarm, onlyInvite: TRUE);
		$data->cFarmerGhost = \farm\FarmerLib::getByFarm($data->eFarm, onlyGhost: TRUE);

		$cUser = $data->cFarmer->getColumnCollection('user');
		\hr\PresenceLib::fillUsers($data->eFarm, $cUser);

		throw new ViewAction($data);

	})
	->get('show', function($data) {

		$data->eFarmer = \farm\FarmerLib::getById(GET('id'))->validate('canWrite');
		$data->eFarm = \farm\FarmLib::getById($data->eFarmer['farm']);

		\farm\FarmerLib::register($data->eFarm);
		\farm\FarmerLib::setView('viewPlanning', $data->eFarm, \farm\Farmer::TEAM);

		$data->cPresence = \hr\PresenceLib::getByUser($data->eFarmer['farm'], $data->eFarmer['user']);
		$data->cAbsence = \hr\AbsenceLib::getByUser($data->eFarmer['farm'], $data->eFarmer['user']);

		throw new ViewAction($data);

	});

(new Page(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		\user\ConnectionLib::checkLogged();

	}))
	->get('createUser', fn($data) => throw new ViewAction($data))
	->post('doCreateUser', function($data) {

		$fw = new FailWatch();

		$eUser = new \user\User([
			'email' => NULL,
			'visibility' => \user\User::PRIVATE,
			'role' => \user\RoleLib::getByFqn('farmer')
		]);

		$eUser->build(['firstName', 'lastName'], $_POST);

		$fw->validate();

		\farm\FarmerLib::createGhostUser($data->eFarm, $eUser);

		throw new BackAction('farm', 'Farmer::userCreated');

	})
	->get('updateUser', function($data) {

		$data->eUserOnline = \user\UserLib::getById(GET('user'))->validate('isPrivate');

		if(\farm\FarmerLib::isFarmer($data->eUserOnline, $data->eFarm, NULL) === FALSE) {
			throw new NotAllowedAction('Not farmer');
		}

		throw new ViewAction($data);

	})
	->post('doUpdateUser', function($data) {

		$eUser = \user\UserLib::getById(POST('user'))->validate('isPrivate');

		if(\farm\FarmerLib::isFarmer($eUser, $data->eFarm, NULL) === FALSE) {
			throw new NotAllowedAction('Not farmer');
		}

		$fw = new FailWatch();

		$eUser->build(['firstName', 'lastName'], $_POST);

		$fw->validate();

		\user\UserLib::update($eUser, ['firstName', 'lastName']);

		throw new BackAction('farm', 'Farmer::userUpdated');

	})
	->post('doDeleteUser', function($data) {

		$eUser = \user\UserLib::getById(POST('user'))->validate('isPrivate');

		\farm\FarmerLib::deleteGhostUser($data->eFarm, $eUser);

		throw new ReloadAction('farm', 'Farmer::userDeleted');

	});

(new \farm\FarmerPage(function($data) {

		\user\ConnectionLib::checkLogged();

		if(OTF_DEMO) {
			throw new \FailAction('user\User::demo.write');
		}

	}))
	->getCreateElement(function($data) {

		return new \farm\Farmer([
			'farm' => \farm\FarmLib::getById(INPUT('farm'))
		]);

	})
	->create(function($data) {

		$data->eFarmerLink = \farm\FarmerLib::getById(GET('farmer'));

		if(
			$data->eFarmerLink->notEmpty() and
			$data->eFarmerLink['farm']['id'] !== $data->e['farm']['id']
		) {
			throw new NotExpectedAction('Inconsistency');
		}

		throw new ViewAction($data);

	})
	->doCreate(fn($data) => throw new RedirectAction('/farm/farmer:manage?farm='.$data->e['farm']['id'].'&success=farm:Farmer::created'))
	->write('doDeleteInvite', function($data) {

		\farm\InviteLib::deleteFromFarmer($data->e);

		throw new RedirectAction('/farm/farmer:manage?farm='.$data->e['farm']['id'].'&success=farm:Invite::deleted');

	})
	->doUpdateProperties('doUpdateStatus', ['status'], function($data) {
		$eFarm = \farm\FarmLib::getById($data->e['farm']);
		throw new RedirectAction(\farm\FarmUi::urlPlanningTeam($eFarm).'&success=farm:'.($data->e['status'] === \farm\Farmer::IN ? 'Farmer::created' : 'Farmer::deleted'));
	})
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new RedirectAction('/farm/farmer:manage?farm='.$data->e['farm']['id'].'&success=farm:Farmer::deleted'));
?>
