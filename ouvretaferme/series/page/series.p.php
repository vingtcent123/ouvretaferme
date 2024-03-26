<?php
(new \series\CultivationPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

	}))
	->create(function($data) {

		$data->cPlant = \plant\PlantLib::getByFarm($data->eFarm, properties: ['id', 'name']);

		throw new \ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		$prepare = \series\SeriesLib::prepareCreate($_POST);

		if($fw->ok()) {

			[$eSeries, $cCultivation] = $prepare;

			if($eSeries['cycle'] === \series\Series::PERENNIAL) {
				$season = 1;
			} else {
				$season = NULL;
			}

			$cFlow = new Collection();
			$referenceYear = NULL;

			if($eSeries['sequence']->notEmpty()) {

				$cFlow = \production\FlowLib::getBySequence($eSeries['sequence'], $season, ['plant' => ['name']]);

				if($eSeries['cycle'] === \series\Series::ANNUAL) {

					if($cFlow->notEmpty()) {

						$startYear = (int)POST('startYear', [0, -1], 'int');
						$startWeek = (int)POST('startWeek', fn($value) => ($value >= 1 and $value <= 52), 1);

						\production\FlowLib::changeWeekStart($cFlow, $startWeek);

						$firstYear = ($cFlow->first()['yearOnly'] ?? $cFlow->first()['yearStart']);
						$selectedYear = $startYear + $eSeries['season'];
						$referenceYear = $selectedYear - $firstYear;

						if(
							$selectedYear < $eSeries['season'] and
							($cFlow->first()['weekOnly'] ?? $cFlow->first()['weekStart']) < \Setting::get('production\minWeekN-1')
						) {
							\production\Flow::fail('weekTooSoonAnnualNeutral', ['season' => $eSeries['season']]);
							$fw->validate();
						}

					}

				} else {
					$referenceYear = $eSeries['season'];
				}

			}

			\series\SeriesLib::createWithCultivations($eSeries, $cCultivation, $cFlow, $referenceYear);

		}

		$fw->validate();

		throw new RedirectAction(\series\SeriesUi::url($eSeries).'?success=series:Series::created');

	})
	->post('addPlant', function($data) {

		$data->season = POST('season', 'int');

		$data->eFarm->validateSeason($data->season);

		$data->ePlant = \plant\PlantLib::getById(POST('plant'))->validateProperty('farm', $data->eFarm);

		$use = \series\Series::POST('use', 'use', \series\Series::BED);

		$data->eSeries = new \series\Series([
			'season' => $data->season,
			'cycle' => \series\Series::POST('cycle', 'cycle', function() {
				throw new NotExpectedAction('Missing cycle');
			}),
			'use' => $use,
			'area' => 0,
			'length' => ($use === \series\Series::BED) ? 0 : NULL
		]);

		$data->ccVariety = \plant\VarietyLib::query($data->eFarm, $data->ePlant);
		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		$data->nextIndex = POST('index', 'int', 0) + 1;

		throw new \ViewAction($data);

	});

(new Page(function($data) {

		$data->season = INPUT('season', 'int');

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))
			->validate('canManage')
			->validateSeason($data->season);

	}))
	->get('createFrom', fn($data) => throw new ViewAction($data))
	->get('createFromPlant', function($data) {

		$data->eFarmer = \farm\FarmerLib::getOnlineByFarm($data->eFarm);
		$data->ePlant = \plant\PlantLib::getById(GET('plant'))->validate('notEmpty');
		$data->ccVariety = \plant\VarietyLib::query($data->eFarm, $data->ePlant);
		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		throw new ViewAction($data);

	})
	->get('createFromSequence', function($data) {

		$data->eSequence = \production\SequenceLib::getById(GET('sequence'))->validate('canRead');

		if($data->eSequence['cycle'] === \production\Sequence::PERENNIAL) {
			$season = 1;
		} else {
			$season = NULL;
		}

		$data->cFlow = \production\FlowLib::getBySequence($data->eSequence, $season, ['plant' => ['name']]);
		$data->cCultivation = \series\CultivationLib::buildFromSequence($data->eSequence, $data->cFlow, $data->eFarm, $data->season);

		$data->events = \production\FlowLib::reorder($data->eSequence, $data->cFlow);

		throw new ViewAction($data);

	})
	->post('getTasksFromSequence', function($data) {

		$data->eSequence = \production\SequenceLib::getById(POST('sequence'))->validate('canRead');

		$data->startYear = (int)POST('startYear', [0, -1], 'int');
		$data->startWeek = (int)POST('startWeek', fn($value) => ($value >= 1 and $value <= 52), 1);

		if($data->eSequence['cycle'] === \production\Sequence::PERENNIAL) {
			$season = 1;
		} else {
			$season = NULL;
		}

		$data->cFlow = \production\FlowLib::getBySequence($data->eSequence, $season, ['plant' => ['name']]);
		\production\FlowLib::changeWeekStart($data->cFlow, $data->startWeek);

		$data->events = \production\FlowLib::reorder($data->eSequence, $data->cFlow);

		throw new ViewAction($data);

	});

