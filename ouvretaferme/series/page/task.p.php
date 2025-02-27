<?php
(new \series\TaskPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate();
		$data->eFarm->saveFeaturesAsSettings();

		return new \series\Task([
			'farm' => $data->eFarm,
			'series' => new \series\Series(),
			'season' => NULL,
			'cultivation' => new \series\Series(),
			'status' => \series\Task::INPUT('status', 'status', \series\Task::TODO),
			'category' => \farm\CategoryLib::getByFarm($data->eFarm, id: INPUT('category')),
		]);

	})
	->create(function($data) {

		$plannedWeek = \series\Task::GET('plannedWeek', 'plannedWeek');
		$plannedDate = \series\Task::GET('plannedDate', 'plannedDate');
		$doneWeek = \series\Task::GET('doneWeek', 'doneWeek', currentWeek());
		$doneDate = \series\Task::GET('doneDate', 'doneDate');

		$data->e->merge([
			'plannedWeek' => $plannedWeek,
			'plannedDate' => $plannedDate ?? ($plannedWeek === currentWeek() ? currentDate() : NULL),
			'plannedSelection' => ($plannedDate !== NULL) ? 'date' : 'week',
			'doneWeek' => \series\Task::GET('doneWeek', 'doneWeek', currentWeek()),
			'doneDate' => $doneDate ?? ($doneWeek === currentWeek() ? currentDate() : NULL),
			'doneSelection' => ($plannedDate !== NULL) ? 'date' : 'week',
			'action' => \farm\ActionLib::getByFarm($data->eFarm, id: GET('action'))
		]);

		if($data->e['category']->empty()) {
			throw new NotExpectedAction('Invalid value for \'category\'');
		}

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, category: $data->e['category']);
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm);

		$ePlant = \plant\PlantLib::getById(GET('plant'));

		if($ePlant->notEmpty()) {

			$ePlant->validateProperty('farm', $data->eFarm);

			$data->e['cSize'] = \plant\SizeLib::getByFarmAndPlant($data->e['farm'], $ePlant);

		} else {
			$data->e['cSize'] = new Collection();
			$data->cAction->map(fn($eAction) => $eAction['disabled'] = ($eAction['fqn'] === ACTION_RECOLTE));
		}

		$data->e->add([
			'cultivation' => new \series\Cultivation(),
			'plant' => $ePlant,
			'cTool?' => fn() => new Collection(),
			'hasTools' => \farm\ToolLib::getForWork($data->e['farm'], $data->e['action']),
			'cMethod?' => fn() => new Collection(),
			'hasMethods' => \farm\MethodLib::getForWork($data->e['farm'], $data->e['action'])
		]);

		throw new ViewAction($data);


	}, propertiesCreate: [], page: 'createFromScratch')
	->create(function($data) {

		$cAction = \farm\ActionLib::getByFarm($data->eFarm, category: CATEGORIE_CULTURE, index: 'id');
		$cAction->setColumn('disabled', FALSE);

		$ePlant = new \plant\Plant();

		// Récupération de la série appelée en paramètre de la page
		if(get_exists('series')) {

			$selection = \series\Series::getSelection();
			$selection['cCultivation'] = \series\CultivationLib::delegateBySeries();

			$data->eSeries = \series\SeriesLib::getById(GET('series'), $selection)->validateProperty('farm', $data->eFarm);

			if($data->eSeries['cCultivation']->count() === 1) {
				$data->eCultivation = $data->eSeries['cCultivation']->first();
			} else if(get_exists('cultivation')) {
				$data->eCultivation = $data->eSeries['cCultivation'][GET('cultivation', 'int')] ?? new \series\Cultivation();
			} else {
				$data->eCultivation = new \series\Cultivation();
			}

			$data->cSeries = new Collection();

			$data->season = $data->eSeries['season'];

			if($data->eCultivation->empty()) {
				$cAction->map(fn($eAction) => $eAction['disabled'] = ($eAction['fqn'] === ACTION_RECOLTE));
			} else {
				$ePlant = $data->eCultivation['plant'];
			}

		} else {

			$data->season = GET('season');
			$data->eFarm->validateSeason($data->season);

			$data->eSeries = new \series\Series();
			$data->eCultivation = new \series\Cultivation();

			$search = new Search([
				'status' => \series\Series::OPEN
			]);

			if(get_exists('plant')) {

				$ePlant = \plant\PlantLib::getById(GET('plant'))->validateProperty('farm', $data->eFarm);
				$search->set('plant', $ePlant);

			}

			$data->cSeries = \series\SeriesLib::getByFarm($data->eFarm, $data->season, selectCultivation: TRUE, selectPlaces: TRUE, search: $search);

			// Si sélection par plante, on trie correctement les séries par ordre chronologique
			if($ePlant->notEmpty()) {

				$data->cSeries->sort(function($eSeries1, $eSeries2) use ($ePlant) {

					$eCultivation1 = $eSeries1['cCultivation']->find(fn($eCultivation) => $eCultivation['plant']['id'] === $ePlant['id'], limit: 1, clone: FALSE);
					$eCultivation2 = $eSeries2['cCultivation']->find(fn($eCultivation) => $eCultivation['plant']['id'] === $ePlant['id'], limit: 1, clone: FALSE);

					if($eCultivation1['startWeek'] === NULL and $eCultivation2['startWeek'] === NULL) {
						return \L::getCollator()->compare($eCultivation1['series']['name'] ?? '', $eCultivation2['series']['name'] ?? '');
					}

					if($eCultivation1['startWeek'] === NULL) {
						return 1;
					}

					if($eCultivation2['startWeek'] === NULL) {
						return -1;
					}

					return ($eCultivation1['startWeek'] > $eCultivation2['startWeek'] ? 1 : -1);

				});

			} else {
				$data->cSeries->sort(['name' => SORT_ASC], natural: TRUE);
			}

			if($data->cSeries->notEmpty()) {

				\series\Series::validateBatch($data->cSeries, $data->eFarm);

				$data->season = $data->cSeries->first()['season'];

			}

			$cAction->map(fn($eAction) => $eAction['disabled'] = TRUE);

			\farm\ActionLib::getMainByFarm($data->eFarm);

		}

		$eAction = GET('action') ? ($cAction[GET('action')] ?? throw new NotExpectedAction('Action mismatch')) : new \farm\Action();

		// Initialisation de l'objet de la tâche
		$data->e->merge([
			'season' => $data->season,
			'series' => $data->eSeries,
			'cultivation' => $data->eCultivation,
			'plannedWeek' => ($data->e['status'] === \series\Task::TODO) ? \series\Task::GET('plannedWeek', 'plannedWeek', $data->season) : NULL,
			'plannedDate' => ($data->e['status'] === \series\Task::TODO) ? \series\Task::GET('plannedDate', 'plannedDate') : NULL,
			'doneWeek' => ($data->e['status'] === \series\Task::DONE) ? \series\Task::GET('doneWeek', 'doneWeek', currentWeek()) : NULL,
			'doneDate' => ($data->e['status'] === \series\Task::DONE) ? \series\Task::GET('doneDate', 'doneDate') : NULL,
			'action' => $eAction,
			'plant' => $ePlant,
			'cTool?' => fn() => new Collection(),
			'hasTools' => \farm\ToolLib::getForWork($data->eFarm, $eAction),
			'cMethod?' => fn() => new Collection(),
			'hasMethods' => \farm\MethodLib::getForWork($data->eFarm, $eAction),
			'cAction' => $cAction,
			'cVariety' => new Collection(),
			'varietiesIntersect' => []
		]);

		if($data->eCultivation->notEmpty()) {
			$data->e->merge(\series\SliceLib::getVarietiesByCultivations(new Collection([$data->eCultivation])));
		}

		if($ePlant->empty()) {
			$data->e['cSize'] = new Collection();
		} else {
			$data->e['cSize'] = \plant\SizeLib::getByFarmAndPlant($data->eFarm, $ePlant);
		}

		throw new ViewAction($data);

	}, propertiesCreate: [], page: 'createFromSeries')
	->doCreate(fn($data) => throw new ViewAction($data));

