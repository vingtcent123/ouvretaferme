<?php
(new \farm\SupplierPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \farm\Supplier([
			'farm' => $data->eFarm,
		]);

	})
	->create()
	->doCreate(fn($data) => throw new ViewAction($data));

(new \farm\SupplierPage())
	->applyElement(function($data, \farm\Supplier $e) {

		$e->validate('canWrite');

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->quick(['name'])
	->update()
	->doUpdate(fn($data) => throw new ViewAction($data))
	->doDelete(fn($data) => throw new ViewAction($data));

(new Page())
	->post('query', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canWrite');

		$data->cSupplier = \farm\SupplierLib::getFromQuery(POST('query'), $eFarm);

		throw new \ViewAction($data);

	})
	->get('manage', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canManage');

		$data->search = new Search([
			'name' => GET('name')
		]);

		$data->cSupplier = \farm\SupplierLib::getByFarm($data->eFarm, $data->search);

		\farm\FarmerLib::register($data->eFarm);

		throw new \ViewAction($data);

	});
?>
