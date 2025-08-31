<?php
new Page(function($data) {

	\user\ConnectionLib::checkLogged();

	$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));
	$data->eFarm->validate('canManage');

	$data->eUser = \user\ConnectionLib::getOnline();

	$data->hasJoined = FALSE;

})
	->get('/ferme/{farm}/adherer', function($data) {

		$data->cHistory = \association\HistoryLib::getByFarm($data->eFarm);

		throw new ViewAction($data, ':adherer');

	});

new Page()
	->post('doCreatePayment', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		$fw = new FailWatch();

		$url = \association\MembershipLib::createPayment($data->eFarm, \association\History::MEMBERSHIP);

		$fw->validate();

		if($fw->ok()) {
			throw new RedirectAction($url);
		}

	})
	->post('doDonate', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		$fw = new FailWatch();

		$url = \association\MembershipLib::createPayment($data->eFarm, \association\History::DONATION);

		$fw->validate();

		if($fw->ok()) {
			throw new RedirectAction($url);
		}

	});
