<?php
new \farm\FarmPage(
		function($data) {
			\user\ConnectionLib::checkLogged();
		}
	)
	->getCreateElement(fn($data) => new \farm\Farm([
		'owner' => \user\ConnectionLib::getOnline(),
		'type' => INPUT('type', [\farm\Farm::COMMUNITY, \farm\Farm::PRODUCER], \farm\Farm::PRODUCER)
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
		throw new RedirectAction(match($data->e['type']) {
			\farm\Farm::PRODUCER => '/farm/farm:start?id='.$data->e['id'],
			\farm\Farm::COMMUNITY => \farm\FarmUi::urlShopList($data->e),
		});
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
	->update(function($data) {

		$data->eFarm = $data->e;

		throw new ViewAction($data);

	}, page: 'updateForElectronicInvoicing')
	->doUpdateProperties('doUpdateForElectronicInvoicing', ['siret', 'legalStreet1', 'legalStreet2', 'legalPostcode', 'legalCity'], fn() => throw new ReloadAction('farm', 'Farm::updated'))
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

new Page()
	->get('surveyAnalyze', function($data) {

		if(
			$data->eUserOnline->empty() or
			$data->eUserOnline['id'] !== 1
		) {
			throw new NotAllowedAction();
		}

		$data->cSurvey = \farm\SurveyLib::getAll();

		throw new ViewAction($data);

	});

new \farm\SurveyPage()
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		if($data->eFarm->notEmpty()) {
			$data->eFarm->validate('isMembership', 'canManage');
		}

		return new \farm\Survey([
			'farm' => $data->eFarm,
		]);

	})
	->create(function($data) {

		if($data->eFarm->empty()) {

			$data->cFarm = \farm\FarmLib::getOnline()->find(fn($eFarm) => (
				$eFarm->canManage()
			));

			throw new \ViewAction($data, ':surveyMain');

		} else {

			$data->hasSurvey = \farm\Survey::model()
				->whereFarm($data->eFarm)
				->exists();

			throw new \ViewAction($data, ':surveyFarm');

		}


	}, page: 'survey')
	->doCreate(function($data) {

		throw new RedirectAction('/farm/farm:survey?farm='.$data->e['farm']['id']);

	}, page: 'doSurvey');
?>
