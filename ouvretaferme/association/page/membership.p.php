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
		$data->hasJoinedForNextYear = $data->cHistory->contains(fn($e) => $e['paymentStatus'] === \selling\Payment::SUCCESS and $e['membership'] === nextYear());

		throw new ViewAction($data);

	})
	->get('/ferme/{farm}/donner', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		throw new ViewAction($data);

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

new Page()
	->get('/adherer', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eUser = \user\ConnectionLib::getOnline();
		$data->cFarmUser = \farm\FarmLib::getOnline();

		switch($data->cFarmUser->count()) {

			case 0 :
				throw new RedirectAction('/donner');

			case 1 :
				throw new RedirectAction(\farm\FarmUi::url($data->cFarmUser->first()).'/adherer');

			default:
				throw new ViewAction($data, ':adherer');

		}

	});
