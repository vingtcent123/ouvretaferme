<?php
new \payment\MethodPage()
	->getCreateElement(function($data) {

		return new \payment\Method([
			'farm' => \farm\FarmLib::getById(INPUT('farm')),
		]);

	})
	->create()
	->doCreate(fn() => throw new ReloadAction('payment', 'Method::created'));

new \payment\MethodPage()
	->quick(['name'])
	->update()
	->doUpdate(fn() => throw new ReloadAction('payment', 'Method::updated'))
	->doDelete(fn() => throw new ReloadAction('payment', 'Method::deleted'));

new Page(function($data) {

	$farm = GET('farm', '?int');

	$data->eFarm = \farm\FarmLib::getById($farm)->validate('canManage');

})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cMethod = \payment\MethodLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	});

?>
