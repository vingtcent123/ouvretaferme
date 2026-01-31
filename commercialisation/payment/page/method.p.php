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
	->quick(['name'])
	->update()
	->doUpdate(fn() => throw new ReloadAction('payment', 'Method::updated'))
	->doUpdateProperties('doUpdateStatus', ['status'], fn($data) => throw new ViewAction($data))
	->doDelete(fn() => throw new ReloadAction('payment', 'Method::deleted'));

new Page(function($data) {

	$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, FALSE);

		throw new ViewAction($data);

	});

?>
