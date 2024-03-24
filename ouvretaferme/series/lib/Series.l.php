<?php
namespace series;

class SeriesLib extends SeriesCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'use', 'mode', 'season', 'sequence', 'areaTarget', 'lengthTarget', 'bedWidth', 'alleyWidth'];
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Series $e) {

			$e->expects(['cycle', 'perennialStatus']);
			$properties = ['name', 'use', 'mode', 'sequence', 'areaTarget', 'lengthTarget', 'bedWidth', 'alleyWidth'];

			if($e['cycle'] === Series::ANNUAL) {
				$properties[] = 'season';
			}

			if($e['cycle'] === Series::PERENNIAL and $e['perennialStatus'] !== Series::CONTINUED) {
				$properties[] = 'perennialLifetime';
			}

			return $properties;

		};

	}

	public static function getByFarm(\farm\Farm $eFarm, ?int $season = NULL, bool $selectCultivation = FALSE, bool $selectPlaces = FALSE, \Search $search = new \Search()): \Collection {

		if($season !== NULL) {
			Series::model()->whereSeason($season);
		}

		$selection = Series::getSelection();

		if($selectCultivation) {
			$selection['cCultivation'] = CultivationLib::delegateBySeries(Cultivation::getSelection() + [
				'firstTaskWeek' => CultivationLib::delegateFirstTaskWeek($eFarm)
			]);
		}

		if($selectPlaces) {
			$selection['cccPlace'] = PlaceLib::delegateBySeries();
		}

		if($search->get('plant')) {

			if($season !== NULL) {
				Cultivation::model()->whereSeason($season);
			}

			$cSeriesFilter = Cultivation::model()
				->select('series')
				->whereFarm($eFarm)
				->wherePlant($search->get('plant'))
				->getColumn('series');

			Series::model()->whereId('IN', $cSeriesFilter);

		}

		if($search->get('status')) {
			Series::model()->whereStatus($search->get('status'));
		}

		return Series::model()
			->select($selection)
			->whereFarm($eFarm)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection();

	}

	public static function countByFarm(\farm\Farm $eFarm, ?int $season = NULL): int {

		return Series::model()
			->whereFarm($eFarm)
			->whereSeason($season, if: $season !== NULL)
			->count();

	}

	public static function fillTimeline(Series $e): void {

		$e->expects(['farm']);

		$cAction = \farm\ActionLib::getByFarm($e['farm'], fqn: [ACTION_SEMIS_DIRECT, ACTION_PLANTATION]);

		\series\Series::model()
			->select([
				'cCultivation' => \series\Cultivation::model()
					->select([
						'id',
						'season',
						'harvested',
						'plant' => ['family', 'fqn', 'vignette', 'name'],
						'harvestMonths', 'harvestMonthsExpected',
						'harvestWeeks', 'harvestWeeksExpected',
						'startWeek', 'startAction',
						'firstTaskWeek' => \series\CultivationLib::delegateFirstTaskWeek($e['farm']),
						'cTask' => \series\Task::model()
							->select([
								'cultivation',
								'action',
								'plannedWeek', 'doneWeek',
								'status'
							])
							->whereAction('IN', $cAction)
							->whereSeries($e)
							->delegateCollection('cultivation')
					])
					->sort(['startWeek' => SORT_ASC])
					->delegateCollection('series')
			])
			->get($e);

	}

	public static function getBySequence(\production\Sequence $eSequence): \Collection {

		return Series::model()
			->select(Series::getSelection())
			->select([
				'ccCultivation' => Cultivation::model()
					->select(Cultivation::getSelection())
					->whereCrop('!=', NULL)
					->sort(['crop' => SORT_ASC])
					->delegateCollection('series', index: ['plant', NULL]),
			])
			->whereSequence($eSequence)
			->sort([
				'season' => SORT_DESC,
				'name' => SORT_ASC
			])
			->getCollection(NULL, NULL, ['season', NULL]);

	}

	public static function getByPerennialFirst(Series $eSeries): \Collection {

		return Series::model()
			->select([
				'id',
				'perennialSeason', 'perennialStatus'
			])
			->wherePerennialFirst($eSeries)
			->sort([
				'perennialSeason' => SORT_ASC
			])
			->getCollection(NULL, NULL, 'perennialSeason');

	}

	public static function getImportPerennial(\farm\Farm $eFarm, int $season): \Collection {

		return Series::model()
			->select(Series::getSelection())
			->select([
				'sequence' => ['name', 'mode', 'status'],
				'cCultivation' => (clone Cultivation::model())
					->select([
						'id',
						'plant' => ['name', 'fqn', 'vignette']
					])
					->delegateCollection('series'),
			])
			->whereFarm($eFarm)
			->whereSeason($season - 1)
			->whereCycle(Series::PERENNIAL)
			->wherePerennialStatus(Series::GROWING)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection();

	}

	public static function getSeasonsAround(\farm\Farm $eFarm, int $year): array {

		return Series::model()
			->whereFarm($eFarm)
			->where('season BETWEEN '.($year - 1).' AND '.($year + 1).'')
			->sort(new \Sql('_ DESC'))
			->getColumn(new \Sql('DISTINCT season', 'int'));

	}

	public static function getCreateElement(): Series {
		return new Series([
			'id' => NULL,
			'cycle' => Series::ANNUAL
		]);
	}

	public static function buildDefaultName(\Collection $cCultivation): string {

		return implode(' + ', $cCultivation->getColumnCollection('plant')
			->sort('name')
			->getColumn('name'));

	}

	public static function prepareCreate(array $input): array {

		$eFarm = \farm\FarmLib::getById($input['farm'] ?? NULL)->validate('canManage');

		$eSeries = SeriesLib::getCreateElement();
		$eSeries['farm'] = $eFarm;

		$fw = new \FailWatch();

		$eSeries->build(self::getPropertiesCreate(), $input, for: 'create');

		if($fw->ko()) {
			return [];
		}

		$cCultivation = new \Collection();

		$eCultivationReference = new Cultivation([
			'farm' => $eFarm,
			'series' => $eSeries,
			'season' => $eSeries['season'],
			'sequence' => $eSeries['sequence']
		]);

		$plants = var_filter($input['plant'] ?? [], 'array');
		$crops = var_filter($input['crop'] ?? [], 'array');

		if($plants === []) {
			Series::fail('plantsCheck');
			return [];
		}

		if($eSeries['sequence']->empty()) {
			$eSeries['cycle'] = NULL;
		} else {
			$eSeries['cycle'] = $eSeries['sequence']['cycle'];
		}

		foreach($plants as $index => $plant) {

			$eCultivation = clone $eCultivationReference;
			$eCultivation['index'] = $index;

			if($eSeries['sequence']->notEmpty()) {

				$crop = $crops[$index] ?? NULL;

				if($crop === NULL) {
					Series::fail('cropsCheck');
					return [];
				}

				$cCultivation->offsetSet($crop, $eCultivation);

			} else {
				$cCultivation->append($eCultivation);
			}

		}

		// Construction de chaque culture
		foreach($cCultivation as $eCultivation) {

			$index = $eCultivation['index'];

			$fw = new \FailWatch();

			// Recherche du 'crop' attaché au 'cultivation'
			if($eSeries['sequence']->notEmpty()) {
				$eCultivation->buildIndex(['crop'], $input, $index);
			} else {
				$eCultivation['crop'] = new \production\Crop();
			}

			$eCultivation->buildIndex(['plant', 'sliceUnit'], $input, $index);

			if($fw->ok()) {

				$eCultivation->buildIndex(['variety'], $input, $index);

			}

			$properties = ['distance', 'density', 'rows', 'plantSpacing', 'rowSpacing', 'seedling', 'seedlingSeeds', 'yieldExpected', 'mainUnit', 'actions'];

			if($eSeries['sequence']->empty()) {
				$properties = array_merge($properties, ['harvestPeriodExpected', 'harvestMonthsExpected', 'harvestWeeksExpected']);
			}

			$eCultivation->buildIndex($properties, $input, $index);

			if($fw->ok()) {

				if($eSeries['cycle'] === NULL) { // La première culture définit le cycle de la série
					$eSeries['cycle'] = $eCultivation['plant']['cycle'];
				}

			}

		}

		$eSeries['plants'] = $cCultivation->count();

		// Le cycle est connu, on vérifie la durée de vie de la culture si c'est une pérenne
		$eSeries->build(['perennialLifetime'], $input);

		if($fw->ko()) {
			return [];
		}

		return [$eSeries, $cCultivation];

	}

	public static function createWithCultivations(Series $e, \Collection $cCultivation, \Collection $cFlow, ?int $referenceYear): void {

		$cCultivation->expects([
			'plant' => ['name']
		]);

		Series::model()->beginTransaction();

		// Créer une série
		$e->add([
			'name' => self::buildDefaultName($cCultivation),
			'cultivations' => $cCultivation->count()
		]);

		self::create($e);

		$fw = new \FailWatch();

		// Créer les nouvelles variétés
		foreach($cCultivation as $eCultivation) {

			\production\SliceLib::createVariety($eCultivation['cSlice']);

			$eCultivation['series'] = $e;

			\production\CropLib::calculateDistance($eCultivation, $e);

		}

		// Ajout des estimations de récolte
		if($cFlow->notEmpty()) {
			CultivationLib::buildHarvestsFromSequence($cCultivation, $cFlow, $referenceYear);
		}

		// Ajout des cultures sans parent une par une pour récupérer les IDs
		self::createCultivations($cCultivation);

		// Create tasks and add into the database
		if($cFlow->notEmpty()) {

			[$cTask, $cRepeat] = \series\TaskLib::buildFromFlow($cFlow, $e, $cCultivation, $e['season'], $referenceYear);

			self::createTasks($cTask);
			self::createRepeats($cRepeat);

		}

		self::recalculate($e['farm'], $e);

		if($fw->ok()) {
			Series::model()->commit();
		} else {
			Series::model()->rollBack();
		}

	}

	private static function createTasks(\Collection $cTask): void {

		foreach($cTask as $eTask) {

			Task::model()->insert($eTask);

			foreach($eTask['cRequirement'] as $eRequirement) {
				$eRequirement['farm'] = $eTask['farm'];
				$eRequirement['series'] = $eTask['series'];
				$eRequirement['cultivation'] = $eTask['cultivation'];
				$eRequirement['task'] = $eTask;
			}

			Requirement::model()->insert($eTask['cRequirement']);

		}

	}

	private static function createRepeats(\Collection $cRepeat): void {

		foreach($cRepeat as $eRepeat) {

			Repeat::model()->insert($eRepeat);

			RepeatLib::createForSeries($eRepeat);

		}

	}

	public static function createCultivations(\Collection $cCultivation): void {

		$cCultivation->expects(['cSlice', 'actions']);

		// Ajout des cultures sans parent une par une pour récupérer les IDs

		foreach($cCultivation as $eCultivation) {

			Cultivation::model()->insert($eCultivation); // Pour mettre l'ID

			CultivationLib::createTasks($eCultivation);

			$eCultivation['cSlice']->setColumn('id', NULL);
			$eCultivation['cSlice']->setColumn('cultivation', $eCultivation);
			$eCultivation['cSlice']->setColumn('series', $eCultivation['series']);

			// Ajout de la répartition des variétés
			SliceLib::createCollection($eCultivation['cSlice']);

		}

	}

	public static function create(Series $e): void {

		$e->expects(['name', 'cycle']);

		if($e['cycle'] === Series::PERENNIAL) {

			$e->expects(['perennialLifetime']);

			$e['perennialSeason'] = 1;
			$e['perennialStatus'] = Series::GROWING;

		}

		if($e['use'] === Series::BED and $e['lengthTarget'] !== NULL) {
			$e['areaTarget'] = round($e['lengthTarget'] * $e['bedWidth'] / 100);
		}

		parent::create($e);

		if($e['cycle'] === Series::PERENNIAL) {

			Series::model()->update($e, [
				'perennialFirst' => $e
			]);

		}
	}

	/**
	 * Dupliquer une série
	 */
	public static function duplicate(Series $eSeries, bool $copyTasks, \Collection $cAction, bool $copyTimesheet, bool $copyPlaces): Series {

		$properties = ['name', 'farm', 'season', 'mode', 'use', 'plants', 'area', 'areaTarget', 'length', 'lengthTarget', 'bedWidth', 'alleyWidth', 'sequence', 'cycle'];

		$eSeries->expects($properties);

		if($eSeries['cycle'] !== \series\Series::ANNUAL) {
			throw new \Exception('Can duplicate only annual series');
		}

		Series::model()->beginTransaction();

		// Créer une nouvelle série
		$eSeriesNew = new Series($eSeries->extracts($properties));
		$eSeriesNew['name'] = (new SeriesUi())->getDuplicateName($eSeriesNew);

		Series::model()->insert($eSeriesNew);

		// Dupliquer les cultures et les variétés
		$cCultivation = self::getDuplicateCultivations($eSeries);

		$seasonDifference = ($eSeriesNew['season'] - $eSeries['oldSeason']);

		foreach($cCultivation as $eCultivation) {

			// Mise à jour de la série
			$eCultivation['series'] = $eSeriesNew;
			$eCultivation['season'] = $eSeries['season'];

			if($seasonDifference !== 0) {

				if($eCultivation['harvestWeeksExpected']) {
					foreach($eCultivation['harvestWeeksExpected'] as $key => $value) {
						$eCultivation['harvestWeeksExpected'][$key] = (substr($value, 0, 4) + $seasonDifference).substr($value, 4);
					}
				}

				if($eCultivation['harvestMonthsExpected']) {
					foreach($eCultivation['harvestMonthsExpected'] as $key => $value) {
						$eCultivation['harvestMonthsExpected'][$key] = (substr($value, 0, 4) + $seasonDifference).substr($value, 4);
					}

				}

			}

		}
		
		self::createCultivations($cCultivation);

		// Dupliquer les tâches
		if($copyTasks) {
			self::duplicateTasks($eSeries, $eSeriesNew, $cCultivation, $cAction, $copyTimesheet);
		}

		// Dupliquer les emplacements
		if($copyPlaces) {
			self::duplicatePlaces($eSeries, $eSeriesNew);
		} else {

			$eSeriesNew['area'] = NULL;
			$eSeriesNew['areaPermanent'] = NULL;
			$eSeriesNew['length'] = NULL;
			$eSeriesNew['lengthPermanent'] = NULL;

			Series::model()
				->select('area', 'areaPermanent', 'length', 'lengthPermanent')
				->update($eSeriesNew);

		}

		foreach($cCultivation as $eCultivation) {
			TaskLib::recalculateHarvest($eSeriesNew['farm'], $eCultivation, $eCultivation['plant']);
		}

		Series::model()->commit();

		return $eSeriesNew;

	}

	private static function duplicatePerennialWithCultivations(Series $eSeries): void {

		$eSeries->expects(['id']);

		// Créer une série
		$eSeriesNew = self::getDuplicatePerennialSeries($eSeries);

		Series::model()->insert($eSeriesNew);

		// Créer les nouvelles cultures
		$cCultivation = self::getDuplicateCultivations($eSeries);

		// Obtenir les Crop équivalents
		$cCrop = new \Collection();

		foreach($cCultivation as $eCultivation) {

			if($eCultivation['crop']->empty() === FALSE) {
				$cCrop[] = $eCultivation['crop'];
			}

		}

		// Obtenir le flow
		if($cCrop->empty()) {
			$cFlow = new \Collection();
		} else {
			$cFlow = \production\FlowLib::getByCrops($cCrop, $eSeriesNew['perennialSeason']);
		}

		// Ajustements les cultures
		self::buildDuplicateCultivations($eSeriesNew, $cCultivation, $cFlow);

		self::createCultivations($cCultivation);

		// Créer les nouvelles tâches
		[$cTask, $cRepeat] = \series\TaskLib::buildFromFlow($cFlow, $eSeriesNew, $cCultivation, $eSeriesNew['season']);

		self::createTasks($cTask);
		self::createRepeats($cRepeat);

		// Réaffecter les emplacements
		self::duplicatePlaces($eSeries, $eSeriesNew);


	}

	private static function getDuplicatePerennialSeries(Series $e): Series {

		$properties = ['name', 'farm', 'season', 'use', 'plants', 'area', 'length', 'bedWidth', 'alleyWidth', 'sequence', 'cycle', 'perennialLifetime', 'perennialFirst', 'perennialSeason'];

		$e->expects($properties);

		$eSeries = new Series($e->extracts($properties));
		$eSeries['season']++;
		$eSeries['perennialSeason']++;
		$eSeries['perennialStatus'] = ($eSeries['perennialSeason'] ===  $eSeries['perennialLifetime'] ? Series::FINISHED : Series::GROWING);

		return $eSeries;

	}

	private static function getDuplicateCultivations(Series $eSeries): \Collection {

		$cCultivation = Cultivation::model()
			->select([
				'id',
				'farm', 'season', 'sequence',
				'crop' => ['yieldExpected'],
				'cSlice' => SliceLib::delegateByCultivation(),
				'plant',
				'startWeek', 'startAction',
				'distance', 'rows', 'rowSpacing', 'plantSpacing', 'density', 'sliceUnit',
				'area', 'areaPermanent', 'length', 'lengthPermanent', 'seedling', 'seedlingSeeds',
				'mainUnit', 'yieldExpected', 'unitWeight', 'bunchWeight',
				'harvestPeriodExpected', 'harvestMonthsExpected', 'harvestWeeksExpected'
			])
			->whereSeries($eSeries)
			->getCollection(NULL, NULL, 'id');

		$cCultivation->setColumn('id', NULL);
		$cCultivation->setColumn('actions', []);

		return $cCultivation;

	}

	private static function buildDuplicateCultivations(Series $eSeriesNew, \Collection $cCultivation, \Collection $cFlow) {

		$harvests = \production\CropLib::getHarvestsFromFlow($cFlow, $eSeriesNew['season']);

		foreach($cCultivation as $eCultivation) {

			$eCultivation['series'] = $eSeriesNew;
			$eCultivation['season'] = $eSeriesNew['season'];

			if($eCultivation['crop']->empty() === FALSE) {

				$harvestWeeksExpected = $harvests[$eCultivation['plant']['id']] ?? [];

				if($harvestWeeksExpected) {
					$eCultivation['yieldExpected'] = $eCultivation['crop']['yieldExpected'];
					$eCultivation['harvestPeriodExpected'] = Cultivation::WEEK;
					$eCultivation['harvestMonthsExpected'] = \util\DateLib::convertWeeksToMonths($harvestWeeksExpected);
					$eCultivation['harvestWeeksExpected'] = $harvestWeeksExpected;
				} else {
					$eCultivation['yieldExpected'] = NULL;
					$eCultivation['harvestPeriodExpected'] = Cultivation::MONTH;
					$eCultivation['harvestMonthsExpected'] = NULL;
					$eCultivation['harvestWeeksExpected'] = NULL;
				}

			}

		}

	}

	private static function duplicateTasks(Series $eSeries, Series $eSeriesNew, \Collection $cCultivation, \Collection $cAction, bool $copyTimesheet): void {

		$cTask = Task::model()
			->select(Task::model()->getProperties() + [
				'cRequirement' => Requirement::model()
					->select([
						'farm', 'tool'
					])
					->delegateCollection('task'),
				'cHarvest' => Harvest::model()
					->select([
						'farm', 'quantity', 'unit', 'date', 'week'
					])
					->delegateCollection('task')
			])
			->whereSeries($eSeries)
			->whereAction('IN', $cAction)
			->getCollection(NULL, NULL, 'id');

		if($cTask->empty()) {
			return;
		}

		if($copyTimesheet) {

			// Mise en réserve des temps de travail avec les anciennes tâches
			$cTimesheet = Timesheet::model()
				->select(array_diff(Timesheet::model()->getProperties(), ['id']))
				->whereTask('IN', $cTask)
				->getCollection();

		}

		$seasonDifference = ($eSeriesNew['season'] - $eSeries['oldSeason']);

		// Copie des tâches
		foreach($cTask as $eTask) {

			$eTask['id'] = NULL;
			$eTask['series'] = $eSeriesNew;
			$eTask['season'] = $eSeriesNew['season'];

			if($eTask['cultivation']->notEmpty()) {
				$eTask['cultivation'] = $cCultivation[$eTask['cultivation']['id']];
			}

			if($copyTimesheet === FALSE) {
				$eTask['time'] = NULL;
				$eTask['timesheetStart'] = NULL;
				$eTask['timesheetStop'] = NULL;
			}

			if($seasonDifference !== 0) {

				if($eTask['plannedWeek'] !== NULL) {
					$eTask['plannedWeek'] = (substr($eTask['plannedWeek'], 0, 4) + $seasonDifference).substr($eTask['plannedWeek'], 4);
				}

				if($eTask['plannedDate'] !== NULL) {
					$eTask['plannedDate'] = (substr($eTask['plannedDate'], 0, 4) + $seasonDifference).substr($eTask['plannedDate'], 4);
				}

				$eTask['doneWeek'] = NULL;
				$eTask['doneDate'] = NULL;
				$eTask['status'] = Task::TODO;

				if($eTask['harvest'] !== NULL) {
					$eTask['harvest'] = NULL;
					$eTask['harvestUnit'] = NULL;
					$eTask['harvestQuality'] = NULL;
				}

			}

			Task::model()->insert($eTask);

			if($seasonDifference === 0) {

				foreach($eTask['cHarvest'] as $eHarvest) {

					$eHarvest['id'] = NULL;
					$eHarvest['series'] = $eTask['series'];
					$eHarvest['cultivation'] = $eTask['cultivation'];
					$eHarvest['task'] = $eTask;

				}

				Harvest::model()->insert($eTask['cHarvest']);

			}

			foreach($eTask['cRequirement'] as $eRequirement) {

				$eRequirement['id'] = NULL;
				$eRequirement['series'] = $eTask['series'];
				$eRequirement['cultivation'] = $eTask['cultivation'];
				$eRequirement['task'] = $eTask;

			}

			Requirement::model()->insert($eTask['cRequirement']);

		}

		if($copyTimesheet) {

			// Copie des temps de travail
			$cTimesheet->map(function(Timesheet $eTimesheet) use ($cTask) {

				$eTask = $cTask[$eTimesheet['task']['id']];

				$eTimesheet['task'] = $eTask;
				$eTimesheet['series'] = $eTask['series'];
				$eTimesheet['cultivation'] = $eTask['cultivation'];
				$eTimesheet['plant'] = $eTask['plant'];

			});

			Timesheet::model()->insert($cTimesheet);

		}

	}

	private static function duplicatePlaces(Series $eSeries, Series $eSeriesNew): void {

		$cPlace =  Place::model()
			->select(['farm', 'season', 'zone', 'plot', 'bed', 'length', 'width', 'area'])
			->whereSeries($eSeries)
			->getCollection();

		if($cPlace->empty()) {
			return;
		}

		foreach($cPlace as $ePlace) {
			$ePlace['series'] = $eSeriesNew;
			$ePlace['season'] = $eSeriesNew['season'];
		}

		if(
			\map\Bed::model()
				->whereId('IN', $cPlace->getColumn('bed'))
				->where('seasonFirst > '.$eSeriesNew['season'].' OR seasonLast < '.$eSeriesNew['season'])
				->count() > 0 or
			\map\Plot::model()
				->whereId('IN', $cPlace->getColumn('plot'))
				->where('seasonFirst > '.$eSeriesNew['season'].' OR seasonLast < '.$eSeriesNew['season'])
				->count() > 0 or
			\map\Zone::model()
				->whereId('IN', $cPlace->getColumn('zone'))
				->where('seasonFirst > '.$eSeriesNew['season'].' OR seasonLast < '.$eSeriesNew['season'])
				->count() > 0
		) {
			Series::fail('duplicatePlaceConsistency');
		}

		if($cPlace->notEmpty()) {
			Place::model()->insert($cPlace);
		}

	}

	public static function updatePerennialContinued(Series $e): void {

		Series::model()->beginTransaction();

		$e['perennialStatus'] = Series::CONTINUED;

		$affected = Series::model()
			->select('perennialStatus')
			->wherePerennialStatus(Series::GROWING)
			->update($e);

		$fw = new \FailWatch();

		if($affected > 0) {
			self::duplicatePerennialWithCultivations($e);
		}

		if($fw->ok()) {
			Series::model()->commit();
		} else {
			Series::model()->rollBack();
		}

	}

	public static function updatePerennialFinished(Series $e): void {

		$e->expects(['perennialSeason']);

		$e['perennialStatus'] = Series::FINISHED;
		$e['perennialLifetime'] = $e['perennialSeason'];

		Series::model()
			->select('perennialStatus', 'perennialLifetime')
			->wherePerennialStatus(Series::GROWING)
			->update($e);

	}

	public static function update(Series $e, array $properties = []): void {

		Series::model()->beginTransaction();

		$updateCultivation = [];
		$updatePlace = [];
		$updateTask = [];

		if(in_array('perennialLifetime', $properties)) {

			$e->expects(['cycle', 'perennialSeason']);

			$properties[] = 'perennialStatus';

			if($e['perennialLifetime'] === NULL or $e['perennialLifetime'] > $e['perennialSeason']) {
				$e['perennialStatus'] = Series::GROWING;
			} else {
				$e['perennialStatus'] = Series::FINISHED;
			}

		}

		$updateUse = FALSE;

		if(in_array('use', $properties)) {

			$e->expects(['use']);

			if($e['use'] !== $e['oldUse']) {

				$updateUse = TRUE;

				switch($e['use']) {

					case Series::BED :

						$e['areaTarget'] = NULL;
						$properties[] = 'areaTarget';

						$updateCultivation['rowSpacing'] = NULL;
						break;

					case Series::BLOCK :

						$e['lengthTarget'] = NULL;
						$properties[] = 'lengthTarget';

						$e['bedWidth'] = NULL;
						$properties[] = 'bedWidth';

						$e['alleyWidth'] = NULL;
						$properties[] = 'alleyWidth';

						$updateCultivation['rows'] = NULL;
						break;

				}

			}

		}

		if(
			$e['use'] === Series::BED and
			(in_array('lengthTarget', $properties) or in_array('bedWidth', $properties))
		) {
			if($e['lengthTarget'] !== NULL) {
				$e['areaTarget'] = round($e['lengthTarget'] * ($e['bedWidth'] + $e['alleyWidth'] ?? 0) / 100);
			} else {
				$e['areaTarget'] = NULL;
			}
			$properties[] = 'areaTarget';
		}

		parent::update($e, $properties);

		if(in_array('season', $properties)) {

			$updateCultivation['season'] = $e['season'];
			$updateTask['season'] = $e['season'];
			$updatePlace['season'] = $e['season'];

		}


		if($updateCultivation) {

			Cultivation::model()
				->whereSeries($e)
				->update($updateCultivation);

		}

		if($updateTask) {

			Task::model()
				->whereSeries($e)
				->update($updateTask);

		}

		if($updatePlace) {

			Place::model()
				->whereSeries($e)
				->update($updatePlace);

		}

		if(
			(array_intersect(['bedWidth', 'alleyWidth'], $properties) and $e['use'] === Series::BED) or
			$updateUse
		) {
			CultivationLib::updateDensityBySeries($e);
		}

		if($updateUse) {
			PlaceLib::deleteBySeries($e);
			PlaceLib::recalculateMetadata($e, new \Collection());
			PlaceLib::updateMetadata($e);
		} else {

			if(
				in_array('alleyWidth', $properties) and
				$e['use'] === Series::BED
			) {
				PlaceLib::recalculateAreaBySeries($e);
			}

		}

		Series::model()->commit();

	}

	public static function delete(Series $e): void {

		$e->expects(['id']);

		Series::model()->beginTransaction();

		// Supprime les cultures
		Cultivation::model()
			->whereSeries($e)
			->delete();

		Slice::model()
			->whereSeries($e)
			->delete();

		Requirement::model()
			->whereSeries($e)
			->delete();

		// Supprime les tâches
		$cTask = Task::model()
			->select('id')
			->whereSeries($e)
			->getCollection();

		TaskLib::deleteCollection($cTask, recalculate: FALSE);

		// Supprime les emplacements
		Place::model()
			->whereSeries($e)
			->delete();

		// Supprime les rapports
		\analyze\Cultivation::model()
			->whereSeries($e)
			->delete();

		Series::model()->delete($e);

		Series::model()->commit();

	}

	public static function recalculate(\farm\Farm $eFarm, Series $e, \farm\Action $eAction = new \farm\Action()): void {

		if($e->empty()) {
			return;
		}

		$e->expects([
			'season'
		]);

		if($eAction->notEmpty()) {

			$eAction->expects(['fqn']);

			if(in_array($eAction['fqn'], [ACTION_SEMIS_DIRECT, ACTION_PLANTATION, ACTION_RECOLTE]) === FALSE) {
				return;
			}

		}

		// On récupére les données de la série à jour
		Series::model()
			->select(['bedStartCalculated', 'bedStartUser', 'bedStopCalculated', 'bedStopUser'])
			->get($e);

		// Recalculer les données internes à chaque culture
		$min = new \Sql('MIN(IF(doneWeek IS NOT NULL, CAST(SUBSTRING(doneWeek, 7, 2) AS SIGNED) + (CAST(SUBSTRING(doneWeek, 1, 4) AS SIGNED) - '.$e['season'].') * 100, CAST(SUBSTRING(plannedWeek, 7, 2) AS SIGNED) + (CAST(SUBSTRING(plannedWeek, 1, 4) AS SIGNED) - '.$e['season'].') * 100))', 'int');
		$max = new \Sql('MAX(IF(doneWeek IS NOT NULL, CAST(SUBSTRING(doneWeek, 7, 2) AS SIGNED) + (CAST(SUBSTRING(doneWeek, 1, 4) AS SIGNED) - '.$e['season'].') * 100, CAST(SUBSTRING(plannedWeek, 7, 2) AS SIGNED) + (CAST(SUBSTRING(plannedWeek, 1, 4) AS SIGNED) - '.$e['season'].') * 100))', 'int');

		$cCrop = \production\SequenceLib::doRecalculate(
			Cultivation::model(),
			Task::model(),
			'series',
			'cultivation',
			$min,
			$max,
			$eFarm,
			$e,
			['id', 'plant', 'harvestWeeks', 'harvestWeeksExpected']
		);

		// Recalculer les données internes à toute la série
		$e['bedStartCalculated'] = NULL;
		$e['bedStopCalculated'] = NULL;

		if($e['bedStartCalculated'] === NULL) {

			$values = array_filter($cCrop->getColumn('startWeek'));
			$e['bedStartCalculated'] = $values ? min($values) : NULL;

		}

		if($e['bedStopCalculated'] === NULL) {

			// On cherche les récoltes attendues
			$stopHarvest = NULL;

			foreach($cCrop as $eCrop) {

				[, $week] = $eCrop->getHarvestBounds();

				if($week === NULL) {
					continue;
				}

				$currentStopHarvest = week_number($week) + (week_year($week) - $e['season']) * 100;
				$stopHarvest = ($stopHarvest === NULL) ? $currentStopHarvest : max($stopHarvest, $currentStopHarvest);

			}

			$e['bedStopCalculated'] = $stopHarvest;

		}

		Series::model()
			->select(['bedStartCalculated', 'bedStopCalculated'])
			->update($e);

	}

}
?>
