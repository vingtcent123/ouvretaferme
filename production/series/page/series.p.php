<?php
new \series\CultivationPage(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

	})
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

				$cFlow = \sequence\FlowLib::getBySequence($eSeries['sequence'], $season, ['plant' => ['name']]);

				if($eSeries['cycle'] === \series\Series::ANNUAL) {

					if($cFlow->notEmpty()) {

						$startYear = (int)POST('startYear', [0, -1], 'int');
						$startWeek = (int)POST('startWeek', fn($value) => ($value >= 1 and $value <= 52), 1);

						\sequence\FlowLib::changeWeekStart($cFlow, $startWeek);

						$firstYear = ($cFlow->first()['yearOnly'] ?? $cFlow->first()['yearStart']);
						$selectedYear = $startYear + $eSeries['season'];
						$referenceYear = $selectedYear - $firstYear;

						if(
							$selectedYear < $eSeries['season'] and
							($cFlow->first()['weekOnly'] ?? $cFlow->first()['weekStart']) < \sequence\SequenceSetting::MIN_WEEK_MINUS_1
						) {
							\sequence\Flow::fail('weekTooSoonAnnualNeutral', ['season' => $eSeries['season']]);
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
			'farm' => $data->eFarm,
			'season' => $data->season,
			'cycle' => \series\Series::POST('cycle', 'cycle', function() {
				throw new NotExpectedAction('Missing cycle');
			}),
			'use' => $use,
			'area' => NULL,
			'areaTarget' => 0,
			'length' => NULL,
			'lengthTarget' => ($use === \series\Series::BED) ? 0 : NULL,
			'bedWidth' => $data->eFarm['defaultBedWidth'],
			'alleyWidth' => $data->eFarm['defaultAlleyWidth']
		]);

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		$data->eCultivation = \series\CultivationLib::getNew($data->eSeries, $data->ePlant);

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

		$data->eFarmer = $data->eFarm->getFarmer();
		$data->ePlant = \plant\PlantLib::getById(GET('plant'))->validate('notEmpty');
		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION], index: 'fqn');

		$data->eSeries = new \series\Series([
			'farm' => $data->eFarm,
			'name' => $data->ePlant['name'],
			'nameAuto' => TRUE,
			'nameDefault' => $data->ePlant['name'],
			'use' => \series\Series::BED,
			'area' => NULL,
			'areaTarget' => NULL,
			'length' => NULL,
			'lengthTarget' => NULL,
			'cycle' => $data->ePlant['cycle'],
			'season' => $data->season,
			'bedWidth' => $data->eFarm['defaultBedWidth'],
			'alleyWidth' => $data->eFarm['defaultAlleyWidth']
		]);

		$data->eCultivation = \series\CultivationLib::getNew($data->eSeries, $data->ePlant);

		throw new ViewAction($data);

	})
	->get('createFromSequence', function($data) {

		$data->eSequence = \sequence\SequenceLib::getById(GET('sequence'))->validate('canRead');

		if($data->eSequence['cycle'] === \sequence\Sequence::PERENNIAL) {
			$season = 1;
		} else {
			$season = NULL;
		}

		$data->cFlow = \sequence\FlowLib::getBySequence($data->eSequence, $season, ['plant' => ['name']]);
		$data->cCultivation = \series\CultivationLib::buildFromSequence($data->eSequence, $data->eFarm, $data->season);

		$cTray = \farm\ToolLib::getTraysByFarm($data->eFarm);
		$data->cCultivation->setColumn('cTray', $cTray);

		$data->events = \sequence\FlowLib::reorder($data->eSequence, $data->cFlow);

		throw new ViewAction($data);

	})
	->post('getTasksFromSequence', function($data) {

		$data->eSequence = \sequence\SequenceLib::getById(POST('sequence'))->validate('canRead');

		$data->startYear = (int)POST('startYear', [0, -1], 'int');
		$data->startWeek = (int)POST('startWeek', fn($value) => ($value >= 1 and $value <= 52), 1);

		if($data->eSequence['cycle'] === \sequence\Sequence::PERENNIAL) {
			$season = 1;
		} else {
			$season = NULL;
		}

		$data->cFlow = \sequence\FlowLib::getBySequence($data->eSequence, $season, ['plant' => ['name']]);
		\sequence\FlowLib::changeWeekStart($data->cFlow, $data->startWeek);

		$data->events = \sequence\FlowLib::reorder($data->eSequence, $data->cFlow);

		throw new ViewAction($data);

	});