(new Page())
	->post('doCreateFromSeriesCollection', function($data) {

		$data->status = \series\Task::INPUT('status', 'status', \series\Task::TODO);
		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$data->e = new \series\Task([
			'status' => $data->status,
			'farm' => $data->eFarm
		]);

		$data->e->validate('canCreate');

		$fw = new \FailWatch();

		$cSeries = \series\SeriesLib::getByIds(POST('series', 'array'));
		\series\Series::validateBatch($cSeries, $data->eFarm);

		$cCultivation = \series\CultivationLib::getByIds(POST('cultivation', 'array'), index: 'series');

		$eCategory = \farm\CategoryLib::getByFarm($data->eFarm, fqn: CATEGORIE_CULTURE);

		$data->c = new Collection();

		foreach($cSeries as $eSeries) {

			$eCultivation = $cCultivation[$eSeries['id']] ?? new \series\Cultivation();

			if(
				($eCultivation->empty() and $eSeries['plants'] === 1) or // Une seule production dans la série mais pas de production en paramètre
				($eCultivation->notEmpty() and $eCultivation['series']['id'] !== $eSeries['id']) // Series mismatch
			) {
				Fail::log('series\Task::cultivation.check');
				continue;
			}

			$ePlant = $eCultivation->notEmpty() ? $eCultivation['plant'] : new \plant\Plant();

			$e = new \series\Task([
				'farm' => $eSeries['farm'],
				'category' => $eCategory,
				'series' => $eSeries,
				'season' => $eSeries['season'],
				'cultivation' => $eCultivation,
				'plant' => $ePlant,
				'status' => $data->status
			]);

			$e->build(\series\TaskLib::getPropertiesCreate()($e), $_POST, for: 'create');

			$data->c[] = $e;

		}

		$fw->validate();

		// Vérification des contraintes de cohérence (espèce, variété, unité, calibre) pour les récoltes
		if($data->c->first()['action']['fqn'] === ACTION_RECOLTE) {
			\series\Task::validateSameHarvest($data->c);
		}

		\series\TaskLib::createCollection($data->c);

		$fw->validate();

		throw new ViewAction($data);

	});


