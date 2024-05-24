<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canWrite');
		$data->eFarm->saveFeaturesAsSettings();

		\farm\FarmerLib::register($data->eFarm);


		$data->tip = \farm\TipLib::pickRandom($data->eUserOnline, $data->eFarm);
		$data->tipNavigation = 'close';

	}))
	->get(['/ferme/{id}/itineraires', '/ferme/{id}/itineraires/{status}'], function($data) {

		\farm\FarmerLib::setView('viewCultivation', $data->eFarm, \farm\Farmer::SEQUENCE);

		$data->sequences = \production\SequenceLib::countByFarm($data->eFarm);

		if(
			get_exists('status') and
			$data->sequences[\production\Sequence::CLOSED] === 0
		) {
			throw new RedirectAction(\farm\FarmUi::urlCultivationSequences($data->eFarm));
		}

		$data->search = new Search([
			'plant' => \plant\PlantLib::getByFarm($data->eFarm, id: GET('plant')),
			'name' => GET('name'),
			'use' => GET('use'),
			'status' => GET('status', default: \production\Sequence::ACTIVE),
			'tool' => \farm\ToolLib::getByFarm($data->eFarm, id: GET('tool')),
		]);

		$data->emptySearch = $data->search->empty(['status']);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);
		$data->ccCrop = \production\CropLib::getByFarm($data->eFarm, $data->cActionMain, TRUE, search: $data->search);

		throw new ViewAction($data, ':sequence');

	})
	->get(['/ferme/{id}/especes', '/ferme/{id}/especes/{status}'], function($data) {

		\farm\FarmerLib::setView('viewCultivation', $data->eFarm, \farm\Farmer::PLANT);

		$data->plants = \plant\PlantLib::countByFarm($data->eFarm);

		if(
			get_exists('status') and
			$data->plants[\plant\Plant::INACTIVE] === 0
		) {
			throw new RedirectAction(\farm\FarmUi::urlCultivationPlants($data->eFarm));
		}

		$data->search = new Search([
			'id' => GET('plantId', '?int'),
			'status' => GET('status', default: \plant\Plant::ACTIVE),
		]);

		$data->cPlant = \plant\PlantLib::getByFarm($data->eFarm, selectMetadata: TRUE, search: $data->search);

		if(
			$data->search->get('id') and
			$data->cPlant->notEmpty()
		) {
			$data->search->set('id', $data->cPlant->first());
		}

		throw new ViewAction($data, ':plant');

	})
	->get([
		'/ferme/{id}/ventes',
		'/ferme/{id}/ventes/particuliers',
		'/ferme/{id}/ventes/professionnels',
		], function($data) {

		$data->eFarm->validate('canSelling');

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::SALE);

		switch($data->route) {

			case '/ferme/{id}/ventes' :
				$data->type = NULL;
				\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::ALL);
				break;

			case '/ferme/{id}/ventes/particuliers' :
				$data->type = \selling\Sale::PRIVATE;
				\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::PRIVATE);
				break;

			case '/ferme/{id}/ventes/professionnels' :
				$data->type = \selling\Sale::PRO;
				\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::PRO);
				break;

		}

		$data->page = GET('page', 'int');

		if($data->page === 0) {
			$data->nextSales = \selling\SaleLib::getNextByFarm($data->eFarm, $data->type);
		} else {
			$data->nextSales = [];
		}

		$data->search = new Search([
			'document' => GET('document', '?int'),
			'ids' => GET('ids'),
			'customerName' => GET('customerName'),
			'deliveredAt' => GET('deliveredAt'),
			'preparationStatus' => GET('preparationStatus'),
			'paymentMethod' => GET('paymentMethod'),
		], GET('sort'));

		[$data->cSale, $data->nSale] = \selling\SaleLib::getByFarm($data->eFarm, $data->type, $data->page * 100, 100, $data->search);

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data, ':sellingSales');

	})
	->get('/ferme/{id}/clients', function($data) {

		$data->eFarm->validate('canSelling');
		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::CUSTOMER);

		$data->page = GET('page', 'int');

		$data->search = new Search([
			'name' => GET('name'),
			'email' => GET('email'),
			'category' => GET('category')
		], GET('sort', default: 'name'));

		[$data->cCustomer, $data->nCustomer] = \selling\CustomerLib::getByFarm($data->eFarm, selectPrices: TRUE, selectSales: TRUE, selectInvite: TRUE, page: $data->page, search: $data->search);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/produits', function($data) {

		$data->eFarm->validate('canSelling');

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::PRODUCT);

		$data->search = new Search([
			'name' => GET('name'),
			'plant' => GET('plant')
		], GET('sort', default: 'name'));

		$data->cProduct = \selling\ProductLib::getByFarm($data->eFarm, selectSales: TRUE, search: $data->search);

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/boutiques', function($data) {

		$data->eFarm->validate('canSelling');

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::SHOP);

		// Liste des boutiques
		$data->cShop = \shop\ShopLib::getForList($data->eFarm);

		// Boutique sélectionnée
		if(get_exists('shop')) {
			$eShop = GET('shop', 'shop\Shop');
			$data->eShop = $data->cShop[$eShop['id']] ?? ($data->cShop->empty() ? new \shop\Shop() : $data->cShop->first());
		} else if($data->cShop->count() === 1) {
			$data->eShop = $data->cShop->first();
		} else {
			$data->eShop = new \shop\Shop();
		}

		if($data->eShop->notEmpty()) {

			\farm\FarmerLib::setView('viewShop', $data->eFarm, $data->eShop);

			$data->eShop['ccPoint'] = \shop\PointLib::getByShop($data->eShop, onlyActive: FALSE);

			// Liste des dates de la boutique sélectionnée
			$data->eShop['cDate'] = \shop\DateLib::getByShop($data->eShop);

		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/factures', function($data) {

		$data->eFarm->validate('canSelling');

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::SALE);
		\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::INVOICE);

		$data->page = GET('page', 'int');

		$data->search = new Search([
			'document' => GET('document'),
			'customer' => GET('customer'),
			'date' => GET('date'),
			'paymentStatus' => \selling\Invoice::GET('paymentStatus', 'paymentStatus')
		], GET('sort'));

		[$data->cInvoice, $data->nInvoice] = \selling\InvoiceLib::getByFarm($data->eFarm, selectSales: TRUE, page: $data->page, search: $data->search);

		$data->transferMonth = date('Y-m', strtotime("last month"));
		$data->transfer = \selling\InvoiceLib::getPendingTransfer($data->eFarm, $data->transferMonth);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/etiquettes', function($data) {

		$data->eFarm->validate('canSelling');

		\farm\FarmerLib::setView('viewSelling', $data->eFarm, \farm\Farmer::SALE);
		\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::LABEL);

		$data->cSale = \selling\SaleLib::getByFarmForLabel($data->eFarm);

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get(['/ferme/{id}/planning/{view}', '/ferme/{id}/planning/{view}/{period}', '/ferme/{id}/planning/{view}/{period}/{subPeriod}'], function($data) {

		$data->eFarm->validate('canPlanning');

		$view = GET('view');

		switch($view) {

			case \farm\Farmer::DAILY :
				$period = GET('period', 'string', currentWeek());
				$subPeriod = NULL;
				break;

			case \farm\Farmer::WEEKLY :
				$period = GET('period', 'string', currentWeek());
				$subPeriod = NULL;
				break;

			case \farm\Farmer::YEARLY :
				$period = GET('period', 'string', date('Y'));
				$subPeriod = GET('subPeriod', 'string', date('n'));
				break;
				
			default :
				throw new NotExpectedAction('Invalid period');

		}

		\farm\FarmerLib::setView('viewPlanning', $data->eFarm, $view);

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, index: 'id');
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm);
		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm);

		\map\PlotLib::putFromZone($data->cZone);

		$search = get_exists('search') ? [] : (Setting::get('main\viewPlanningSearch') ?? []);

		$search = array_filter([
			'plant' => GET('plant', '?int'),
			'action' => GET('action', '?int'),
			'plot' => GET('plot', '?int')
		]) + $search;

		foreach($search as $key => $value) {

			try {

				$search[$key] = match($key) {
					'plant' => $value ? \plant\PlantLib::getByFarm($data->eFarm, id: $value) : new \plant\Plant(),
					'action' => $data->cAction[$value] ?? new \farm\Action(),
					'plot' => $value ? \map\PlotLib::getById($value)->validate('canRead') : new \map\Plot(),
				};

			} catch(Exception) {
				// On ignore d'éventuels champs supplémentaires
			}

		}

		$data->search = new Search($search);

		\farm\FarmerLib::setView('viewPlanningSearch', $data->eFarm, $data->search->toArray() ?: NULL);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);

		switch(Setting::get('main\viewPlanning')) {

			case \farm\Farmer::DAILY :

				$data->date = date('Y-m-d', strtotime(date('Y-m-d', strtotime($period)).' + '.($subPeriod - 1).' DAYS'));
				$data->week = $period;

				\series\RepeatLib::createForWeek($data->eFarm, $data->week);

				if(get_exists('user')) {
					$data->eUserSelected = GET('user', 'user\User');
					\farm\FarmerLib::setView('viewPlanningUser', $data->eFarm, $data->eUserSelected);
				} else {
					$data->eUserSelected = Setting::get('main\viewPlanningUser') ?? new \user\User();
				}

				$data->seasonsWithSeries = \series\SeriesLib::getSeasonsAround($data->eFarm, week_year($data->week));

				[
					$data->ccTimesheet,
					$data->cccTask
				] = \series\TaskLib::getForDaily($data->eFarm, $data->week, $data->eUserSelected, $data->cActionMain[ACTION_RECOLTE], $data->search);

				$data->cUserFarm = \farm\FarmerLib::getUsersByFarmForPeriod($data->eFarm, week_date_starts($data->week), week_date_ends($data->week), withPresenceAbsence: TRUE);

				$data->cccTaskAssign = \series\TaskLib::getForAssign($data->eFarm, $data->week);

				\hr\WorkingTimeLib::fillByWeekFromUsers($data->eFarm, $data->cUserFarm, $data->week);
				\series\TimesheetLib::fillTimesByDate($data->eFarm, $data->cUserFarm, $data->week);
				break;

			case \farm\Farmer::WEEKLY :

				$data->week = $period;

				\series\RepeatLib::createForWeek($data->eFarm, $data->week);

				$data->cccTask = \series\TaskLib::getForWeek($data->eFarm, $data->week, $data->cActionMain[ACTION_RECOLTE], $data->search);

				$data->seasonsWithSeries = \series\SeriesLib::getSeasonsAround($data->eFarm, week_year($data->week));

				$data->cUserFarm = \farm\FarmerLib::getUsersByFarmForPeriod($data->eFarm, week_date_starts($data->week), week_date_ends($data->week), withPresenceAbsence: TRUE);

				$data->eUserTime = $data->eUserOnline;
				$data->eUserTime['weekTimesheet'] = \series\TimesheetLib::getTimesByWeek($data->eFarm, $data->eUserTime, $data->week);
				$data->eUserTime['cWorkingTimeWeek'] = \hr\WorkingTimeLib::getByWeek($data->eFarm, $data->eUserTime, $data->week);
				$data->eUserTime['cPresence'] = $data->cUserFarm[$data->eUserTime['id']]['cPresence'] ?? new Collection();
				$data->eUserTime['cAbsence'] = $data->cUserFarm[$data->eUserTime['id']]['cAbsence'] ?? new Collection();
				break;

			case \farm\Farmer::YEARLY :

				$data->week = NULL;
				$data->year = (int)$period;
				$data->month = (int)$subPeriod;

				\series\RepeatLib::createForYear($data->eFarm, $data->year);

				$data->cUserFarm = \farm\FarmerLib::getUsersByFarmForPeriod($data->eFarm, $data->year.'-01-01', $data->year.'-12-31', withPresenceAbsence: TRUE);

				$data->ccTask = \series\TaskLib::getByYear($data->eFarm, $data->year, $data->search);

				break;

		}

		throw new ViewAction($data, ':planning');

	})
	->get('/ferme/{id}/taches/{week}/{action}', function($data) {

		$data->eFarm->validate('canPlanning');

		$data->week = \series\Task::GET('week', 'plannedWeek', currentWeek());
		$data->eAction = \farm\ActionLib::getById(GET('action'))->validate('canRead');

		\farm\ActionLib::getMainByFarm($data->eFarm);

		\series\RepeatLib::createForWeek($data->eFarm, $data->week);

		$data->cTask = \series\TaskLib::getByWeekAndAction($data->eFarm, $data->week, $data->eAction);

		foreach($data->cTask as $eTask) {
			if($eTask['cultivation']->notEmpty()) {
				$eTask['cultivation']['series'] = $eTask['series'];
				\series\CultivationLib::populateSliceStats($eTask['cultivation']);
			}
		}

		throw new ViewAction($data);

	})
	->get(['/ferme/{id}/series', '/ferme/{id}/series/{season}'], function($data) {

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));

		$view = GET('view');

		if(
			$view === \farm\Farmer::WORKING_TIME and
			$data->eFarm->hasFeatureTime() === FALSE
		) {
			$view = \farm\Farmer::AREA;
		}

		if(
			$view === \farm\Farmer::FORECAST and
			$data->eFarm->canAnalyze() === FALSE
		) {
			$view = \farm\Farmer::AREA;
		}

		\farm\FarmerLib::setView('viewCultivation', $data->eFarm, \farm\Farmer::SERIES);
		\farm\FarmerLib::setView('viewSeries', $data->eFarm, $view);

		if(get_exists('harvestExpected')) {
			\farm\FarmerLib::setView('viewPlanningHarvestExpected', $data->eFarm, GET('harvestExpected', [\farm\Farmer::TOTAL, \farm\Farmer::WEEKLY], \farm\Farmer::TOTAL));
		}

		if(get_exists('field')) {
			\farm\FarmerLib::setView('viewPlanningField', $data->eFarm, GET('field', [\farm\Farmer::VARIETY, \farm\Farmer::SOIL], \farm\Farmer::SOIL));
		}

		$data->cSeriesImport = \series\SeriesLib::getImportPerennial($data->eFarm, $data->season);

		$data->search = new Search([
			'supplier' => GET('supplier', 'farm\Supplier'),
			'bedWidth' => GET('bedWidth', '?int')
		]);

		$data->nSeries = \series\SeriesLib::countByFarm($data->eFarm, $data->season);

		if($data->nSeries === 0) {
			$data->firstSeries = (\series\SeriesLib::countByFarm($data->eFarm) === 0);
		} else {
			$data->firstSeries = FALSE;
		}

		$data->cSupplier = new Collection();

		\farm\ActionLib::getMainByFarm($data->eFarm);

		switch(Setting::get('main\viewSeries')) {

			case \farm\Farmer::AREA :
				$data->ccCultivation = \series\CultivationLib::getForArea($data->eFarm, $data->season, $data->search);
				$data->ccForecast = \plant\ForecastLib::getReadOnlyByFarm($data->eFarm, $data->season);
				break;

			case \farm\Farmer::FORECAST :
				if(get_exists('help')) {
					\Cache::redis()->delete('help-forecast-'.$data->eFarm['id']);
				}
				$cccCultivation = \series\CultivationLib::getForForecast($data->eFarm, $data->season);
				$data->ccForecast = \plant\ForecastLib::getByFarm($data->eFarm, $data->season, $cccCultivation);
				break;

			case \farm\Farmer::SEEDLING :
				$data->cSupplier = \farm\SupplierLib::getByFarm($data->eFarm);
				$data->items = \series\CultivationLib::getForSeedling($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::HARVESTING :
				$data->ccCultivation = \series\CultivationLib::getForHarvesting($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::WORKING_TIME :
				$data->ccCultivation = \series\CultivationLib::getWorkingTimeByFarm($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::TOOL :
				$data->search->set('tool', \farm\ToolLib::getOneByFarm($data->eFarm, GET('tool')));
				$data->cSeries = \series\CultivationLib::getToolByFarm($data->eFarm, $data->season, $data->search);
				break;

		}

		throw new ViewAction($data, ':series');

	})
	->get(['/ferme/{id}/carte', '/ferme/{id}/carte/{season}'], function($data) {

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

		\farm\FarmerLib::setView('viewMap', $data->eFarm, \farm\Farmer::CARTOGRAPHY);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		if($data->cZone->empty()) {
			throw new ViewAction($data, ':mapEmpty');
		}

		if($data->cZone->count() === 1) {
			$data->eZone = $data->cZone->first();
		} else {

			$data->eZone = new \map\Zone();

			if(get_exists('zone')) {
				$zone = GET('zone', 'int');
				$data->eZone = $data->cZone->find(fn($eZone) => $eZone['id'] === $zone, limit: 1, clone: FALSE, default: new \map\Zone());
			}

		}

		\map\PlotLib::putFromZone($data->cZone, withBeds: TRUE, withDraw: TRUE, season: $data->season);

		throw new ViewAction($data, ':cartography');

	})
	->get(['/ferme/{id}/assolement', '/ferme/{id}/assolement/{season}'], function($data) {

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

		\farm\FarmerLib::setView('viewMap', $data->eFarm, \farm\Farmer::SOIL);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		if($data->cZone->empty()) {
			throw new ViewAction($data, ':mapEmpty');
		}

		\map\GreenhouseLib::putFromZone($data->cZone);

		$seasonsSeries = [$data->season + 1, $data->season, $data->season - 1];

		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, $seasonsSeries);

		throw new ViewAction($data, ':soil');


	})
	->get(['/ferme/{id}/rotation', '/ferme/{id}/rotation/{season}'], function($data) {

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

		$data->selectedSeasons = $data->eFarm->getRotationSeasons($data->season);

		\farm\FarmerLib::setView('viewMap', $data->eFarm, \farm\Farmer::HISTORY);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		if($data->cZone->empty()) {
			throw new ViewAction($data, ':mapEmpty');
		}

		\map\GreenhouseLib::putFromZone($data->cZone);

		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, $data->selectedSeasons, onlySeries: TRUE);

		$data->search = new Search([
			'cFamily' => \plant\FamilyLib::getList(),
			'family' => GET('family', 'plant\Family'),
			'seen' => GET('seen', '?int'),
			'bed' => GET('bed', 'bool')
		]);

		\series\PlaceLib::filterRotationsByFamily($data->eFarm, $data->cZone, $data->search);

		throw new ViewAction($data, ':history');


	})
	->get(['/ferme/{id}/analyses/planning', '/ferme/{id}/analyses/planning/{year}', '/ferme/{id}/analyses/planning/{year}/{category}'], function($data) {

		$data->eFarm->validate('canAnalyze');

		if($data->eFarm->hasFeatureTime() === FALSE) {
			throw new RedirectAction(\farm\FarmUi::urlAnalyzeCultivation($data->eFarm));
		}

		\farm\FarmerLib::setView('viewAnalyze', $data->eFarm, \farm\Farmer::WORKING_TIME);

		$data->years = \series\AnalyzeLib::getYears($data->eFarm);

		if($data->years) {

			if(get_exists('year')) {
				$selectedYear = GET('year', 'int');
				$data->year = in_array($selectedYear, $data->years) ? $selectedYear : first($data->years);
				\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->year);
			} else {
				$currentYear = Setting::get('main\viewAnalyzeYear');
				$data->year = in_array($currentYear, $data->years) ? $currentYear : first($data->years);
			}

			$data->category = GET('category', 'string', Setting::get('main\viewPlanningCategory'));
			$data->month = GET('month', '?int');
			$data->week = GET('week', '?string');

			switch($data->category) {

				case \farm\Farmer::TIME :

					$data->monthly = get_exists('monthly');

					$data->globalTime = \series\AnalyzeLib::getGlobalWorkingTime($data->eFarm, $data->year, $data->month, $data->week);
					$data->cTimesheetAction = \series\AnalyzeLib::getActionTimesheet($data->eFarm, $data->year, $data->month, $data->week);
					$data->cTimesheetCategory = \series\AnalyzeLib::getCategoryTimesheet($data->eFarm, $data->year, $data->month, $data->week);
					$data->cTimesheetPlant = \series\AnalyzeLib::getPlantsTimesheet($data->eFarm, $data->year, $data->month, $data->week);
					$data->cTimesheetSeries = \series\AnalyzeLib::getSeriesTimesheet($data->eFarm, $data->year, $data->month, $data->week);

					if(
						($data->month === NULL and $data->week === NULL) or
						$data->monthly
					) {
						$data->ccTimesheetSeriesMonthly = \series\AnalyzeLib::getSeriesMonthly($data->eFarm, $data->year);
					} else {
						$data->ccTimesheetSeriesMonthly = new Collection();
					}

					if($data->monthly) {
						$data->cccTimesheetActionMonthly = \series\AnalyzeLib::getActionMonthly($data->eFarm, $data->year);
						$data->ccTimesheetCategoryMonthly = \series\AnalyzeLib::getCategoryMonthly($data->eFarm, $data->year);
						$data->ccTimesheetPlantMonthly = \series\AnalyzeLib::getMonthlyPlantsTimesheet($data->eFarm, $data->year);
					} else {
						$data->cccTimesheetActionMonthly = new Collection();
						$data->ccTimesheetCategoryMonthly = new Collection();
						$data->ccTimesheetPlantMonthly = new Collection();
					}

					break;

				case \farm\Farmer::TEAM :
					$data->ccWorkingTimeMonthly = \series\AnalyzeLib::getMonthlyWorkingTime($data->eFarm, $data->year);
					$data->workingTimeWeekly = \series\AnalyzeLib::getWeeklyWorkingTime($data->eFarm, $data->year);
					$data->ccTimesheetAction = \series\AnalyzeLib::getActionTimesheetByUser($data->eFarm, $data->year);
					break;

				case \farm\Farmer::PACE :
					$data->cAction = \farm\ActionLib::getByFarmWithPace($data->eFarm);
					$data->ccPlant = \series\CultivationLib::getPaceByFarm($data->eFarm, $data->year, $data->cAction);

					$data->yearCompare = GET('compare', '?int');

					if($data->yearCompare !== NULL) {
						$data->ccPlantCompare = \series\CultivationLib::getPaceByFarm($data->eFarm, $data->yearCompare, $data->cAction);
					} else {
						$data->ccPlantCompare = new Collection();
					}
					break;

				case \farm\Farmer::PERIOD :
					$data->cWorkingTimeMonth = \series\AnalyzeLib::getFarmMonths($data->eFarm, $data->year);
					break;

				default :
					throw new NotExpectedAction('Invalid category');

			}

		} else {
			$data->year = NULL;
			$data->category = NULL;
		}

		throw new ViewAction($data, ':analyzeWorkingTime');

	})
	->get(['/ferme/{id}/analyses/rapports', '/ferme/{id}/analyses/rapports/{season}'], function($data) {

		$data->eFarm->validate('canAnalyze');

		if(get_exists('season')) {
			$selectedSeason = GET('season', 'int');
			$data->season = ($selectedSeason >= $data->eFarm['seasonFirst'] and $selectedSeason <= $data->eFarm['seasonLast']) ? $selectedSeason : $data->eFarm['seasonLast'];
			\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->season);
		} else {
			$currentSeason = Setting::get('main\viewAnalyzeYear');
			$data->season = ($currentSeason >= $data->eFarm['seasonFirst'] and $currentSeason <= $data->eFarm['seasonLast']) ? $currentSeason : $data->eFarm['seasonLast'];
		}

		\farm\FarmerLib::setView('viewAnalyze', $data->eFarm, \farm\Farmer::REPORT);

		$data->search = new Search([
			'plant' => get_exists('plant') ? \plant\PlantLib::getById(GET('plant')) : NULL,
		], REQUEST('sort'));

		$data->cReport = \analyze\ReportLib::getByFarm($data->eFarm, $data->season, $data->search);

		throw new ViewAction($data, ':analyzeReport');

	})
	->get([
		'/ferme/{id}/analyses/ventes',
		'/ferme/{id}/analyses/ventes/{year}/{category}',
		'/ferme/{id}/analyses/ventes/{year}/{category}/compare/{compare}',
		], function($data) {

		\farm\FarmerLib::setView('viewAnalyze', $data->eFarm, \farm\Farmer::SALES);

		$data->years = \selling\AnalyzeLib::getYears($data->eFarm);

		if($data->years) {

			if(get_exists('year')) {
				$selectedYear = GET('year', 'int');
				$data->year = array_key_exists($selectedYear, $data->years) ? $selectedYear : array_key_first($data->years);
				\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->year);
			} else {
				$currentYear = Setting::get('main\viewAnalyzeYear');
				$data->year = array_key_exists($currentYear, $data->years) ? $currentYear : array_key_first($data->years);
			}
			$data->month = GET('month', '?int');
			$data->week = GET('week', '?string');

			$data->category = GET('category', 'string', Setting::get('main\viewSellingCategory'));

			if(
				$data->category === \farm\Farmer::CUSTOMER or
				$data->category === \farm\Farmer::SHOP
			) {
				$data->eFarm->validate('canPersonalData');
			}

			$years = [];
			for($year = $data->year - 3; $year <= $data->year + 3; $year++) {
				$years[] = $year;
			}

			$data->search = new Search([
				'type' => \selling\Customer::GET('type', 'type'),
			], REQUEST('sort'));

			switch($data->category) {

				case \farm\Farmer::ITEM :

					$data->yearCompare = GET('compare', '?int');

					if($data->yearCompare !== NULL) {
						$years[] = $data->yearCompare;
					}

					$data->cSaleTurnover = \selling\AnalyzeLib::getGlobalTurnover($data->eFarm, $years, $data->month, $data->week);
					$data->cItemProduct = \selling\AnalyzeLib::getFarmProducts($data->eFarm, $data->year, $data->month, $data->week, $data->search);
					$data->cPlant = \selling\AnalyzeLib::getFarmPlants($data->eFarm, $data->year, $data->month, $data->week, $data->search);

					\selling\AnalyzeLib::addShipping($data->cSaleTurnover, $data->cItemProduct, $data->year);

					$data->cItemProductCompare = new Collection();
					$data->cPlantCompare = new Collection();

					if($data->yearCompare !== NULL) {

						if(array_key_exists($data->yearCompare, $data->years) and $data->yearCompare !== $data->year) {

							$data->cItemProductCompare = \selling\AnalyzeLib::getFarmProducts($data->eFarm, $data->yearCompare, $data->month, $data->week ? str_replace($data->year, $data->yearCompare, $data->week) : NULL, $data->search);

							$data->cPlantCompare = \selling\AnalyzeLib::getFarmPlants($data->eFarm, $data->yearCompare, $data->month, $data->week ? str_replace($data->year, $data->yearCompare, $data->week) : NULL, $data->search);

							\selling\AnalyzeLib::addShipping($data->cSaleTurnover, $data->cItemProductCompare, $data->yearCompare);

						} else {
							$data->yearCompare = NULL;
						}

					}

					$data->monthly = $data->yearCompare ? NULL : GET('monthly', ['turnover', 'quantity', 'average'], NULL);

					if($data->monthly) {
						$data->cItemProductMonthly = \selling\AnalyzeLib::getMonthlyFarmProducts($data->eFarm, $data->year, $data->search);
						$data->cccItemPlantMonthly = \selling\AnalyzeLib::getMonthlyFarmPlants($data->eFarm, $data->year, $data->search);
					} else {
						$data->cItemProductMonthly = new Collection();
						$data->cccItemPlantMonthly = new Collection();
					}

					break;

				case \farm\Farmer::CUSTOMER :

					$data->ccItemCustomer = \selling\AnalyzeLib::getFarmCustomers($data->eFarm, $data->year, $data->month, $data->week, $data->search);

					$data->monthly = GET('monthly', ['turnover'], NULL);

					if($data->monthly) {
						$data->ccItemCustomerMonthly = \selling\AnalyzeLib::getMonthlyFarmCustomers($data->eFarm, $data->year, $data->search);
					} else {
						$data->ccItemCustomerMonthly = new Collection();
					}
					break;

				case \farm\Farmer::SHOP :

					$data->cShop = \shop\ShopLib::getByFarm($data->eFarm);

					if(get_exists('shop')) {
						$selectedshop = GET('shop', 'int');
						$data->eShop = $data->cShop[$selectedshop] ?? $data->cShop->first();
					} else {
						$data->eShop = $data->cShop->first();
					}


					$data->cSaleTurnover = \selling\AnalyzeLib::getShopTurnover($data->eShop, $years, $data->month, $data->week);
					$data->cItemProduct = \selling\AnalyzeLib::getShopProducts($data->eShop, $data->year, $data->month, $data->week);
					$data->cPlant = \selling\AnalyzeLib::getShopPlants($data->eShop, $data->year, $data->month, $data->week);
					$data->ccItemCustomer = \selling\AnalyzeLib::getShopCustomers($data->eShop, $data->year, $data->month, $data->week);

					$data->monthly = GET('monthly', ['turnover', 'quantity', 'average'], NULL);

					if($data->monthly) {
						$data->cItemProductMonthly = \selling\AnalyzeLib::getMonthlyShopProducts($data->eShop, $data->year)  ;
						$data->cccItemPlantMonthly = \selling\AnalyzeLib::getMonthlyShopPlants($data->eShop, $data->year);
					} else {
						$data->cItemProductMonthly = new Collection();
						$data->cccItemPlantMonthly = new Collection();
					}

					\selling\AnalyzeLib::addShipping($data->cSaleTurnover, $data->cItemProduct, $data->year);

					break;

				case \farm\Farmer::PERIOD :
					$data->cItemMonth = \selling\AnalyzeLib::getFarmMonths($data->eFarm, $data->year);
					$data->cItemMonthBefore = \selling\AnalyzeLib::getFarmMonths($data->eFarm, $data->year - 1);
					$data->cItemWeek = \selling\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year);
					$data->cItemWeekBefore = \selling\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year - 1);
					break;

				default :
					throw new NotExpectedAction('Invalid category');

			}

		} else {
			$data->year = NULL;
			$data->category = NULL;
			$data->month = NULL;
			$data->week = NULL;
		}

		throw new ViewAction($data, ':analyzeSelling');

	})
	->get(['/ferme/{id}/analyses/cultures', '/ferme/{id}/analyses/cultures/{season}/{category}'], function($data) {

		$data->eFarm->validate('canAnalyze');

		\farm\FarmerLib::setView('viewAnalyze', $data->eFarm, \farm\Farmer::CULTIVATION);

		$data->seasons = $data->eFarm->getSeasons();

		if(get_exists('season')) {
			$selectedSeason = GET('season', 'int');
			$data->season = in_array($selectedSeason, $data->seasons) ? $selectedSeason : first($data->seasons);
			\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->season);
		} else {
			$currentYear = Setting::get('main\viewAnalyzeYear');
			$data->season = in_array($currentYear, $data->seasons) ? $currentYear : last($data->seasons);
		}

		$data->category = GET('category', 'string', Setting::get('main\viewCultivationCategory'));

		switch($data->category) {

			case \farm\Farmer::AREA :
				$data->area = \plant\AnalyzeLib::getArea($data->eFarm, $data->seasons);
				break;

			case \farm\Farmer::PLANT :

				$data->search = new Search([
					'cycle' => \series\Series::GET('cycle', 'cycle'),
					'use' => \series\Series::GET('use', 'use'),
				], REQUEST('sort'));

				$data->ccCultivationPlant = \plant\AnalyzeLib::getPlants($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::FAMILY :

				$data->area = \plant\AnalyzeLib::getArea($data->eFarm, $data->seasons);

				$data->search = new Search([
					'cycle' => \series\Series::GET('cycle', 'cycle'),
					'use' => \series\Series::GET('use', 'use'),
				], REQUEST('sort'));

				$data->ccCultivationFamily = \plant\AnalyzeLib::getFamilies($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::ROTATION :

				$data->selectedSeasons = $data->eFarm->getRotationSeasons($data->season);
				$data->selectedSeasons = array_reverse($data->selectedSeasons);

				$data->area = \plant\AnalyzeLib::getArea($data->eFarm, [$data->season])[$data->season];

				// Planches de l'année
				$data->cBed = \map\BedLib::getByFarm($data->eFarm, $data->season);

				// Recherche des cultures dans les planches de l'année
				$ccccPlaceRotation = \series\PlaceLib::getForRotations($data->eFarm, $data->cBed, $data->selectedSeasons);

				$data->cFamily = new Collection();
				$ccccPlaceRotation->map(fn($ePlace) => $ePlace['family']->notEmpty() ? ($data->cFamily[$ePlace['family']['id']] = $ePlace['family']) : NULL, 4);
				$data->cFamily->sort('name');

				$data->rotations = \series\PlaceLib::getRotationsStats($ccccPlaceRotation);
				break;

			default :
				throw new NotExpectedAction('Invalid category');

		}


		throw new ViewAction($data, ':analyzeCultivation');

	})
	->get('/ferme/{id}/configuration', function($data) {

		$data->eFarm->validate('canManage');

		\farm\FarmerLib::setView('viewSettings', $data->eFarm, \farm\Farmer::SETTINGS);

		$data->eNews = \website\NewsLib::getLastForBlog();

		throw new ViewAction($data);

	});
?>
