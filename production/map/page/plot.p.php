<?php
new \map\PlotPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->season = \map\SeasonLib::getOnline(INPUT('season'));


	})
	->getCreateElement(function($data) {

		$data->eZone = \map\ZoneLib::getById(INPUT('zone'))->validate('canWrite');

		return new \map\Plot([
			'zone' => $data->eZone,
			'farm' => $data->eZone['farm']
		]);

	})
	->create(function($data) {

		\map\GreenhouseLib::putFromZone($data->eZone);
		\map\PlotLib::putFromZone($data->eZone);

		throw new \ViewAction($data);

	})
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCultivationCartography($data->eZone['farm'], $data->season).'?zone='.$data->eZone['id'].'&success=map:Plot::created');
	});

new \map\PlotPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->season = \map\SeasonLib::getOnline(INPUT('season'));

	})
	->applyElement(function($data, \map\Plot $e) {

		$e->validate('canWrite');

	})
	->update(function($data) {

		\map\PlotLib::putFromZone($data->e['zone']);
		\map\GreenhouseLib::putFromZone($data->e['zone']);

		throw new \ViewAction($data);

	})
	->doUpdate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCultivationCartography($data->e['farm'], $data->season).'?zone='.$data->e['zone']['id'].'&success=map:Plot::updated');
	})
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCultivationCartography($data->e['farm'], $data->season).'?zone='.$data->e['zone']['id'].'&success=map:Plot::deleted');
	});
?>