(new Page())
	->post('getCreateCollectionFields', function($data) {

		$data->eFarm = \farm\FarmLib::getById(POST('farm'));

		$data->eTask = new \series\Task([
			'action' => new \farm\Action(),
			'farm' => $data->eFarm
		]);

		$data->eTask->validate('canWrite');

		$data->cCultivation = \series\CultivationLib::getByIds(POST('cultivations', 'array'));

		if($data->cCultivation->notEmpty()) {

			$ePlant = \series\Cultivation::validateBatch($data->cCultivation, $data->eFarm);

			[
				'varietiesIntersect' => $data->varietiesIntersect,
				'cVariety' => $data->cVariety
			] = \series\SliceLib::getVarietiesByCultivations($data->cCultivation);

		} else {
			$data->cVariety = new Collection();
			$data->varietiesIntersect = [];
			$ePlant = new \plant\Plant();
		}

		if($ePlant->empty()) {
			$data->eTask['cSize'] = new Collection();
		} else {
			$data->eTask['cSize'] = \plant\SizeLib::getByFarmAndPlant($data->eFarm, $ePlant);
		}

		throw new ViewAction($data);

	})
	->post('getFields', function($data) {

		$eFarm = \farm\FarmLib::getById(POST('farm'));
		$eAction = \farm\ActionLib::getById(POST('action'))->validateProperty('farm', $eFarm);

		$data->eTask = new \series\Task([
			'farm' => $eFarm,
			'action' => $eAction
		]);

		$data->eTask->validate('canWrite');

		$data->eTask['hasTools'] = \farm\ToolLib::getForWork($eFarm, $eAction);
		$data->eTask['hasMethods'] = \farm\MethodLib::getForWork($eFarm, $eAction);

		$data->eTask['cTool?'] = fn() => new Collection();
		$data->eTask['cMethod?'] = fn() => new Collection();

		throw new \ViewAction($data);

	});

