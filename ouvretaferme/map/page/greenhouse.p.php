<?php
(new \map\GreenhousePage(function($data) {
		\user\ConnectionLib::checkLogged();
	}))
	->getCreateElement(function($data) {

		$eFarm = \farm\FarmLib::getById(INPUT('farm'));
		$ePlot = \map\PlotLib::getByFarm($eFarm, INPUT('plot'))->validateProperty('farm', $eFarm);

		return new \map\Greenhouse([
			'farm' => $eFarm,
			'plot' => $ePlot,
			'zone' => $ePlot['zone'],
		]);

	})
	->create()
	->doCreate(function($data) {

		throw new ReloadAction('map', 'Greenhouse.created');

	});

(new \map\GreenhousePage())
	->applyElement(function($data, \map\Greenhouse $e) {
		$e->validate('canWrite');
	})
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('map', 'Greenhouse.updated');
	})
	->doDelete(function($data) {
		throw new ReloadAction('map', 'Greenhouse.deleted');
	});
?>
