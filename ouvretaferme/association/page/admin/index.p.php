<?php
new \association\HistoryPage(
	fn() => \user\ConnectionLib::getOnline()->checkIsAdmin(),
)
	->create(function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'));
		$data->cHistory = \association\HistoryLib::getByFarm($data->eFarm);
		$data->cMethod = \payment\MethodLib::getByFqns([\payment\MethodLib::CARD, \payment\MethodLib::CASH, \payment\MethodLib::CHECK, \payment\MethodLib::TRANSFER, \payment\MethodLib::DIRECT_DEBIT]);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'));

		$fw = new FailWatch();

		\association\MembershipLib::createPayment($data->eFarm, post('type'), TRUE);

		$fw->validate();

		POST('type') === \association\History::MEMBERSHIP ? throw new ReloadAction('association', 'History::adminMembershipCreated') : throw new ReloadAction('association', 'History::adminCreated');
	});
