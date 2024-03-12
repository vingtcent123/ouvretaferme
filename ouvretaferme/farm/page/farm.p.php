<?php
(new \farm\FarmPage(
		function($data) {
			\user\ConnectionLib::checkLogged();
		}
	))
	->getCreateElement(fn($data) => new \farm\Farm([
		'owner' => \user\ConnectionLib::getOnline()
	]))
	->create()
	->doCreate(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCartography($data->e).'?success=farm:Farm.created');
	});

(new \farm\FarmPage())
	->applyElement(function($data, \farm\Farm $e) {
		$e->validate('canManage');
	})
	->update()
	->doUpdate(fn() => throw new ReloadAction('farm', 'Farm.updated'))
	->update(function($data) {
		$data->e['cPlantRotationExclude'] = \plant\PlantLib::getByIds($data->e['rotationExclude'], sort: 'name');
		throw new ViewAction($data);
	}, page: 'updateSeries')
	->doUpdateProperties('doUpdateSeries', ['calendarMonthStart', 'calendarMonthStop', 'rotationYears', 'rotationExclude'], fn() => throw new ReloadAction('farm', 'Farm.updatedRotation'))
	->update(page: 'updateFeature')
	->doUpdateProperties('doUpdateFeature', ['featureTime', 'featureDocument'], fn() => throw new ReloadAction('farm', 'Farm.updatedFeatures'))
	->read('calendarMonth', function($data) {

		$data->e['calendarMonthStart'] = GET('calendarMonthStart', '?int');
		$data->e['calendarMonthStop'] = GET('calendarMonthStop', '?int');
		$data->e['calendarMonths'] = ($data->e['calendarMonthStart'] ? (12 - $data->e['calendarMonthStart'] + 1) : 0) + 12 + ($data->e['calendarMonthStop'] ?? 0);

		throw new ViewAction($data);

	})
	->write('doSeasonFirst', function($data) {

		$data->increment = POST('increment', 'int');
		\farm\FarmLib::updateSeasonFirst($data->e, $data->increment);

		throw new RedirectAction(\farm\FarmUi::urlCultivationSeries($data->e, \farm\Farmer::AREA, season: $data->e['seasonFirst'] + $data->increment));

	})
	->write('doSeasonLast', function($data) {

		$data->increment = POST('increment', 'int');
		\farm\FarmLib::updateSeasonLast($data->e, $data->increment);

		throw new RedirectAction(\farm\FarmUi::urlCultivationSeries($data->e, \farm\Farmer::AREA, season: $data->e['seasonLast'] + $data->increment));

	})
	->write('doClose', function($data) {

		if(OTF_DEMO) {
			throw new \FailAction('farm\Farm::demo.delete');
		}

		$data->e['status'] = \farm\Farm::CLOSED;

		\farm\FarmLib::update($data->e, ['status']);

		throw new RedirectAction('/?success=farm:Farm.closed');

	})
	->read('export', function($data) {

		$data->eFarm = $data->e;
		$data->year = GET('year', default: $data->e['seasonLast']);

		\farm\FarmerLib::register($data->e);

		throw new \ViewAction($data);

	}, validate: ['canPersonalData']);
?>
