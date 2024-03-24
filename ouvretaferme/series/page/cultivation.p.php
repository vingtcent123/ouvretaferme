<?php
(new \series\CultivationPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eSeries = \series\SeriesLib::getById(INPUT('series'))->validate('canWrite');

	}))
	->post('addPlant', function($data) {

		$data->ePlant = \plant\PlantLib::getById(POST('plant'));
		$data->ccVariety = \plant\VarietyLib::query($data->eSeries['farm'], $data->ePlant);
		$data->cAction = \farm\ActionLib::getByFarm($data->eSeries['farm'], fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		throw new \ViewAction($data);

	})
	->getCreateElement(function($data) {

		return new \series\Cultivation([
			'farm' => $data->eSeries['farm'],
			'series' => $data->eSeries,
			'season' => $data->eSeries['season'],
			'area' => $data->eSeries['area'],
			'length' => $data->eSeries['length']
		]);

	})
	->create()
	->doCreate(fn() => throw new ReloadAction());

(new \series\CultivationPage())
	->applyElement(function($data, \series\Cultivation $e) {

		$e->validate('canWrite');

	})
	->quick(['yieldExpected'])
	->read('harvest', function($data) {

		$data->cTask = \series\TaskLib::getHarvestedByCultivation($data->e);
		\series\CultivationLib::populateSliceStats($data->e);

		throw new ViewAction($data);

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
	->doUpdate(fn() => throw new ReloadAction())
	->doDelete(function($data) {
		throw new ReloadAction('series', 'Cultivation::deleted');
	});
?>
