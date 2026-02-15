<?php
new \association\HistoryPage(
	fn() => \user\ConnectionLib::getOnline()->checkIsAdmin(),
)
	->create(function($data) {

		$data->eFarmOtf = \farm\FarmLib::getById(\association\AssociationSetting::FARM);
		$data->eFarm = \farm\FarmLib::getById(GET('id'));

		$data->cHistory = \association\HistoryLib::getByFarm($data->eFarm);
		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarmOtf, NULL);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'));

		$fw = new FailWatch();

		\association\MembershipLib::createPayment($data->eFarm, post('type'), TRUE);

		$fw->validate();

		if(POST('type') === \association\History::MEMBERSHIP) {
			throw new ReloadAction('association', 'History::adminMembershipCreated');
		}

		throw new ReloadAction('association', 'History::adminCreated');

	});
