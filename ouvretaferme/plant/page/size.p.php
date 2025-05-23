<?php
new \farm\FarmPage()
	->read('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->ePlant = \plant\PlantLib::getById(GET('plant'))->validate('canRead');
		$data->cSize = \plant\SizeLib::getByFarmAndPlant($data->e, $data->ePlant);

		throw new \ViewAction($data);

	});

new \plant\SizePage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm', '?int'))->validate('canManage');
		$data->ePlant = \plant\PlantLib::getById(INPUT('plant'))->validate('canRead');

	})
	->getCreateElement(function($data) {
		return new \plant\Size([
			'farm' => $data->eFarm,
			'plant' => $data->ePlant
		]);
	})
	->create(fn($data) => throw new ViewAction($data))
	->doCreate(function($data) {
		throw new \ViewAction($data);

	});

new \plant\SizePage()
	->applyElement(function($data, \plant\Size $e) {

		$e->validate('canWrite');

		$data->ePlant = \plant\PlantLib::getById($e['plant']);

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->update()
	->doUpdate(function($data) {
		throw new ViewAction($data);

	})
	->doDelete(function($data) {
		throw new ViewAction($data);

	});
?>