(new \series\TaskPage())
	->read('/tache/{id}', function($data) {

		$data->eFarm = $data->e['farm'];

		\farm\FarmerLib::register($data->eFarm);

		$data->e['cultivation'] = \series\CultivationLib::getById($data->e['cultivation']);

		\series\CultivationLib::fillSliceStats($data->e['cultivation']);
		\series\TaskLib::fillHarvestDates($data->e);

		if($data->e['series']->empty()) {
			$data->cPlace = \series\PlaceLib::getByElement($data->e);
		} else {
			$data->cPlace = \series\PlaceLib::getByElement($data->e['series']);
		}

		switch($data->e['action']['fqn']) {

			case ACTION_FERTILISATION :
				$data->e['cToolFertilizer'] = \farm\ToolLib::getByFarm($data->eFarm, routineName: 'fertilizer', search: new Search(['status' => \farm\Tool::ACTIVE]));
				break;

		}

		$data->cPhoto = \gallery\PhotoLib::getByTask($data->e);

		$data->cUser = \farm\FarmerLib::getUsersByFarm($data->e['farm'], withPresenceAbsence: TRUE);
		\series\TimesheetLib::fillTimesByTask($data->cUser, $data->e);

		$data->cComment = \series\CommentLib::getByTask($data->e);

		throw new ViewAction($data);

	})
	->read('getVarietiesField', function($data) {

		$data->eCultivation = \series\CultivationLib::getById(POST('cultivation'))->validateProperty('series', $data->e['series']);

		if($data->eCultivation->notEmpty()) {
			$data->eCultivation['cSlice'] = \series\SliceLib::getByCultivation($data->eCultivation);
		}

		throw new \ViewAction($data);

	}, method: 'post')
	->update(function($data) {

		if($data->e['series']->empty()) {

			$data->cZone = \map\ZoneLib::getByFarm($data->e['farm']);
			\map\PlotLib::putFromZone($data->cZone);

		} else {

			$data->e['series']['cCultivation'] = \series\CultivationLib::getBySeries($data->e['series']);

			if($data->e['cultivation']->notEmpty()) {
				$data->e['cultivation']['cSlice'] = \series\SliceLib::getByCultivation($data->e['cultivation']);
			}

			$data->cZone = new Collection();

		}

		if($data->e['plant']->empty()) {
			$data->e['cSize'] = new Collection();
		} else {
			$data->e['cSize'] = \plant\SizeLib::getByFarmAndPlant($data->e['farm'], $data->e['plant']);
		}

		$data->e['hasMethods'] = \farm\MethodLib::getForWork($data->e['farm'], $data->e['action']);
		$data->e['hasTools'] = \farm\ToolLib::getForWork($data->e['farm'], $data->e['action']);

		$data->cAction = \farm\ActionLib::getByFarm($data->e['farm'], category: $data->e['category']);
		$data->cAction->filter(fn($eAction) => $eAction['fqn'] !== ACTION_RECOLTE); // On ne peut pas changer l'action pour une récolte

		throw new ViewAction($data);

	})
	->doUpdate(fn($data) => throw new BackAction())
	->quick(['description'])
	->update(function($data) {

		if($data->e['series']->empty()) {
			throw new NotExpectedAction('No series');
		}

		$data->cCultivation = \series\CultivationLib::getBySamePlants($data->e['farm'], $data->e['season'], $data->e['plant']);

		throw new ViewAction($data);

	}, page: 'updateCultivation')
	->doUpdateProperties('doUpdateCultivation', ['cultivation'], fn($data) => throw new ViewAction($data))
	->write('doCheck', function($data) {

		\series\TaskLib::updateCheck($data->e, POST('position', 'int'), POST('check', 'bool'));

		throw new ViewAction($data);

	})
	->doDelete(fn($data) => throw new ViewAction($data))
	->write('doDeleteRepeat', function($data) {

		\series\TaskLib::deleteRepeat($data->e);

		throw new ViewAction($data, ':doDelete');

	}, validate: ['canDelete']);

