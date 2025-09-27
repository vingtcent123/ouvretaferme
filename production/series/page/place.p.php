<?php
new Page(function($data) {

		if(input_exists('cultivation')) {

			$data->eCultivation = \series\CultivationLib::getById(INPUT('cultivation'))->validate('canWrite');

			$data->e = $data->eCultivation['series'];
			$data->e['farm'] = \farm\FarmLib::getById($data->eCultivation['farm']);

			$data->source = 'cultivation';

		} else if(input_exists('series')) {

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

	})
	->get('update', function($data) {

		if(
			$data->source === 'series' or
			$data->source === 'cultivation'
		) {
			\series\SeriesLib::fillTimeline($data->e);
		}

		// On récupère les emplacements
		$data->cZone = \map\ZoneLib::getByFarm($data->e['farm'], season: $data->e['season']);

		\map\GreenhouseLib::putFromZone($data->cZone);
		\map\PlotLib::putFromZoneWithSeries($data->e['farm'], $data->cZone, $data->e['season'], [$data->e['season'], $data->e['season'] - 1, $data->e['season'] + 1]);

		$data->e['cPlace'] = \series\PlaceLib::getByElement($data->e);

		\farm\ActionLib::getMainByFarm($data->e['farm']);

		switch($data->source) {

			case 'cultivation' :
			case 'series' :

				$data->search = new Search([
					'canWidth' => \map\BedLib::countWidthsByFarm($data->e['farm'], $data->e['season']) > 1,
					'mode' => GET('mode', [NULL, \map\Plot::GREENHOUSE, \map\Plot::OPEN_FIELD]),
				]);

				\map\ZoneLib::test($data->cZone, $data->search, $data->e);

				throw new \ViewAction($data, match($data->source) {
					'cultivation' => ':updateCultivation',
					'series' => ':updateModal',
				});

			case 'task' :

				$data->search = new Search();

				throw new \ViewAction($data, ':updateModal');

		}


	})
	->post('doUpdate', function($data) {

		$fw = new \FailWatch();

		$cPlace = \series\PlaceLib::buildFromBeds(
			match($data->source) {
				'series', 'cultivation' => 'series',
				'task' => 'task',
			},
			$data->e,
			POST('beds', 'array'),
			POST('sizes', 'array')
		);

		if($fw->ok()) {

			match($data->source) {
				'series', 'cultivation' => \series\PlaceLib::replaceForSeries($data->e, $cPlace),
				'task' => \series\PlaceLib::replaceForTask($data->e, $cPlace),
			};
		}

		$fw->validate();

		if($data->source === 'cultivation') {

			\farm\ActionLib::getMainByFarm($data->e['farm']);

			\series\SeriesLib::fillTimeline($data->e);

			$data->cZone = \map\ZoneLib::getByFarm($data->e['farm'], season: $data->e['season']);

			\map\GreenhouseLib::putFromZone($data->cZone);
			\map\PlotLib::putFromZoneWithSeries($data->e['farm'], $data->cZone, $data->e['season'], [$data->e['season'], $data->e['season'] - 1, $data->e['season'] + 1]);

			$data->ccCultivation = \series\CultivationLib::getForSelector($data->e['farm'], $data->e['season']);

		} else {
			$data->cPlace = \series\PlaceLib::getByElement($data->e);
		}

		throw new \ViewAction($data);

	});
?>
