<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(GET('id'))->validate('canWrite');
		$data->eFarm->saveFeaturesAsSettings();

		$data->tip = \farm\TipLib::pickRandom($data->eUserOnline, $data->eFarm);
		$data->tipNavigation = 'close';

	}))
	->get(['/ferme/{id}/itineraires', '/ferme/{id}/itineraires/{status}'], function($data) {

		$data->sequences = \sequence\SequenceLib::countByFarm($data->eFarm);

		if(
			get_exists('status') and
			$data->sequences[\sequence\Sequence::CLOSED] === 0
		) {
			throw new RedirectAction(\farm\FarmUi::urlCultivationSequences($data->eFarm));
		}

		$data->search = new Search([
			'plant' => \plant\PlantLib::getByFarm($data->eFarm, id: GET('plant')),
			'name' => GET('name'),
			'use' => GET('use'),
			'status' => GET('status', default: \sequence\Sequence::ACTIVE),
			'tool' => \farm\ToolLib::getByFarm($data->eFarm, id: GET('tool')),
		]);

		$data->emptySearch = $data->search->empty(['status']);

		$data->cActionMain = \farm\ActionLib::getMainByFarm($data->eFarm);
		$data->ccCrop = \sequence\CropLib::getByFarm($data->eFarm, $data->cActionMain, TRUE, search: $data->search);

		throw new ViewAction($data, ':sequence');

	})
	->get([
		'/ferme/{id}/ventes',
		'/ferme/{id}/ventes/particuliers',
		'/ferme/{id}/ventes/professionnels',
		], function($data) {

		$data->eFarm->validate('canSelling');

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

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL);
		[$data->cSale, $data->nSale] = \selling\SaleLib::getByFarm($data->eFarm, $data->type, $data->page * 100, 100, $data->search);

		throw new ViewAction($data, ':sellingSales');

	})
	->get('/ferme/{id}/clients', function($data) {

		$data->eFarm->validate('canSelling');

		$data->page = GET('page', 'int');

		$data->search = new Search([
			'name' => GET('name'),
			'email' => GET('email'),
			'category' => GET('category')
		], GET('sort', default: 'lastName'));

		[$data->cCustomer, $data->nCustomer] = \selling\CustomerLib::getByFarm($data->eFarm, selectPrices: TRUE, selectSales: TRUE, selectInvite: TRUE, page: $data->page, search: $data->search);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/produits', function($data) {

		$data->eFarm->validate('canSelling');

		$data->cCategory = \selling\CategoryLib::getByFarm($data->eFarm, index: 'id');

		if(get_exists('category')) {

			$data->eCategory = \selling\Product::GET('category', 'category', new \selling\Category());

			if($data->eCategory->notEmpty()) {
				$data->cCategory->validateOffset($data->eCategory);
			}

			\farm\FarmerLib::setView('viewSellingCategoryCurrent', $data->eFarm, $data->eCategory);

		} else {
			$data->eCategory = $data->eFarm->getView('viewSellingCategoryCurrent');
		}

		$data->search = new Search([
			'category' => $data->eCategory,
			'composition' => GET('composition'),
			'name' => GET('name'),
			'plant' => GET('plant')
		], GET('sort', default: 'name'));


		$data->products = \selling\ProductLib::countByFarm($data->eFarm, $data->search);
		$data->cProduct = \selling\ProductLib::getByFarm($data->eFarm, $data->eCategory, selectSales: TRUE, search: $data->search);

		if($data->cProduct->empty()) {
			$data->cUnit = \selling\UnitLib::getByFarm($data->eFarm);
		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/stocks', function($data) {

		$data->eFarm->validate('canSelling');
		$data->eFarm['stockNotesUpdatedBy'] = \user\UserLib::getById($data->eFarm['stockNotesUpdatedBy']);

		$data->search = new Search(sort: GET('sort'));

		$data->cProduct = \selling\StockLib::getProductsByFarm($data->eFarm, $data->search);

		$data->ccItemPast = \selling\ItemLib::getForPastStock($data->eFarm);
		$data->cItemFuture = \selling\ItemLib::getForFutureStock($data->eFarm);

		$data->cStockBookmark = \selling\StockLib::getBookmarksByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/boutiques', function($data) {

		$data->eFarm->validate('canSelling');

		// Liste des boutiques
		$data->ccShop = \shop\ShopLib::getList($data->eFarm);

		// Une seule boutique
		if(
			$data->ccShop['admin']->empty() and
			$data->ccShop['selling']->count() === 1
		) {
			throw new RedirectAction(\shop\ShopUi::adminUrl($data->eFarm, $data->ccShop['selling']->first()));
		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/catalogues', function($data) {

		$data->eFarm->validate('canSelling');

		$data->products = \shop\ProductLib::countCatalogsByFarm($data->eFarm);
		$data->cCatalog = \shop\CatalogLib::getByFarm($data->eFarm, index: 'id');
		$data->eCatalogSelected = new \shop\Catalog();

		if(get_exists('catalog')) {

			$eCatalog = GET('catalog', 'shop\Catalog', fn() => new \shop\Catalog());

			if($eCatalog->notEmpty()) {
				$data->cCatalog->validateOffset($eCatalog);
				$data->eCatalogSelected = $data->cCatalog[$eCatalog['id']];
			}

			\farm\FarmerLib::setView('viewShopCatalogCurrent', $data->eFarm, $eCatalog);

		} else if($data->eFarm->getView('viewShopCatalogCurrent')->notEmpty()) {

			$eCatalog = $data->eFarm->getView('viewShopCatalogCurrent');

			$data->eCatalogSelected = $data->cCatalog[$eCatalog['id']] ?? new \shop\Catalog();

		} else {

			$data->eCatalogSelected = $data->cCatalog->notEmpty() ?
				$data->cCatalog->first() :
				new \shop\Catalog();

		}

		if($data->eCatalogSelected->notEmpty()) {
			$data->eCatalogSelected['cProduct'] = \shop\ProductLib::getByCatalog($data->eCatalogSelected, onlyActive: FALSE);
			$data->eCatalogSelected['cCategory'] = \selling\CategoryLib::getByFarm($data->eFarm, index: 'id');
			$data->eCatalogSelected['cCustomer'] = \selling\CustomerLib::getLimitedByProducts($data->eCatalogSelected['cProduct']);
		}

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/livraison', function($data) {

		$data->eFarm->validate('canSelling');

		$data->ccPoint = \shop\PointLib::getByFarm($data->eFarm);
		$data->pointsUsed = \shop\PointLib::getUsedByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/factures', function($data) {

		$data->eFarm->validate('canSelling');

		$data->page = GET('page', 'int');

		$data->search = new Search([
			'invoice' => GET('invoice'),
			'document' => GET('document'),
			'customer' => GET('customer'),
			'date' => GET('date'),
			'paymentStatus' => \selling\Invoice::GET('paymentStatus', 'paymentStatus')
		], GET('sort'));

		[$data->cInvoice, $data->nInvoice] = \selling\InvoiceLib::getByFarm($data->eFarm, selectSales: TRUE, page: $data->page, search: $data->search);

		$data->hasInvoices = (
			$data->cInvoice->notEmpty() or
			$data->search->notEmpty()
		);
		$data->hasSales = (
			$data->cInvoice->notEmpty() or
			\selling\InvoiceLib::existsQualifiedSales($data->eFarm)
		);

		$data->transferMonth = date('Y-m', strtotime("last month"));
		$data->transfer = \selling\InvoiceLib::getPendingTransfer($data->eFarm, $data->transferMonth);

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/etiquettes', function($data) {

		$data->eFarm->validate('canSelling');

		\farm\FarmerLib::setView('viewSellingSales', $data->eFarm, \farm\Farmer::LABEL);

		$data->cSale = \selling\SaleLib::getByFarmForLabel($data->eFarm);

		throw new ViewAction($data);

	})
	->get(['/ferme/{id}/planning/{view}', '/ferme/{id}/planning/{view}/{period}', '/ferme/{id}/planning/{view}/{period}/{subPeriod}'], function($data) {

		$data->eFarm->validate('canPlanning');

		$view = GET('view');

		switch($view) {

			case \farm\Farmer::DAILY :
			case \farm\Farmer::WEEKLY :
				$period = GET('period', fn($value) => Filter::check('week', $value), currentWeek());
				$subPeriod = NULL;

				if($data->eFarm->isSeasonValid(date_year($period)) === FALSE) {
					throw new NotExpectedAction('Invalid period');
				}
				break;

			case \farm\Farmer::YEARLY :
				$period = GET('period', 'int', (int)date('Y'));
				$subPeriod = GET('subPeriod', 'string', date('n'));

				if($data->eFarm->isSeasonValid($period) === FALSE) {
					throw new NotExpectedAction('Invalid period');
				}
				break;
				
			default :
				throw new NotExpectedAction('Invalid period');

		}

		\farm\FarmerLib::setView('viewPlanning', $data->eFarm, $view);

		$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, index: 'id');
		$data->cCategory = \farm\CategoryLib::getByFarm($data->eFarm);
		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm);

		\map\PlotLib::putFromZone($data->cZone);

		$search = get_exists('search') ? [] : ($data->eFarm->getView('viewPlanningSearch') ?? []);

		$search = array_filter([
			'plant' => GET('plant', '?int'),
			'farmer' => GET('farmer', '?int'),
			'action' => GET('action', '?int'),
			'plot' => GET('plot', '?int')
		]) + $search;

		foreach($search as $key => $value) {

			try {

				$search[$key] = match($key) {
					'plant' => $value ? \plant\PlantLib::getByFarm($data->eFarm, id: $value) : new \plant\Plant(),
					'farmer' => $value ? new \user\User(['id' => $value]) : new \user\User(),
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

		switch($data->eFarm->getView('viewPlanning')) {

			case \farm\Farmer::DAILY :

				$data->date = date('Y-m-d', strtotime(date('Y-m-d', strtotime($period)).' + '.($subPeriod - 1).' DAYS'));
				$data->week = $period;

				\series\RepeatLib::createForWeek($data->eFarm, $data->week);

				if($data->eFarm->canManage()) {

					if(get_exists('user')) {
						$data->eUserSelected = GET('user', 'user\User');
						\farm\FarmerLib::setView('viewPlanningUser', $data->eFarm, $data->eUserSelected);
					} else {
						$data->eUserSelected = $data->eFarm->getView('viewPlanningUser') ?? new \user\User();
					}

				} else {
					$data->eUserSelected = $data->eUserOnline;
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
		$data->eAction->validateProperty('farm', $data->eFarm);

		\farm\ActionLib::getMainByFarm($data->eFarm);

		\series\RepeatLib::createForWeek($data->eFarm, $data->week);

		$data->cTask = \series\TaskLib::getByWeekAndAction($data->eFarm, $data->week, $data->eAction);

		foreach($data->cTask as $eTask) {

			if($eTask['cultivation']->notEmpty()) {

				$eTask['cultivation']['series'] = $eTask['series'];
				$eTask['cultivation']['plant'] = $eTask['plant'];

				\series\CultivationLib::fillSliceStats($eTask['cultivation']);

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

		\farm\FarmerLib::setView('viewSeries', $data->eFarm, $view);

		if(get_exists('harvestExpected')) {
			\farm\FarmerLib::setView('viewPlanningHarvestExpected', $data->eFarm, GET('harvestExpected', [\farm\Farmer::TOTAL, \farm\Farmer::WEEKLY], \farm\Farmer::TOTAL));
		}

		if(get_exists('field')) {
			\farm\FarmerLib::setView('viewPlanningField', $data->eFarm, GET('field', [\farm\Farmer::VARIETY, \farm\Farmer::SOIL], \farm\Farmer::SOIL));
		}

		if(get_exists('area')) {
			\farm\FarmerLib::setView('viewPlanningArea', $data->eFarm, GET('area', [\farm\Farmer::AREA, \farm\Farmer::LENGTH], \farm\Farmer::LENGTH));
		}

		$data->cSeriesImportPerennial = \series\SeriesLib::getImportPerennial($data->eFarm, $data->season);

		$data->nSeries = \series\SeriesLib::countByFarm($data->eFarm, $data->season);

		if($data->nSeries === 0) {
			$data->firstSeries = (\series\SeriesLib::countByFarm($data->eFarm) === 0);
		} else {
			$data->firstSeries = FALSE;
		}

		$data->cSupplier = new Collection();
		$data->cAction = new Collection();

		\farm\ActionLib::getMainByFarm($data->eFarm);

		switch($data->eFarm->getView('viewSeries')) {

			case \farm\Farmer::AREA :

				$data->search = new Search([
					'tool' => get_exists('tool') ? \farm\ToolLib::getOneByFarm($data->eFarm, GET('tool')) : new \farm\Tool(),
					'bedWidth' => GET('bedWidth', '?int')
				]);

				$data->ccCultivation = \series\CultivationLib::getForArea($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::SEEDLING :

				$data->search = new Search([
					'supplier' => GET('supplier', 'farm\Supplier'),
					'seedling' => \series\Cultivation::GET('seedling', 'seedling')
				]);

				$data->cSupplier = \farm\SupplierLib::getByFarm($data->eFarm);
				$data->items = ($data->search->get('seedling') === \series\Cultivation::YOUNG_PLANT_BOUGHT) ?
					\series\CultivationLib::getForSeedlingByStartWeek($data->eFarm, $data->season, $data->search) :
					\series\CultivationLib::getForSeedling($data->eFarm, $data->season, $data->search);
				break;

			case \farm\Farmer::HARVESTING :
				$data->search = NULL;
				$data->ccCultivation = \series\CultivationLib::getForHarvesting($data->eFarm, $data->season);
				break;

			case \farm\Farmer::WORKING_TIME :

				$data->search = new Search([
					'action' => GET('action', 'farm\Action'),
				]);

				$data->cAction = \farm\ActionLib::getByFarm($data->eFarm, category: \farm\CategoryLib::getByFarm($data->eFarm, fqn: 'culture'));
				$data->ccCultivation = \series\CultivationLib::getWorkingTimeByFarm($data->eFarm, $data->season, $data->search);
				break;

		}

		throw new ViewAction($data, ':series');

	})
	->get(['/ferme/{id}/previsionnel', '/ferme/{id}/previsionnel/{season}'], function($data) {

		$data->eFarm->validate('canAnalyze');

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));

		$data->cSupplier = new Collection();
		$data->cAction = new Collection();

		\farm\ActionLib::getMainByFarm($data->eFarm);

		if(get_exists('help')) {
			\Cache::redis()->delete('help-forecast-'.$data->eFarm['id']);
		}
		$cccCultivation = \series\CultivationLib::getForForecast($data->eFarm, $data->season);
		$data->ccForecast = \plant\ForecastLib::getByFarm($data->eFarm, $data->season, $cccCultivation);

		throw new ViewAction($data, ':forecast');

	})
	->get(['/ferme/{id}/carte', '/ferme/{id}/carte/{season}'], function($data) {

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

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

		\farm\FarmerLib::setView('viewSoil', $data->eFarm, \farm\Farmer::PLAN);

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		\map\GreenhouseLib::putFromZone($data->cZone);

		$seasonsSeries = [$data->season + 1, $data->season, $data->season - 1];

		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, $seasonsSeries);

		throw new ViewAction($data, ':soil');


	})
	->get(['/ferme/{id}/rotation', '/ferme/{id}/rotation/{season}'], function($data) {

		\farm\FarmerLib::setView('viewSoil', $data->eFarm, \farm\Farmer::ROTATION);

		$data->season = \farm\FarmerLib::getDynamicSeason($data->eFarm, GET('season', 'int'));
		\map\SeasonLib::setOnline($data->season);

		$data->selectedSeasons = $data->eFarm->getRotationSeasons($data->season);


		$data->cZone = \map\ZoneLib::getByFarm($data->eFarm, season: $data->season);

		\map\GreenhouseLib::putFromZone($data->cZone);

		\map\PlotLib::putFromZoneWithSeries($data->eFarm, $data->cZone, $data->season, $data->selectedSeasons, onlySeries: TRUE);

		$data->search = new Search([
			'cFamily' => \plant\FamilyLib::getList(),
			'family' => GET('family', 'plant\Family'),
			'seen' => GET('seen', '?int'),
			'bed' => GET('bed', 'bool')
		]);

		\series\PlaceLib::filterRotationsByFamily($data->eFarm, $data->cZone, $data->search);

		throw new ViewAction($data, ':soil');


	})
	->get(['/ferme/{id}/analyses/planning', '/ferme/{id}/analyses/planning/{year}', '/ferme/{id}/analyses/planning/{year}/{category}'], function($data) {

		$data->eFarm->validate('canAnalyze');

		if($data->eFarm->hasFeatureTime() === FALSE) {
			throw new RedirectAction(\farm\FarmUi::urlAnalyzeCultivation($data->eFarm));
		}

		$data->years = \series\AnalyzeLib::getYears($data->eFarm);

		if($data->years) {

			if(get_exists('year')) {
				$selectedYear = GET('year', 'int');
				$data->year = in_array($selectedYear, $data->years) ? $selectedYear : first($data->years);
				\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->year);
			} else {
				$currentYear = $data->eFarm->getView('viewAnalyzeYear');
				$data->year = in_array($currentYear, $data->years) ? $currentYear : first($data->years);
			}

			$data->category = GET('category', 'string', $data->eFarm->getView('viewPlanningCategory'));
			$data->month = GET('month', fn($value) => Filter::check(['?int', 'min' => 1, 'max' => 12], $value));
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

					$data->ccWorkingTimeMonthly = \series\AnalyzeLib::getMonthlyWorkingTime($data->eFarm, $data->year, $data->month, $data->week);
					$data->workingTimeWeekly = \series\AnalyzeLib::getWeeklyWorkingTime($data->eFarm, $data->year, $data->month, $data->week);
					$data->ccTimesheetAction = \series\AnalyzeLib::getActionTimesheetByUser($data->eFarm, $data->year, $data->month, $data->week);
					$data->ccTimesheetCategory = \series\AnalyzeLib::getCategoryTimesheetByUser($data->eFarm, $data->year, $data->month, $data->week);
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
					$data->cWorkingTimeMonthBefore = \series\AnalyzeLib::getFarmMonths($data->eFarm, $data->year - 1);
					
					$data->cWorkingTimeWeek = \series\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year);
					$data->cWorkingTimeWeekBefore = \series\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year - 1);
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
			$currentSeason = $data->eFarm->getView('viewAnalyzeYear');
			$data->season = ($currentSeason >= $data->eFarm['seasonFirst'] and $currentSeason <= $data->eFarm['seasonLast']) ? $currentSeason : $data->eFarm['seasonLast'];
		}

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

		[$data->years, $data->salesByYear] = \selling\AnalyzeLib::getYears($data->eFarm);

		if($data->years) {

			if(get_exists('year')) {
				$selectedYear = GET('year', 'int');
				$data->year = in_array($selectedYear, $data->years) ? $selectedYear : first($data->years);
				\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->year);
			} else {
				$currentYear = $data->eFarm->getView('viewAnalyzeYear');
				$data->year = in_array($currentYear, $data->years) ? $currentYear : first($data->years);
			}
			$data->month = GET('month', fn($value) => Filter::check(['?int', 'min' => 1, 'max' => 12], $value));
			$data->week = GET('week', '?string');

			$data->category = GET('category', 'string', $data->eFarm->getView('viewSellingCategory'));

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

						if(in_array($data->yearCompare, $data->years) and $data->yearCompare !== $data->year) {

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

					if($data->cShop->notEmpty()) {

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

					}

					break;

				case \farm\Farmer::PERIOD :
					$data->cItemMonth = \selling\AnalyzeLib::getFarmMonths($data->eFarm, $data->year, $data->search);
					$data->cItemMonthBefore = \selling\AnalyzeLib::getFarmMonths($data->eFarm, $data->year - 1, $data->search);
					$data->cItemWeek = \selling\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year, $data->search);
					$data->cItemWeekBefore = \selling\AnalyzeLib::getFarmWeeks($data->eFarm, $data->year - 1, $data->search);
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

		$data->seasons = $data->eFarm->getSeasons();

		if(get_exists('season')) {
			$selectedSeason = GET('season', 'int');
			$data->season = in_array($selectedSeason, $data->seasons) ? $selectedSeason : first($data->seasons);
			\farm\FarmerLib::setView('viewAnalyzeYear', $data->eFarm, $data->season);
		} else {
			$currentYear = $data->eFarm->getView('viewAnalyzeYear');
			$data->season = in_array($currentYear, $data->seasons) ? $currentYear : last($data->seasons);
		}

		$data->category = GET('category', 'string', $data->eFarm->getView('viewCultivationCategory'));

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
	->get('/ferme/{id}/configuration/production', function($data) {

		$data->eFarm->validate('canManage');

		throw new ViewAction($data);

	})
	->get('/ferme/{id}/configuration/commercialisation', function($data) {

		$data->eFarm->validate('canManage');

		throw new ViewAction($data);

	});
?>
