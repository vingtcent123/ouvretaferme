<?php
new \association\HistoryPage(
	fn() => \user\ConnectionLib::getOnline()->checkIsAdmin(),
)
	->create(function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('id'));
		$data->cHistory = \association\HistoryLib::getByFarm($data->eFarm);
		$data->cMethod = \payment\MethodLib::getByFqns(['card', 'online-card', 'cash', 'check', 'transfer', 'direct-debit']);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'));

		$fw = new FailWatch();

		\association\MembershipLib::createPayment($data->eFarm, post('type'), TRUE);

		$fw->validate();

		throw new ReloadAction('association', 'History::adminCreated');
	});
