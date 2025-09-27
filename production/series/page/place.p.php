<?php
(new Page(function($data) {

	if(input_exists('series')) {

		$data->e = \series\SeriesLib::getById(INPUT('series'))->validate('canWrite');
		$data->e['farm'] = \farm\FarmLib::getById($data->e['farm']);

		$data->source = 'series';

	} else if(input_exists('task')) {

		$data->e = \series\TaskLib::getById(INPUT('task'))->validate('canWrite', 'acceptSoil');
		$data->e['farm'] = \farm\FarmLib::getById($data->e['farm']);
		$data->e['season'] = week_year($data->e['doneWeek'] ?? $data->e['plannedWeek']);
		$data->e['use'] = \series\Series::BED;
		$data->e['bedWidth'] = NULL;

		$data->source = 'task';

	} else {
		throw new NotExpectedAction('Missing entry');
	}

}))
	->get('updateModal', function($data) {

		if($data->source === 'series') {
			\series\SeriesLib::fillTimeline($data->e);
		}

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);

		// On récupère les emplacements
		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->e['season']);

		\map\GreenhouseLib::putFromZone($data->cZone);
		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->e['season'], [$data->e['season'], $data->e['season'] - 1, $data->e['season'] + 1]);

		$data->e['cPlace'] = \series\PlaceLib::getByElement($data->e);

		switch($data->source) {

			case 'series' :

				$data->search = new Search([
					'canWidth' => \map\BedLib::countWidthsByFarm($data->eFarm, $data->e['season']) > 1,
					'mode' => GET('mode', [NULL, \map\Plot::GREENHOUSE, \map\Plot::OPEN_FIELD]),
				]);

				\map\ZoneLib::test($data->cZone, $data->search, $data->e);

				break;

			case 'task' :
				$data->search = new Search();
				break;

		}

		\farm\ActionLib::getMainByFarm($data->eFarm);

		throw new \ViewAction($data);

	})
	->post('doUpdateModal', function($data) {

		$fw = new \FailWatch();

		$cPlace = \series\PlaceLib::buildFromBeds($data->source, $data->e, POST('beds', 'array'), POST('sizes', 'array'));

		if($fw->ok()) {

			match($data->source) {
				'series' => \series\PlaceLib::replaceForSeries($data->e, $cPlace),
				'task' => \series\PlaceLib::replaceForTask($data->e, $cPlace),
			};
		}

		$fw->validate();

		$data->cPlace = \series\PlaceLib::getByElement($data->e);

		throw new \ViewAction($data);

	});


new \series\CultivationPage()
	->applyElement(function($data, \series\Cultivation $e) {

		$data->eSeries = $e['series'];

		$data->eFarm = \farm\FarmLib::getById($e['farm']);
		$data->season = $e['season'];

		\farm\ActionLib::getMainByFarm($data->eFarm);

	})
	->read('updateSoil', function($data) {

		\series\SeriesLib::fillTimeline($data->eSeries);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		\map\GreenhouseLib::putFromZone($data->cZone);
		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, [$data->season, $data->season - 1, $data->season + 1]);

		$data->eSeries['cPlace'] = \series\PlaceLib::getByElement($data->eSeries);


		throw new \ViewAction($data);

	})
	->write('doUpdateSoil', function($data) {

		$fw = new \FailWatch();

		$cPlace = \series\PlaceLib::buildFromBeds($data->source, $data->e, POST('beds', 'array'), POST('sizes', 'array'));

		if($fw->ok()) {

			match($data->source) {
				'series' => \series\PlaceLib::replaceForSeries($data->e, $cPlace),
				'task' => \series\PlaceLib::replaceForTask($data->e, $cPlace),
			};
		}

		$fw->validate();

		$data->cPlace = \series\PlaceLib::getByElement($data->e);

		throw new \ViewAction($data);

	})
	->write('doDeleteSoil', function($data) {

		$data->eSeries['cPlace'] = new Collection();

		\series\PlaceLib::replaceForSeries($data->eSeries, $data->eSeries['cPlace']);

		\series\SeriesLib::fillTimeline($data->eSeries);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		\map\GreenhouseLib::putFromZone($data->cZone);
		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, [$data->season, $data->season - 1, $data->season + 1]);

		$data->ccCultivation = \series\CultivationLib::getForSelector($data->eFarm, $data->season);

		throw new \ViewAction($data);

	});
?>
