<?php
(new Page(function($data) {

		$data->ePlot = \map\PlotLib::getById(INPUT('plot'))->validate('canWrite');

	}))
	->get('create', function($data) {

		$data->cGreenhouse = \map\GreenhouseLib::getByPlot($data->ePlot);

		$data->season = GET('season', 'int');

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$data->season = POST('season', 'int');

		$fw = new \FailWatch();

		$e = new \map\Bed([
			'plot' => $data->ePlot
		]);

		$e->build(['length', 'width', 'greenhouse'], $_POST);

		$data->c = \map\BedLib::buildFromNames($e, POST('names', 'array'));

		if($fw->ok()) {
			\map\BedLib::createCollection($data->c);
		}

		$fw->validate();

		$success = $data->c->count() === 1 ? 'Bed::created' : 'Bed::createdCollection';

		throw new RedirectAction(\farm\FarmUi::urlCartography($data->ePlot['farm'], $data->season).'?zone='.$data->ePlot['zone']['id'].'&success=map:'.$success);

	});

(new \map\BedPage())
	->applyElement(function($data, \map\Bed $e) {

		$e->validate('canWrite');

	})
	->update(function($data) {

		$data->cPlot = \map\PlotLib::getByZone($data->e['zone']);

		$data->season = GET('season', 'int');

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new ViewAction($data))
	->quick(['name'])
	->doDelete(fn($data) => throw new ViewAction($data))
	->read('swapSeries', function($data) {

		if($data->e['plotFill']) {
			throw new NotExpectedAction('Invalid plot');
		}

		$data->season = GET('season', 'int');

		$eFarm = $data->e['farm']->validateSeason($data->season);

		$data->cZone = \map\ZoneLib::getByFarm($eFarm, season: $data->season);
		\map\PlotLib::putFromZone($data->cZone, withBeds: TRUE, season: $data->season);

		throw new ViewAction($data);

	})
	->write('doSwapSeries', function($data) {

		if($data->e['plotFill']) {
			throw new NotExpectedAction('Invalid plot');
		}

		$data->season = POST('season', 'int');

		$data->e['farm']->validateSeason($data->season);

		$eBed2 = \map\BedLib::getById(POST('swapId'))->validate('canWrite');

		if(
			$data->e['id'] !== $eBed2['id'] and
			$data->e['farm']['id'] === $eBed2['farm']['id']
		) {
			\map\BedLib::swapSeries($data->season, $data->e, $eBed2);
		}

		throw new ReloadAction();

	});

$updateCollection = function($data, ?Closure $callback = NULL) {

	$fw = new \FailWatch();

	$data->cBed = \map\BedLib::buildIds($data->ePlot, GET('ids', 'array'));

	$data->season = GET('season', 'int');

	$fw->validate();

	if($callback) {
		$callback($data);
	}

	throw new ViewAction($data);

};

(new Page(function($data) {

		$data->ePlot = \map\PlotLib::getById(INPUT('plot'))->validate('canWrite');

	}))
	->post('doUpdateGreenhouseCollection', function($data) {

		if($data->ePlot['zoneFill'] === FALSE) {
			throw new NotExpectedAction('Not zone fill');
		}

		$fw = new \FailWatch();

		$e = new \map\Bed([
			'plot' => $data->ePlot
		]);

		$e->build(['greenhouse'], $_POST);

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$fw->validate();

		\map\BedLib::updateCollection($c, $e, ['greenhouse']);

		throw new ViewAction($data);

	})
	->get('updateBedLineCollection', fn($data) => $updateCollection($data, function($data) {

		if($data->ePlot->canBedLine() === FALSE) {
			throw new \FailAction('map\Bed::canNotDraw');
		}
		
		\map\PlotLib::putFromZone($data->ePlot['zone'], withBeds: TRUE, withDraw: TRUE, season: $data->season);
		
	}))
	->post('doUpdateBedLineCollection', function($data) use ($updateCollection) {

		if($data->ePlot->canBedLine() === FALSE) {
			throw new \FailAction('map\Bed::canNotDraw');
		}

		$fw = new \FailWatch();

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$eDraw = new \map\Draw([
			'farm' => $data->ePlot['farm'],
			'plot' => $data->ePlot
		]);

		$eDraw->build(['season'], $_POST);

		$fw->validate();

		try {
			$_POST['coordinates'] = POST('coordinates') ? json_decode(POST('coordinates'), TRUE, 512, JSON_THROW_ON_ERROR) : [];
		} catch(Exception) {
			throw new NotExpectedAction('Invalid JSON');
		}

		$eDraw->build(['coordinates'], $_POST);

		$fw->validate();

		\map\DrawLib::createByBeds($eDraw, $c);

		$fw->validate();

		throw new RedirectAction(\farm\FarmUi::urlCartography($eDraw['farm'], $eDraw['season']).'?zone='.$data->ePlot['zone']['id'].'&success=map:Draw::created');

	})
	->post('doDeleteBedLineCollection', function($data) use ($updateCollection) {

		$fw = new \FailWatch();

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$eDraw = new \map\Draw([
			'farm' => $data->ePlot['farm'],
			'plot' => $data->ePlot
		]);

		$eDraw->build(['season'], $_POST);

		$fw->validate();

		\map\DrawLib::deleteByBeds($eDraw, $c);

		throw new RedirectAction(\farm\FarmUi::urlCartography($eDraw['farm'], $eDraw['season']).'?zone='.$data->ePlot['zone']['id'].'&success=map:Draw::deleted');

	})
	->get('updateSizeCollection', $updateCollection)
	->post('doUpdateSizeCollection', function($data) {

		$fw = new \FailWatch();

		$e = new \map\Bed([
			'plot' => $data->ePlot
		]);

		$e->build(['length', 'width'], $_POST);

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$fw->validate();

		\map\BedLib::updateCollection($c, $e, ['length', 'width']);

		$fw->validate();

		throw new ViewAction($data);

	})
	->get('updateSeasonCollection', $updateCollection)
	->post('doUpdateSeasonCollection', function($data) {

		$fw = new \FailWatch();

		$e = new \map\Bed([
			'plot' => $data->ePlot
		]);

		$e->build(['seasonFirst', 'seasonLast'], $_POST);

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$fw->validate();

		\map\BedLib::updateCollection($c, $e, ['seasonFirst', 'seasonLast']);

		$fw->validate();

		throw new ViewAction($data);

	})
	->post('doDeleteCollection', function($data) {

		$fw = new \FailWatch();

		$c = \map\BedLib::buildIds($data->ePlot, POST('ids', 'array'));

		$fw->validate();

		\map\BedLib::deleteCollection($c);

		$fw->validate();

		throw new ReloadAction();

	});
?>
