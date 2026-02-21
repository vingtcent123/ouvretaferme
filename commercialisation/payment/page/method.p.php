<?php
new \payment\MethodPage()
	->getCreateElement(function($data) {

		return new \payment\Method([
			'farm' => \farm\FarmLib::getById(INPUT('farm')),
		]);

	})
	->create()
	->doCreate(fn() => throw new ReloadAction('payment', 'Method::created'));

new \payment\MethodPage(function($data) {

	\user\ConnectionLib::checkLogged();

})
	->applyElement(function($data, \payment\Method $e) {

		$e->validate('canWrite');

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->update(function($data) {

		$data->e['cCustomerLimit'] = \selling\CustomerLib::getForRestrictions($data->e['limitCustomers']);
		$data->e['cCustomerExclude'] = \selling\CustomerLib::getForRestrictions($data->e['excludeCustomers']);

		$data->e['cGroupLimit'] = \selling\CustomerGroupLib::getForRestrictions($data->e['limitGroups']);
		$data->e['cGroupExclude'] = \selling\CustomerGroupLib::getForRestrictions($data->e['excludeGroups']);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('payment', 'Method::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doDelete(fn() => throw new ReloadAction('payment', 'Method::deleted'));

new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, FALSE);

		$data->cCustomer = \selling\CustomerLib::getRestrictedByCollection($data->cMethod);
		$data->cCustomerGroup = \selling\CustomerGroupLib::getRestrictedByCollection($data->cMethod);

		throw new ViewAction($data);

	});

?>