new \series\SeriesPage()
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

		$data->e['sequence'] = \sequence\SequenceLib::getById($data->e['sequence']);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);


		throw new ViewAction($data);

	});

new \series\SeriesPage()
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
		$data->e['cSequence'] = \sequence\SequenceLib::getForSeries($data->e, $cCultivation);
		$data->e['sequence'] = \sequence\SequenceLib::getById($data->e['sequence']);

		throw new ViewAction($data);

	})
	->doUpdate(fn() => throw new ReloadAction())
	->doDelete(function($data) {
		throw new RedirectAction(\farm\FarmUi::urlCultivationSeries($data->eFarm, \farm\Farmer::AREA, season: $data->season).'?success=series:Series::deleted');
	});


new \series\SeriesPage()
	->applyCollection(function($data, Collection $c) {
		$c->validateProperty('farm', $c->first()['farm']);
	})
	->doUpdateCollectionProperties('doUpdateStatusCollection', ['status'], fn($data) => throw new ReloadAction())
	->doUpdateCollectionProperties('doUpdateSeasonCollection', ['season'], fn($data) => throw new ReloadAction('series', $data->c->count() === 1  ? 'Series::updatedSeason' : 'Series::updatedSeasonCollection'),
		validate: ['canUpdate', 'acceptSeason']
	);


(new Page(function($data) {

		$data->c = \series\SeriesLib::getByIds(REQUEST('ids', 'array'), sort: ['name' => SORT_ASC]);

		\series\Series::validateBatch($data->c);

		$data->eFarm = \farm\FarmLib::getById($data->c->first()['farm']);


	}))
	->get('duplicate', function($data) {

		$data->c->validate('canRead', 'acceptDuplicate');

		$data->cTaskMetadata = \series\TaskLib::getMetadataForDuplicate($data->c);
		$data->hasPlaces = \series\PlaceLib::existsBySeries($data->c);

		throw new ViewAction($data);

	})
	->post('doDuplicate', function($data) {

		$data->c->validate('canRead', 'acceptDuplicate');

		$cAction = \farm\ActionLib::getByFarm($data->eFarm, id: POST('copyActions', 'array'));

		$season = POST('season');
		$data->eFarm->validateSeason($season);

		$copies = POST('copies', 'int');

		if($copies < 1 or $copies > \series\SeriesSetting::DUPLICATE_LIMIT) {
			throw new NotExpectedAction('Invalid copies');
		}

		$copyTimesheet = POST('copyTimesheet', 'bool');
		$copyPlaces = POST('copyPlaces', 'bool');

		$fw = new FailWatch();

			$cSeriesNew = \series\SeriesLib::duplicateCollection(
				$data->c,
				$season,
				$cAction,
				$copies,
				$_POST,
				$copyTimesheet,
				$copyPlaces
			);

		$fw->validate();

		if($cSeriesNew->count() === 1) {
			throw new RedirectAction(\series\SeriesUi::url($cSeriesNew->first()).'?success=series:Series::duplicated');
		} else {
			throw new RedirectAction(\farm\FarmUi::urlCultivationSeries($data->eFarm, \farm\Farmer::AREA, season: $season).'&success=series:Series::duplicatedCollection');
		}

	})
	->get('updateSeasonCollection', function($data) {

		$data->c->validate('canRead', 'acceptSeason');

		throw new ViewAction($data);

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete');

		\series\SeriesLib::deleteCollection($data->c);

		throw new ReloadAction('series', 'Series::deletedCollection');

	});
?>
