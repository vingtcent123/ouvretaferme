<?php
new \selling\CategoryPage(function($data) {

		\user\ConnectionLib::checkLogged();

	})
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \selling\Category([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new ViewAction($data));

new \selling\CategoryPage()
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\selling\CategoryLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ViewAction($data));

new Page()
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cCategory = \selling\CategoryLib::getByFarm($data->eFarm);

		\farm\FarmerLib::setView('viewSellingProducts', $data->eFarm, \farm\Farmer::CATEGORY);

		throw new ViewAction($data);

	});
?>
