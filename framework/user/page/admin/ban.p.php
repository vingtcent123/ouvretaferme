<?php
(new Page(function($data) {

		Feature::check('user\ban');

		Privilege::check('user\ban');

	}))
	/**
	 * Display all bans (ended or active).
	 */
	->get('index', function($data) {

		$data->active = GET('active', 'bool', FALSE);
		$userId = GET('user', 'int');
		$data->page = GET('page', 'int');

		if($userId !== 0) {
			$data->eUserSelected = \user\UserLib::getById($userId);
		} else {
			$data->eUserSelected = new \user\User();
		}

		list($data->cBan, $data->nPage) = \user\BanLib::getAll($data->active, $data->page, $data->eUserSelected);

		throw new ViewAction($data);

	})
	/**
	 * End a banishment.
	 */
	->post('doEnd', function($data) {

		$eBan = \user\BanLib::getById(POST('id'))->validate();

		$fw = new FailWatch();

		\user\BanLib::changeBanDuration($eBan, 0);

		$fw->validate();

		throw new ReloadAction();

	})
	/**
	 * Change a banishment end date.
	 */
	->get('updateEndDate', function($data) {

		$data->eBan = \user\BanLib::getById(GET('id'))->validate();

		throw new ViewAction($data);

	})
	->post('doUpdateEndDate', function($data) {

		$eBan = \user\BanLib::getById(POST('id'))->validate();
		$duration = POST('duration', 'int', -1);

		$fw = new FailWatch();

		\user\BanLib::changeBanDuration($eBan, $duration);

		$fw->validate();

		throw new ReloadAction();

	})
	/**
	 * Display panel form.
	 */
	->get('form', function($data) {

		$id = GET('userToBan', 'int');

		$data->eUserToBan = \user\UserLib::getById($id, ['id', 'email'])->validate();

		if($data->eUserToBan['id'] === $data->eUserOnline['id']) {
			throw new NotExpectedAction('You can\'t ban yourself');
		}

		$data->userToBanIp = \user\UserLib::getLastKnownIp($data->eUserToBan);

		if($data->userToBanIp !== NULL) {
			$data->nUserOnIp = \user\UserLib::countByIp($data->userToBanIp);
		} else {
			$data->nUserOnIp = 0;
		}

		throw new ViewAction($data);

	})
	/**
	 * Create a banishment.
	 */
	->post('do', function($data) {

		$user = POST('user', 'int');
		$type = POST('type', 'array');
		$reason = POST('reason');
		$duration = POST('duration', 'int', -1);

		$fw = new FailWatch();

		if($user !== 0) {
			$eUser = \user\UserLib::getById($user, ['id']);
		} else {
			$eUser = new \user\User();
		}

		\user\BanLib::createBan($eUser, $data->eUserOnline, $type, $reason, $duration, $fw);

		$fw->validate();

		$redirectPath = '/user/admin/ban?type=active';

		if($type !== 'ip') {
			$redirectPath .= '&user='.$eUser['id'];
		}

		throw new RedirectAction($redirectPath);

	});
?>
