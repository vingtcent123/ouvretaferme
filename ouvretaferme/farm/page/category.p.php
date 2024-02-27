<?php
(new \farm\CategoryPage(function($data) {

		\user\ConnectionLib::checkLogged();

	}))
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \farm\Category([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new ViewAction($data));

(new \farm\CategoryPage())
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->write('doIncrementPosition', function($data) {

		$increment = POST('increment', 'int');
		\farm\CategoryLib::incrementPosition($data->e, $increment);

		throw new ReloadAction();

	})
	->doDelete(fn($data) => throw new ViewAction($data));

(new Page())
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	});
?>
