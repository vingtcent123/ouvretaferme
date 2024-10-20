<?php
namespace series;

class CsvLib {

	public static function getExportTasks(\farm\Farm $eFarm, int $year): array {

		$ccTimesheet = Timesheet::model()
			->select([
				'user' => ['firstName', 'lastName', 'visibility'],
				'time',
				'date'
			])
			->join(Task::model()
				->select([
					'task' => new \Sql('m2.id'),
					'action' => ['name'],
					'category' => ['name'],
					'series' => ['name', 'mode'],
					'plant' => ['name'],
					'variety' => ['name'],
					'description',
					'harvestUser' => new \Sql('IF(harvest IS NOT NULL, IF(m2.time > 0, FLOOR(harvest * m1.time / m2.time * 100) / 100, 0), NULL)', 'float'),
					'harvest',
					'harvestUnit',
					'harvestSize' => ['name']
				]), 'm2.id = m1.task')
			->where('m1.farm', $eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->getCollection(index: ['task', NULL]);

		$output = [];

		foreach($ccTimesheet as $cTimesheet) {

			$eTimesheetFirst = $cTimesheet->first();

			if($eTimesheetFirst['harvest'] !== NULL) {

				$harvestUser = $cTimesheet->sum('harvestUser');
				$harvestTotal = $eTimesheetFirst['harvest'];

				if($harvestUser < $harvestTotal) {
					$eTimesheetFirst['harvestUser'] += round($harvestTotal - $harvestUser, 2);
				}

			}

			foreach($cTimesheet as $eTimesheet) {

				$output[] = [
					\util\DateUi::numeric($eTimesheet['date']),
					($eTimesheet['user']['firstName'] === NULL) ? $eTimesheet['user']['lastName'] : $eTimesheet['user']['firstName'].' '.$eTimesheet['user']['lastName'],
					$eTimesheet['category']['name'],
					$eTimesheet['action']['name'],
					\util\TextUi::csvNumber($eTimesheet['time']),
					$eTimesheet['series']->empty() ? '' : $eTimesheet['series']['id'],
					$eTimesheet['series']->empty() ? '' : $eTimesheet['series']['name'],
					$eTimesheet['plant']->empty() ? '' : $eTimesheet['plant']['name'],
					$eTimesheet['variety']->empty() ? '' : $eTimesheet['variety']['name'],
					$eTimesheet['harvestUser'] ? \util\TextUi::csvNumber($eTimesheet['harvestUser']) : '',
					($eTimesheet['harvestUser'] and $eTimesheet['harvestUnit']) ? \main\UnitUi::getSingular($eTimesheet['harvestUnit']) : '',
					$eTimesheet['harvestSize']->empty() ? '' : $eTimesheet['harvestSize']['name'],
				];

			}

		}

		return $output;

	}

	public static function getExportHarvests(\farm\Farm $eFarm, int $year): array {

		$cHarvest = Harvest::model()
			->select([
				'date',
				'task' => [
					'series' => ['name'],
					'plant' => ['name'],
					'variety' => ['name'],
					'harvestSize' => ['name'],
				],
				'quantity' => new \Sql('SUM(quantity)', 'float'),
				'unit'
			])
			->whereFarm($eFarm)
			->where('EXTRACT(YEAR FROM date) = '.$year)
			->where('quantity IS NOT NULL')
			->group(['date', 'unit', 'task'])
			->sort('date')
			->getCollection();

		$output = [];

		foreach($cHarvest as $eHarvest) {

			$eTask = $eHarvest['task'];

			if(round($eHarvest['quantity'], 2) === 0.0) {
				continue;
			}

			$output[] = [
				\util\DateUi::numeric($eHarvest['date']),
				$eTask['series']->empty() ? '' : $eTask['series']['id'],
				$eTask['series']->empty() ? '' : $eTask['series']['name'],
				$eTask['plant']->empty() ? '' : $eTask['plant']['name'],
				$eTask['variety']->empty() ? '' : $eTask['variety']['name'],
				\util\TextUi::csvNumber($eHarvest['quantity']),
				\main\UnitUi::getSingular($eHarvest['unit']),
				$eTask['harvestSize']->empty() ? '' : $eTask['harvestSize']['name']
			];

		}

		return $output;

	}

	public static function getExportCultivations(\farm\Farm $eFarm, int $year, &$maxVarieties): array {

		$cAction = \farm\Action::model()
			->select('id', 'fqn')
			->whereFarm($eFarm)
			->whereFqn('IN', [ACTION_SEMIS_PEPINIERE, ACTION_SEMIS_DIRECT, ACTION_PLANTATION])
			->getCollection(index: 'fqn');

		$cTool = \farm\ToolLib::getByFarm($eFarm, routineName: 'tray');

		$eActionSemisDirect = $cAction[ACTION_SEMIS_DIRECT];
		$eActionSemisPepiniere = $cAction[ACTION_SEMIS_PEPINIERE];
		$eActionPlantation = $cAction[ACTION_PLANTATION];

		$cCultivation = Cultivation::model()
			->select(Cultivation::getSelection() + [
				'cTask' => Task::model()
					->select([
						'action',
						'min' => new \Sql('MIN(IF(doneWeek IS NOT NULL, doneWeek, plannedWeek))'),
						'max' => new \Sql('MAX(IF(doneWeek IS NOT NULL, doneWeek, plannedWeek))'),
						'toolName' => Requirement::model()
							->select([
								'tool',
								'toolName' => fn($e) => $cTool[$e['tool']['id']]['name']
							])
							->whereTool('IN', $cTool)
							->delegateProperty('cultivation', 'toolName', propertyParent: 'cultivation')
					])
					->whereAction('IN', $cAction)
					->group(['cultivation', 'action'])
					->delegateCollection('cultivation', index: 'action')
			])
			->whereFarm($eFarm)
			->whereSeason($year)
			->sort([
				'series' => SORT_ASC,
				'id' => SORT_ASC
			])
			->getCollection();

		$maxVarieties = 0;
		$output = [];

		foreach($cCultivation as $eCultivation) {

			$maxVarieties = max($maxVarieties, $eCultivation['cSlice']->count());
			$eSeries = $eCultivation['series'];

			// Pas de cultures pérennes
			if($eSeries['cycle'] === Series::PERENNIAL) {
				continue;
			}

			$sowingWeek = match($eCultivation['seedling']) {
				Cultivation::SOWING => $eCultivation['cTask'][$eActionSemisDirect['id']]['min'] ?? NULL,
				Cultivation::YOUNG_PLANT => $eCultivation['cTask'][$eActionSemisPepiniere['id']]['min'] ?? NULL,
				default => NULL
			};
			$sowingDate = $sowingWeek ? week_date_starts($sowingWeek) : NULL;

			$plantingWeek = match($eCultivation['seedling']) {
				Cultivation::YOUNG_PLANT, Cultivation::YOUNG_PLANT_BOUGHT => $eCultivation['cTask'][$eActionPlantation['id']]['min'] ?? NULL,
				default => NULL,
			};
			$plantingDate = $plantingWeek ? week_date_starts($plantingWeek) : NULL;

			if($eCultivation['seedling'] === Cultivation::YOUNG_PLANT) {
				$youngPlantTray = $eCultivation['cTask'][$eActionSemisPepiniere['id']]['toolName'] ?? NULL;
			} else {
				$youngPlantTray = NULL;
			}

			$harvestDates = $eCultivation['harvestWeeks'] ?? $eCultivation['harvestWeeksExpected'] ?? [];

			$minHarvestDate = $harvestDates ? week_date_starts(min($harvestDates)) : NULL;
			$maxHarvestDate = $harvestDates ? week_date_ends(max($harvestDates)) : NULL;

			$line = [
				$eSeries['season'],
				$eSeries['id'],
				$eSeries['name'],
				match($eSeries['mode']) {
					Series::OUTDOOR => 'open-field',
					default => $eSeries['mode']
				},
				$eCultivation['plant']['name'],
				($eCultivation['seedling'] !== NULL) ? str_replace('-', '_', $eCultivation['seedling']) : NULL,
				($eCultivation['seedling'] === Cultivation::YOUNG_PLANT) ? $eCultivation['seedlingSeeds'] : NULL,
				$youngPlantTray,
				$sowingDate,
				$plantingDate,
				$minHarvestDate,
				$maxHarvestDate,
				$eSeries['use'],
				($eSeries['use'] === Series::BLOCK) ? $eSeries['area'] ?? $eSeries['areaTarget'] ?? NULL : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::DENSITY) ? $eCultivation['density'] : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['rowSpacing'] : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['plantSpacing'] : NULL,
				($eSeries['use'] === Series::BED) ? $eSeries['length'] ?? $eSeries['lengthTarget'] ?? NULL : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::DENSITY) ? $eCultivation['density'] : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['rows'] : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['plantSpacing'] : NULL,
				$eSeries['status'] === Series::CLOSED ? 'true' : 'false',
				$eCultivation['mainUnit'],
				\util\TextUi::csvNumber($eCultivation['yieldExpected']),
				\util\TextUi::csvNumber(($eCultivation['harvestedNormalized'] !== NULL and $eSeries['area'] !== NULL) ? round($eCultivation['harvestedNormalized'] / $eSeries['area'], 1) : NULL),
			];

			foreach($eCultivation['cSlice'] as $eSlice) {

				$line[] = $eSlice['variety']['name'];

				if($eCultivation['sliceUnit'] === Cultivation::PERCENT) {
					$line[] = $eSlice['partPercent'];
				} else {

					$line[] = match($eSeries['use']) {
						Series::BED => (int)($eSlice['partLength'] / $eSeries['length'] * 100),
						Series::BLOCK => (int)($eSlice['partArea'] / $eSeries['area'] * 100),
					};

				}

			}

			$output[] = $line;

		}

