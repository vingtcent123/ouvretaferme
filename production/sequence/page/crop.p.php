<?php
new \sequence\CropPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eSequence = \sequence\SequenceLib::getById(INPUT('sequence'))->validate('canWrite');

	})
	->post('addPlant', function($data) {

		$data->ePlant = \plant\PlantLib::getById(POST('plant'));
		$data->ccVariety = \plant\VarietyLib::query($data->eSequence['farm'], $data->ePlant);

		$data->cAction = \farm\ActionLib::getByFarm($data->eSequence['farm'], fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		throw new \ViewAction($data);

	})
	->getCreateElement(function($data) {
		return new \sequence\Crop([
			'farm' => $data->eSequence['farm'],
			'sequence' => $data->eSequence
		]);
	})
	->create()
	->doCreate(fn() => throw new ReloadAction());

new \sequence\CropPage()
	->applyElement(function($data, \sequence\Crop $e) {
		$e['sequence'] = \sequence\SequenceLib::getById($e['sequence'])->validate('canWrite');
	})
	->read('changePlant', function($data) {

		$data->ePlant = \plant\PlantLib::getById(POST('plant'));

		if($data->ePlant->notEmpty()) {

			$data->ePlant->validate('canRead');
			$data->ccVariety = \plant\VarietyLib::query($data->e['farm'], $data->ePlant);

			// On récupère les répartitions actuelles des variétés si l'espèce recherchée est celle actuellement utilisée
			if($data->ePlant['id'] === $data->e['plant']['id']) {
				$data->cSlice = $data->e['cSlice'];
			} else {
				$data->cSlice = new Collection();
			}

		} else {
			$data->ccVariety = new Collection();
			$data->cSlice = new Collection();
		}

		throw new \ViewAction($data);

	}, method: 'post')
	->update(function($data) {

		$data->e['ccVariety'] = \plant\VarietyLib::query($data->e['farm'], $data->e['plant']);

		if($data->e['seedling'] === NULL) {

			$data->cAction = \farm\ActionLib::getByFarm($data->e['farm'], fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

			if(\sequence\FlowLib::hasCropActions($data->e, $data->cAction)) {
				$data->cAction = new Collection();
			}

		} else {
			$data->cAction = new Collection();
		}

		throw new ViewAction($data);

	})
	->quick(['yieldExpected', 'seedlingSeeds'])
	->doUpdate(function($data) {
		throw new ReloadAction('sequence', 'Crop::updated');
	})
	->doDelete(fn() => throw new ReloadAction());

new Page()
	->post('changePlant', function($data) {

		$data->ePlant = \plant\PlantLib::getById(POST('plant'));

		if($data->ePlant->notEmpty()) {

			$data->ePlant->validate('canRead');
			$data->ccVariety = \plant\VarietyLib::query($data->ePlant['farm'], $data->ePlant);

			// On récupère les répartitions actuelles des variétés si l'espèce recherchée est celle actuellement utilisée
			if($data->ePlant['id'] === $data->e['plant']['id']) {
				$data->cSlice = $data->e['cSlice'];
			} else {
				$data->cSlice = new Collection();
			}

		} else {
			$data->ccVariety = new Collection();
			$data->cSlice = new Collection();
		}

		throw new \ViewAction($data);

	});
?>