(new Page(function($data) {

		$data->c = \series\TaskLib::getByIds(REQUEST('ids', 'array'), properties: \series\Task::getSelection() + [
			'cccPlace' => \series\PlaceLib::delegateByTask()
		]);

		\series\Task::validateBatch($data->c);

		$data->eFarm = $data->c->first()['farm'];

	}))
	->get('updateHarvestCollection', function($data) {

		$data->c->validate('canWrite');
		$data->c->setColumn('harvestDate', GET('date', default: currentDate()));

		\series\Task::validateSameAction($data->c, \farm\ActionLib::getByFarm($data->eFarm, fqn: ACTION_RECOLTE));
		\series\Task::validateSameHarvest($data->c);

		\series\TaskLib::fillDistribution($data->c);

		$data->cProductStock = \selling\StockLib::getCompatibleProducts($data->c->first());

		$data->eProductBookmark = \selling\StockLib::getBookmark($data->c->first());

		\farm\ActionLib::getMainByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->post('doUpdateHarvestCollection', function($data) {

		$data->c->validate('canWrite');

		$eAction = \farm\ActionLib::getByFarm($data->eFarm, fqn: ACTION_RECOLTE);

		\series\Task::validateSameAction($data->c, $eAction);
		\series\Task::validateSameHarvest($data->c);

		\series\TaskLib::fillHarvestDates($data->c);
		\series\TaskLib::fillDistribution($data->c);

		$data->harvestDate = POST('harvestDate');
		$data->harvestMore = POST('harvestMore', 'float', 0.0);

		$fw = new FailWatch();

		\series\TaskLib::buildHarvests(
			$data->c,
			$data->harvestMore,
			$data->harvestDate,
			POST('harvestUnit'),
			POST('distribution', ['area', 'plant', 'fair'], 'fair')
		);

		foreach($data->c as $e) {
			\series\TaskLib::update($e, ['harvest', 'harvestUnit']);
		}

		if(post_exists('stock')) {

			$productStock = POST('stock', '?int');

			if($productStock !== NULL) {

				$eProductStock = \selling\ProductLib::getById($productStock)
					->validate('acceptStock', onFail: fn() => \series\Task::fail('stock.check'))
					->validateProperty('farm', $data->eFarm);

			} else {
				$eProductStock = new \selling\Product();
			}

			$fw->validate();

			// Mise à jour du stock
			if($data->harvestMore !== 0.0) {

				if($eProductStock->notEmpty()) {

					\selling\StockLib::increment($eProductStock, new \selling\Stock([
						'newValue' => $data->harvestMore,
						'comment' => $eAction['name']
					]), zeroIfNegative: TRUE);

				}

			}

			// Mise à jour du marquage ou non en favori du stock
			if(POST('stockRemember', 'bool')) {
				\selling\StockLib::remember($data->c->first(), $eProductStock);
			} else {
				\selling\StockLib::forget($data->c->first());
			}

		}

		throw new ViewAction($data);

	})
	->post('doUpdateTodoCollection', function($data) {

		$data->c->validate('isDone', 'canWrite');

		\series\TaskLib::updateTodoCollection($data->c);

		throw new ReloadAction();

	})
	->post('doUpdateDoneCollection', function($data) {

		\series\Task::validateSameAction($data->c);

		$data->c->validate('isTodo', 'canWrite');

		$newDone = \series\Task::POST('doneWeek', 'doneWeek', fn() => throw new NotExpectedAction('Invalid week'));

		if($newDone !== NULL) {
			\series\TaskLib::updateDoneCollection($data->c, $newDone);
		}

		throw new ReloadAction();

	})
	->get('updatePlannedCollection', function($data) {

		$data->c->validate('isTodo', 'canWrite');

		throw new ViewAction($data);

	})
	->post('doUpdatePlannedDateCollection', function($data) {

		$data->c->validate('isTodo', 'canWrite');

		$fw = new FailWatch();

		$e = new \series\Task();
		$e->build(['plannedDate'], $_POST);

		$fw->validate();

		\series\TaskLib::updatePlannedDateCollection($data->c, $e['plannedDate']);

		throw new ReloadAction();

	})
	->post('doUpdatePlannedCollection', function($data) {

		$data->c->validate('isTodo', 'canWrite');

		$fw = new FailWatch();

		$e = new \series\Task();
		$e->build(['plannedWeek'], $_POST);

		$fw->validate();

		\series\TaskLib::updatePlannedCollection($data->c, $e['plannedWeek']);

		throw new ReloadAction();

	})
	->get('incrementPlannedCollection', function($data) {

		$data->c->validate('acceptPostpone', 'canWrite');

		throw new ViewAction($data);

	})
	->post('doIncrementPlannedCollection', function($data) {

		$data->c->validate('acceptPostpone', 'canWrite');

		\series\TaskLib::incrementPlannedCollection($data->c, POST('increment', 'int'));

		throw new ReloadAction();

	})
	->post('doUpdateUserCollection', function($data) {

		$data->c->validate('isTodo', 'canWrite');

		$eUser = \user\UserLib::getById(POST('user'));

		if(\farm\FarmerLib::isFarmer($eUser, $data->eFarm) === FALSE) {
			throw new NotExpectedAction('Invalid user');
		}

		$action = POST('action', ['add', 'delete'], fn() => throw new NotExpectedAction('Invalid action'));

		\series\TaskLib::updateUser($data->c, $eUser, $action);

		throw new ViewAction($data);

	})
	->post('doDeleteCollection', function($data) {

		$data->c->validate('canDelete');

		\series\TaskLib::deleteCollection($data->c);

		throw new ReloadAction();

	});

(new \series\TaskPage())
	->read('getStockField', function($data) {

		$data->e['harvestUnit'] = \series\Task::POST('harvestUnit', 'harvestUnit', fn() => throw new NotExpectedAction('Invalid unit'));

			$data->cProductStock = \selling\StockLib::getCompatibleProducts($data->e);
			$data->eProductBookmark = \selling\StockLib::getBookmark($data->e);

		throw new \ViewAction($data);

	}, method: 'post', validate: ['canWrite'])
?>
