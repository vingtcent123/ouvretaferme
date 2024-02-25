<?php
(new \plant\ForecastPage(function($data) {

		$data->season = INPUT('season', 'int');

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm', '?int'))
			->validate('canAnalyze')
			->validateSeason($data->season);


	}))
	->getCreateElement(function($data) {
		return new \plant\Forecast([
			'farm' => $data->eFarm,
			'season' => $data->season
		]);
	})
	->create()
	->doCreate(fn($data) => throw new ReloadAction('plant', 'Forecast::created'));

(new \plant\ForecastPage())
	->quick(['harvestObjective', 'proPrice', 'privatePart', 'privatePrice', 'proPart'])
	->update(function($data) {

		$data->e['nCultivation'] = \plant\ForecastLib::countCultivations($data->e);

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ReloadAction('plant', 'Forecast::updated'))
	->doDelete(fn($data) => throw new ReloadAction('plant', 'Forecast::deleted'));
?>