(new \series\SeriesPage())
	->read('/serie/{id}', function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById($data->e['farm']);
		$data->eFarm->saveFeaturesAsSettings();

		$data->season = $data->e['season'];

		$data->cSeriesPerennial = \series\SeriesLib::getByPerennialFirst($data->e['perennialFirst']);

		$data->cCultivation = \series\CultivationLib::getBySeries($data->e);
		$data->cTask = \series\TaskLib::getBySeries($data->e);
		$data->cPhoto = \gallery\PhotoLib::getBySeries($data->e);
		$data->cPlace = \series\PlaceLib::getByElement($data->e);
		$data->ccTask = \series\TaskLib::getWorkingTimeBySeries($data->e);
		$data->ccTaskHarvested = \series\TaskLib::getHarvestedBySeries($data->e);

		$data->e['sequence'] = \production\SequenceLib::getById($data->e['sequence']);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);

		\farm\FarmerLib::register($data->eFarm);

		throw new ViewAction($data);

	});

(new \series\SeriesPage())
	->applyElement(function($data, \series\Series $e) {

		$e->validate('canWrite');

		$e['farm'] = \farm\FarmLib::getById($e['farm']);

		$data->eFarm = $e['farm'];
		$data->season = $e['season'];

	})
	->read('restoreComment', function($data) {
		throw new ViewAction($data, path: ':getComment');
	}, method: 'post')
	->read('updateComment', fn($data) => throw new ViewAction($data), method: 'post')
	->doUpdateProperties('doUpdateComment', ['comment'], function($data) {
		throw new ViewAction($data, path: ':getComment');
	})
	->doUpdateProperties('doUpdateStatus', ['status'], fn() => throw new ReloadAction())
	->quick(['name', 'areaTarget', 'lengthTarget'])
	->read('perennialContinued', function($data) {

		$data->e->validate('isPerennial');

		$fw = new FailWatch();

		\series\SeriesLib::updatePerennialContinued($data->e);

		$fw->validate();

		throw new ReloadAction();

	}, method: 'post')
	->read('perennialFinished', function($data) {

		$data->e->validate('isPerennial');

		\series\SeriesLib::updatePerennialFinished($data->e);

		throw new ReloadAction();

	}, method: 'post')
	->update(function($data) {

		$cCultivation = \series\CultivationLib::getBySeries($data->e);

		$data->e['cPlace'] = \series\PlaceLib::getByElement($data->e);
		$data->e['cCultivation'] = \series\CultivationLib::getBySeries($data->e);
		$data->e['cSequence'] = \production\SequenceLib::getForSeries($data->e, $cCultivation);
		$data->e['sequence'] = \production\SequenceLib::getById($data->e['sequence']);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction())
	->read('duplicate', function($data) {

		if($data->e['cycle'] !== \series\Series::ANNUAL) {
			throw new NotExpectedAction('Can duplicate only annual series');
		}

		$data->cTask = \series\TaskLib::getBySeries($data->e);
		$data->cPlace = \series\PlaceLib::getByElement($data->e);

		throw new ViewAction($data);

	})
	->write('doDuplicate', function($data) {

		if($data->e['cycle'] !== \series\Series::ANNUAL) {
			throw new NotExpectedAction('Can duplicate only annual series');
		}

		$copyTasks = POST('copyTasks', 'bool');

		if($copyTasks) {

			$cAction = \farm\ActionLib::getByFarm($data->eFarm, id: POST('copyActions', 'array'));

			if($cAction->empty()) {
				\series\Series::fail('copyActions.check');
				return;
			}

		} else {
			$cAction = new Collection();
		}

		$fw = new FailWatch();

		$data->e['oldSeason'] = $data->e['season'];
		$data->e->build(['season'], $_POST);

		$fw->validate();

		$data->eSeriesNew = \series\SeriesLib::duplicate(
			$data->e,
			$copyTasks,
			$cAction,
			POST('copyTimesheet', 'bool'),
			POST('copyPlaces', 'bool')
		);

		throw new RedirectAction(\series\SeriesUi::url($data->eSeriesNew).'?success=series:Series::duplicated');
	})
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCultivationSeries($data->eFarm, \farm\Farmer::SERIES, season: $data->season).'?success=series:Series::deleted');
	});
?>
