<?php
(new \farm\FarmPage())
	->read('index', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->ePlant = \plant\PlantLib::getById(GET('plant'))->validate('canRead');
		$data->cVariety = \plant\VarietyLib::getByFarmAndPlant($data->e, $data->ePlant);

		$data->cSupplier = \farm\SupplierLib::getByFarm($data->e);

		throw new \ViewAction($data);

	});

(new \plant\VarietyPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm', '?int'))->validate('canManage');
		$data->ePlant = \plant\PlantLib::getById(INPUT('plant'))->validate('canRead');

	}))
	->getCreateElement(function($data) {
		return new \plant\Variety([
			'farm' => $data->eFarm,
			'plant' => $data->ePlant
		]);
	})
	->create(function($data) {

		$data->cSupplier = \farm\SupplierLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new \ViewAction($data);

	});

(new \plant\VarietyPage())
	->applyElement(function($data, \plant\Variety $e) {

		$e->validate('canWrite');

		$data->ePlant = \plant\PlantLib::getById($e['plant']);

		$data->eFarm = $e['farm'];

		\farm\Farm::model()
			->select('status', 'name')
			->get($data->eFarm);

		$data->eFarm->validate('active');

	})
	->quick(['supplierSeed', 'supplierPlant', 'weightSeed1000', 'numberPlantKilogram'], [
		'supplierSeed' => function($data) {
			$data->e['cSupplier'] = \farm\SupplierLib::getByFarm($data->e['farm']);
		},
		'supplierPlant' => function($data) {
			$data->e['cSupplier'] = \farm\SupplierLib::getByFarm($data->e['farm']);
		}
	])
	->update(function($data) {

		$data->e['cSupplier'] = \farm\SupplierLib::getByFarm($data->e['farm']);

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new ViewAction($data);

	})
	->doDelete(function($data) {
		throw new ViewAction($data);

	});
?>
