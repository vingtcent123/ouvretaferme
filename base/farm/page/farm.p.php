<?php
new \farm\FarmPage(
		function($data) {
			\user\ConnectionLib::checkLogged();
		}
	)
	->getCreateElement(fn($data) => new \farm\Farm([
		'owner' => \user\ConnectionLib::getOnline()
	]))
	->create(function($data) {

		$data->e['legalCountry'] = $data->eUserOnline['invoiceCountry'];

		throw new ViewAction($data);

	})
	->read('start', function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, validate: ['canManage'])
	->doCreate(function($data) {
		throw new RedirectAction('/farm/farm:start?id='.$data->e['id']);
	});

new \farm\FarmPage()
	->applyElement(function($data, \farm\Farm $e) {

		$e->validate('canManage');

	})
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction('farm', 'Farm::updated'))
	->update(function($data) {

		$data->eFarm = $data->e;

		$data->e['cPlantRotationExclude'] = \plant\PlantLib::getByIds($data->e['rotationExclude'], sort: 'name');

		throw new ViewAction($data);

	}, page: 'updateProduction')
	->doUpdateProperties('doUpdateProduction', ['defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth', 'featureTime', 'calendarMonthStart', 'calendarMonthStop', 'rotationYears', 'rotationExclude'], fn() => throw new ReloadAction('farm', 'Farm::updatedProduction'))
	->doUpdateProperties('doUpdateElectronicInvoicing', ['electronicScheme', 'electronicAddress'], fn() => throw new ReloadAction('farm', 'Farm::updated'))
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, page: 'updateForElectronicInvoicing')
	->doUpdateProperties('doUpdateForElectronicInvoicing', ['siret', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity', 'electronicScheme', 'electronicAddress'], fn() => throw new ReloadAction('farm', 'Farm::updated'))
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, page: 'updatePlace')
	->doUpdateProperties('doUpdatePlace', ['cultivationPlace', 'cultivationLngLat'], fn() => throw new ReloadAction('farm', 'Farm::updatedPlace'))
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, page: 'updateEmail')
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, page: 'updateLegal')
	->doUpdateProperties('doUpdateCountry', fn(\farm\Farm $e) => \farm\FarmLib::getPropertiesCountry($e), fn() => throw new ReloadAction('farm', 'Farm::updatedLegal'), for: 'legal')
	->doUpdateProperties('doUpdateLegal', fn(\farm\Farm $e) => \farm\FarmLib::getPropertiesLegal($e), fn() => throw new ReloadAction('farm', 'Farm::updatedLegal'), for: 'legal')
	->doUpdateProperties('doUpdateEmail', ['emailFooter', 'emailDefaultTime'], fn() => throw new ReloadAction('farm', 'Farm::updatedEmail'))
	->doUpdateProperties('doUpdatePlanningDelayedMax', ['planningDelayedMax'], fn() => throw new ReloadAction())
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

		throw new RedirectAction('/?success=farm\\Farm::closed');

	})
	->read('export', function($data) {

		$data->eFarm = $data->e;
		$data->year = GET('year', default: date('Y'));

		throw new \ViewAction($data);

	}, validate: ['canPersonalData']);

new \farm\SurveyPage()
	->getCreateElement(function($data) {

		$eFarm = \farm\FarmLib::getById(INPUT('farm'));

		if(in_array($data->eUserOnline['id'], [1140, 1]) === FALSE) {
			throw new NotExpectedAction();
		}
/*
		$eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		if(in_array($eFarm['id'], \farm\Survey::getFarms()) === FALSE) {
			throw new NotExpectedAction();
		}
*/
		return new \farm\Survey([
			'farm' => $eFarm,
		]);

	})
	->create(function($data) {

		$analyze = ($data->eUserOnline['id'] === 1140 or $data->eUserOnline['id'] === 1);

		$data->eFarm = $data->e['farm'];
		$data->hasSurvey = ($analyze === FALSE);// and \farm\SurveyLib::existsByFarm($data->eFarm));

		if(get_exists('id')) {
			\farm\Survey::model()
				->select(\farm\Survey::getSelection() + [
					'farm' => ['name']
				])
				->whereId(GET('id'))
				->get($data->e);
		}

		throw new \ViewAction($data);

	}, page: 'survey')
	->doCreate(function($data) {

		throw new RedirectAction('/farm/farm:survey?farm='.$data->e['farm']['id']);

	}, page: 'doSurvey');
?>
