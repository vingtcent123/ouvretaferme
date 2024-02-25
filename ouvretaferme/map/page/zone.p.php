<?php
(new \map\ZonePage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->season = \map\SeasonLib::getOnline(INPUT('season'));

	}))
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \map\Zone([
			'farm' => $data->eFarm
		]);

	})
	->create(function($data) {

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->eFarm['seasonLast']);
		\map\GreenhouseLib::putFromZone($data->cZone);

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCartography($data->e['farm'], $data->season).'?zone='.$data->e['id'].'&success=map:Zone::created');
	});

(new \map\ZonePage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->season = \map\SeasonLib::getOnline(INPUT('season'));

	}))
	->read('getCartography', function($data) {

		$data->e->validate('canRead');

		\farm\FarmerLib::register($data->e['farm']);

		\map\GreenhouseLib::putFromZone($data->e);
		\map\PlotLib::putFromZone($data->e, withBeds: TRUE, withDraw: TRUE, season: $data->season);

		$data->cGreenhouse = \map\GreenhouseLib::getByFarm($data->e['farm']);

		$data->ePlot = GET('plot', 'map\Plot');

		throw new \ViewAction($data);

	});

(new \map\ZonePage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->season = \map\SeasonLib::getOnline(INPUT('season'));

	}))
	->applyElement(function($data, \map\Zone $e) {

		$e->validate('canWrite');

	})
	->update(function($data) {

		$data->cZone = \map\ZoneLib::getByFarm($data->e['farm'], season: $data->e['farm']['seasonLast']);
		\map\GreenhouseLib::putFromZone($data->cZone);

		throw new \ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCartography($data->e['farm'], $data->season).'?zone='.$data->e['id'].'&success=map:Zone::updated');
	})
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCartography($data->e['farm'], $data->season).'?success=map:Zone::deleted');
	});
?>