		return $output;

	}

	public static function uploadCultivations(\farm\Farm $eFarm): bool {

		if(isset($_FILES['csv']) === FALSE) {
			return FALSE;
		}

		$file = $_FILES['csv']['tmp_name'];

		// Vérification de la taille (max 1 Mo)
		if(filesize($file) > 1024 * 1024) {
			\Fail::log('csvSize');
			return FALSE;
		}

		$csv = \util\CsvLib::parseCsv($file, ',');

		$header = $csv[0];

		if(count(array_intersect($header, ['crop', 'in_greenhouse'])) === 2) {
			$csv = self::convertFromBrinjel($eFarm, $csv);
		} else if(count(array_intersect($header, ['series_name', 'season', 'place', 'species', 'use'])) !== 5) {
			dd('faire un tableau associatif');
			\Fail::log('csvSource');
			return FALSE;
		}

		\Cache::redis()->set('import-cultivations-'.$eFarm['id'], $csv);

		return TRUE;

	}

	public static function convertFromBrinjel(\farm\Farm $eFarm, array $cultivations): array {

		$import = [];

		$head = array_shift($cultivations);

		foreach($cultivations as $cultivation) {

			$line = array_combine($head, $cultivation);

			$sowing = $line['sowing_date'] ?: NULL;
			$planting = $line['planting_date'] ?: NULL;

			// planting_type
			$plantingType = match($line['planting_type'] ?? NULL) {
				'direct_seeded' => Cultivation::SOWING,
				'transplant_raised' => Cultivation::YOUNG_PLANT,
				'transplant_bought' => Cultivation::YOUNG_PLANT_BOUGHT,
				default => NULL
			};

			if($plantingType === NULL) {

				if($sowing !== NULL and $planting === NULL) {
					$plantingType = Cultivation::SOWING;
				} else if($sowing !== NULL and $planting !== NULL) {
					$plantingType = Cultivation::YOUNG_PLANT_BOUGHT;
				} else if($sowing === NULL and $planting !== NULL) {
					$plantingType = Cultivation::YOUNG_PLANT;
				}

			}

			// first_harvest_date et last_harvest_date
			$firstHarvestDate = $line['first_harvest_date'] ?: NULL;
			$lastHarvestDate = $line['last_harvest_date'] ?: NULL;

			if(
				$firstHarvestDate === NULL or
				$lastHarvestDate === NULL
			) {
				$firstHarvestDate = NULL;
				$lastHarvestDate = NULL;
			}

			$season = (int)substr($firstHarvestDate ?? $planting ?? $sowing ?? currentDate(), 0, 4);

			$import[] = [
				'series' => [
					'name' => NULL
				],
				'cultivations' => [
					[
						'season' => $season,
						'place' => (($line['in_greenhouse'] ?? 'false') === 'true') ? Series::GREENHOUSE : Series::OUTDOOR,
						'species' => $line['crop'] ?? NULL,
						'planting_type' => $plantingType,
						'young_plants_seeds' => $line['seeds_per_hole_seedling'] ?? $line['seeds_per_hole_direct'] ?? NULL,
						'young_plants_tray' => $line['container_name'] ?? NULL,
						'young_plants_tray_size' => $line['container_size'] ?? NULL,
						'sowing_date' => $sowing,
						'planting_date' => $planting,
						'first_harvest_date' => $firstHarvestDate,
						'last_harvest_date' => $lastHarvestDate,
						'use' => Series::BED,
						'block_area' => NULL,
						'block_density' => NULL,
						'block_spacing_rows' => NULL,
						'block_spacing_plants' => NULL,
						'bed_length' => $line['length'] ? (int)$line['length'] : NULL,
						'bed_density' => NULL,
						'bed_rows' => $line['rows'] ? (int)$line['rows'] : NULL,
						'bed_spacing_plants' => $line['spacing_plants'] ? (int)$line['spacing_plants'] : NULL,
						'finished' => (($line['finished'] ?? 'false') === 'true'),
						'harvest_unit' => $line['unit'] ?: NULL,
						'yield_expected_area' => NULL,
						'yield_expected_length' => $line['yield_per_bed_meter'] ?? NULL,
						'varieties' => isset($line['variety']) ? [['variety' => $line['variety'], 'part' => 100]] : []
					]
				]
			];

		}

		return $import;

	}

	public static function reset(\farm\Farm $eFarm): void {

		\Cache::redis()->delete('import-cultivations-'.$eFarm['id']);

	}

	public static function import(\farm\Farm $eFarm): ?array {

		$import = \Cache::redis()->get('import-cultivations-'.$eFarm['id']);

		if($import === FALSE) {
			return NULL;
		}

		$errorsCount = 0;
		$errorsGlobal = [
			'species' => [],
			'harvestUnit' => []
		];
		$infoGlobal = [
			'varieties' => [],
			'tools' => []
		];

		$cachePlants = [];
		$cacheVarieties = [];

		foreach($import as $key1 => ['series' => $series, 'cultivations' => $cultivations]) {

			foreach($cultivations as $key2 => $cultivation) {

				$errors = [];

				if($cultivation['species'] === NULL) {
					$errors[] = 'speciesEmpty';
					continue;
				}

				// crop
				$plantFqn = toFqn($cultivation['species']);

				if(empty($cachePlants[$plantFqn])) {

					$cachePlants[$plantFqn] = \plant\Plant::model()
						->select(['id', 'vignette', 'fqn', 'name'])
						->whereFarm($eFarm)
						->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\plant\Plant::model()->format(str_replace('-', ' ', $plantFqn)))
						->get();

				}

				$ePlant = $cachePlants[$plantFqn];

				$import[$key1]['cultivations'][$key2]['ePlant'] = $ePlant;

				if($cachePlants[$plantFqn]->empty()) {
					$errorsGlobal['species'][] = $cultivation['species'];
				}

				// varieties
				foreach($cultivation['varieties'] as $key => ['variety' => $variety, 'part' => $part]) {

					$varietyFqn = toFqn($variety);

					if($ePlant->empty()) {
						$eVariety = new \plant\Variety();
					} else {

						$cacheKey = $ePlant['id'].'-'.$varietyFqn;

						if(empty($cacheVarieties[$cacheKey])) {

							$cacheVarieties[$cacheKey] = \plant\Variety::model()
								->select(['id', 'name'])
								->whereFarm($eFarm)
								->wherePlant($ePlant)
								->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\plant\Plant::model()->format(str_replace('-', ' ', $varietyFqn)))
								->get();

							$eVariety = $cacheVarieties[$cacheKey];

						}

					}

					$import[$key1]['cultivations'][$key2]['varieties'][$key2]['eVariety'] = $eVariety;

					if($cachePlants[$plantFqn]->empty()) {
						$infoGlobal['varieties'][] = $cultivation['species'];
					}

				}

				$errors[] = self::checkDateField($cultivation['sowing_date'], 'sowingDateFormat');
				$errors[] = self::checkDateField($cultivation['planting_date'], 'plantingDateFormat');
				$errors[] = self::checkDateField($cultivation['first_harvest_date'], 'firstHarvestDateFormat');
				$errors[] = self::checkDateField($cultivation['last_harvest_date'], 'lastHarvestDateFormat');

				if(
					$cultivation['first_harvest_date'] !== NULL and
					$cultivation['first_harvest_date'] > $cultivation['last_harvest_date']
				) {
					$errors[] = 'harvestDateConsistency';
				}

				if(
					$cultivation['harvest_unit'] !== NULL and
					in_array($cultivation['harvest_unit'], Cultivation::model()->getPropertyEnum('mainUnit')) === FALSE
				) {
					$errorsGlobal['harvestUnit'][] = $cultivation['harvest_unit'];
				}

				switch($cultivation['use']) {

					case Series::BED :
						if(
							$cultivation['bed_density'] !== NULL and
							($cultivation['bed_rows'] !== NULL or $cultivation['bed_spacing_plants'] !== NULL)
						) {
							$errors[] = 'bedSpacing';
						}
						break;

					case Series::BLOCK :
						if(
							$cultivation['block_density'] !== NULL and
							($cultivation['block_spacing_rows'] !== NULL or $cultivation['block_spacing_rows'] !== NULL)
						) {
							$errors[] = 'blockSpacing';
						}
						break;

				}

				$errors = array_filter($errors);
				$errors = array_unique($errors);

				$errorsCount += count($errors);

				$import[$key1]['cultivations'][$key2]['errors'] = $errors;

			}

		}

		$errorsGlobal['harvestUnit'] = array_unique($errorsGlobal['harvestUnit']);
		$errorsGlobal['species'] = array_unique($errorsGlobal['species']);

		$infoGlobal['varieties'] = array_unique($infoGlobal['varieties']);

		return [
			'import' => $import,
			'errorsCount' => $errorsCount + count($errorsGlobal['harvestUnit']) + count($errorsGlobal['species']),
			'errorsGlobal' => $errorsGlobal,
			'infoGlobal' => $infoGlobal
		];

	}

	private static function checkDateField(mixed $value, string $error): ?string {

		if(
			$value !== NULL and
			\Filter::check('date', $value) === FALSE
		) {
			return $error;
		} else {
			return NULL;
		}

	}

}
?>
