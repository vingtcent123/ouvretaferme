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

	public static function saveCultivations(\farm\Farm $eFarm): bool {

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

		if(count(array_intersect($header, ['series_name', 'season', 'place', 'species', 'use'])) === 5) {
			$source = 'ouvretaferme';
		} else if(count(array_intersect($header, ['family', 'crop', 'in_greenhouse'])) === 3) {
			$source = 'brinjel';
		} else {
			\Fail::log('csvSource');
			return FALSE;
		}

		\Cache::redis()->set('import-cultivations-'.$eFarm['id'], [
			'source' => $source,
			'data' => $csv
		]);

		return TRUE;

	}

}
?>
