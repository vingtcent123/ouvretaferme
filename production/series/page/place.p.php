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
	->get('update', function($data) {

		if($data->source === 'series') {
			\series\SeriesLib::fillTimeline($data->e);
		}

		$data->e['farm'] = \farm\FarmLib::getById($data->e['farm']);

		// On récupère les emplacements
		$data->cZone = \map\ZoneLib::getByFarm($data->e['farm'], season: $data->e['season']);
		\map\PlotLib::putFromZoneWithSeries($data->e['farm'], $data->cZone, $data->e['season'], [$data->e['season'], $data->e['season'] - 1, $data->e['season'] + 1]);

		$data->cPlace = \series\PlaceLib::getByElement($data->e);

		switch($data->source) {

			case 'series' :

				$data->search = new Search([
					'canWidth' => \map\BedLib::countWidthsByFarm($data->e['farm'], $data->e['season']) > 1,
					'mode' => GET('mode', [NULL, \map\Plot::GREENHOUSE, \map\Plot::OPEN_FIELD], NULL),
				]);

				\map\ZoneLib::test($data->cZone, $data->search, $data->e);

				\series\SeriesLib::fillTimeline($data->e);

				break;

			case 'task' :
				$data->search = new Search();
				break;

		}

		\farm\ActionLib::getMainByFarm($data->e['farm']);

		throw new \ViewAction($data);

	})
	->post('doUpdate', function($data) {

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
?>
