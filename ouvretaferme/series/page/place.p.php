<?php
(new Page(function($data) {

		$data->eSeries = \series\SeriesLib::getById(INPUT('series'))
			->validate('canWrite');

	}))
	->get('update', function($data) {

		\series\SeriesLib::fillTimeline($data->eSeries);

		$data->eSeries['farm'] = \farm\FarmLib::getById($data->eSeries['farm']);

		$data->cZone = \map\ZoneLib::getByFarm($data->eSeries['farm'], season: $data->eSeries['season']);
		\map\PlotLib::putFromZoneWithSeries($data->eSeries['farm'], $data->cZone, $data->eSeries['season'], [$data->eSeries['season'], $data->eSeries['season'] - 1, $data->eSeries['season'] + 1]);

		$data->cPlace = \series\PlaceLib::getBySeries($data->eSeries);

		$hasAlternativeBedWidth = $data->cPlace->match(fn($ePlace) => $ePlace['bed']['width'] !== $data->eSeries['bedWidth']);

		$data->search = new Search([
			'canAll' => ($hasAlternativeBedWidth === FALSE),
			'all' => GET('all', 'bool', $hasAlternativeBedWidth),
			'available' => GET('available', 'int', 0),
			'rotation' => GET('rotation', 'int', 0)
		]);

		\map\ZoneLib::filter($data->cZone, $data->search, $data->eSeries);

		throw new \ViewAction($data);

	})
	->post('doUpdate', function($data) {

		$fw = new \FailWatch();

		$cPlace = \series\PlaceLib::buildFromBeds($data->eSeries, POST('beds', 'array'), POST('sizes', 'array'));

		if($fw->ok()) {
			\series\PlaceLib::replaceForSeries($data->eSeries, $cPlace);
		}

		$fw->validate();

		$data->cPlace = \series\PlaceLib::getBySeries($data->eSeries);

		throw new \ViewAction($data);

	});
?>
