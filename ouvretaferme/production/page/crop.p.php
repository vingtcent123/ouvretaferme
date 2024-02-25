<?php
(new \production\CropPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eSequence = \production\SequenceLib::getById(INPUT('sequence'))->validate('canWrite');

	}))
	->post('addPlant', function($data) {

		$data->ePlant = \plant\PlantLib::getById(POST('plant'));
		$data->ccVariety = \plant\VarietyLib::query($data->eSequence['farm'], $data->ePlant);

		throw new \ViewAction($data);

	})
	->getCreateElement(function($data) {
		return new \production\Crop([
			'farm' => $data->eSequence['farm'],
			'sequence' => $data->eSequence
		]);
	})
	->create()
	->doCreate(fn() => throw new ReloadAction());

(new \production\CropPage())
	->applyElement(function($data, \production\Crop $e) {
		$e['sequence'] = \production\SequenceLib::getById($e['sequence'])->validate('canWrite');
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

		throw new ViewAction($data);

	})
	->quick(['yieldExpected', 'seedlingSeeds'])
	->doUpdate(function($data) {
		throw new ReloadAction('production', 'Crop::updated');
	})
	->doDelete(fn() => throw new ReloadAction());

(new Page())
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
