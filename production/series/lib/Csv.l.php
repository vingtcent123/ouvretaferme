<?php
namespace series;

class CsvLib {

	public static function getExportTimesheet(\farm\Farm $eFarm, int $year): array {

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
					($eTimesheet['harvestUser'] and $eTimesheet['harvestUnit']) ? \selling\UnitUi::getSingular($eTimesheet['harvestUnit']) : '',
					$eTimesheet['harvestSize']->empty() ? '' : $eTimesheet['harvestSize']['name'],
				];

			}

		}

		return $output;

	}

	public static function getExportTasks(\farm\Farm $eFarm, int $year): array {

		$cTask = Task::model()
				->select([
					'id',
					'plannedWeek', 'doneWeek',
					'time',
					'action' => ['name'],
					'category' => ['name'],
					'series' => ['name', 'mode'],
					'plant' => ['name'],
					'variety' => ['name'],
					'description',
					'tools',
					'methods',
					'cTool?' => fn($e) => fn() => \farm\ToolLib::askByFarm($eFarm, $e['tools']),
					'cMethod?' => fn($e) => fn() => \farm\MethodLib::askByFarm($eFarm, $e['methods']),
					'harvest',
					'harvestUnit',
					'harvestSize' => ['name']
				])
			->whereFarm($eFarm)
			->where(new \Sql('IF(doneWeek IS NULL, plannedWeek, doneWeek) LIKE "'.$year.'%"'))
			->sort(new \Sql('IF(doneWeek IS NULL, plannedWeek, doneWeek), id'))
			->getCollection();

		$output = [];

		foreach($cTask as $eTask) {

			$output[] = [
				$eTask['plannedWeek'],
				$eTask['doneWeek'],
				$eTask['category']['name'],
				$eTask['action']['name'],
				\util\TextUi::csvNumber($eTask['time']),
				$eTask['series']->empty() ? '' : $eTask['series']['id'],
				$eTask['series']->empty() ? '' : $eTask['series']['name'],
				$eTask['plant']->empty() ? '' : $eTask['plant']['name'],
				$eTask['variety']->empty() ? '' : $eTask['variety']['name'],
				implode(', ', $eTask['cTool?']()->getColumn('name')),
				implode(', ', $eTask['cMethod?']()->getColumn('name')),
				$eTask['description'],
				\util\TextUi::csvNumber($eTask['harvest']),
				($eTask['harvest'] and $eTask['harvestUnit']) ? \selling\UnitUi::getSingular($eTask['harvestUnit']) : '',
				$eTask['harvestSize']->empty() ? '' : $eTask['harvestSize']['name'],
			];

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
				\selling\UnitUi::getSingular($eHarvest['unit']),
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

		$cTool = \farm\ToolLib::getTraysByFarm($eFarm);

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
						'toolList' => new \Sql('CONCAT("{", GROUP_CONCAT(CONCAT("\\"", IF(doneWeek IS NOT NULL, doneWeek, plannedWeek), "\\":", tools) SEPARATOR ","), "}")'),
						'toolName' => function($e) use($cTool) {

							$tools = json_decode($e['toolList'], TRUE);

							if(empty($tools[$e['min']]) === FALSE) {

								foreach($tools[$e['min']] as $tool) {
									if($cTool->offsetExists($tool)) {
										return $cTool[$tool]['name'];
									}
								}

							}

							return '';

						}
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
				$eSeries['mode'],
				$eSeries['use'],
				$eCultivation['plant']['name'],
				($eCultivation['seedling'] !== NULL) ? $eCultivation['seedling'] : NULL,
				in_array($eCultivation['seedling'], [Cultivation::SOWING, Cultivation::YOUNG_PLANT]) ? $eCultivation['seedlingSeeds'] : NULL,
				$youngPlantTray,
				$sowingDate,
				$plantingDate,
				$minHarvestDate,
				$maxHarvestDate,
				($eSeries['use'] === Series::BLOCK) ? $eSeries['area'] ?? $eSeries['areaTarget'] ?? NULL : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::DENSITY) ? $eCultivation['density'] : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['rowSpacing'] : NULL,
				($eSeries['use'] === Series::BLOCK and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['plantSpacing'] : NULL,
				($eSeries['use'] === Series::BED) ? $eSeries['length'] ?? $eSeries['lengthTarget'] ?? NULL : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::DENSITY) ? $eCultivation['density'] : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['rows'] : NULL,
				($eSeries['use'] === Series::BED and $eCultivation['distance'] === Cultivation::SPACING) ? $eCultivation['plantSpacing'] : NULL,
				$eSeries['status'] === Series::CLOSED ? 'yes' : 'no',
				$eCultivation['mainUnit'],
				\util\TextUi::csvNumber($eCultivation['yieldExpected']),
				\util\TextUi::csvNumber(($eCultivation['harvestedNormalized'] !== NULL and $eSeries['area'] !== NULL) ? round($eCultivation['harvestedNormalized'] / $eSeries['area'], 1) : NULL),
			];

			if($eCultivation['cSlice']->notEmpty()) {

				$slices = [];

				$limit = match($eCultivation['sliceUnit']) {
					Cultivation::PERCENT => 100,
					Cultivation::LENGTH => ($eSeries['use'] === Series::BED) ? ($eSeries['length'] ?? $eSeries['lengthTarget']) : NULL,
					Cultivation::AREA => ($eSeries['use'] === Series::BLOCK) ? ($eSeries['area'] ?? $eSeries['areaTarget']) : NULL,
					Cultivation::PLANT => $eCultivation['cSlice']->sum('partPlant'),
					Cultivation::TRAY => $eCultivation['cSlice']->sum('partTray'),
				};

				foreach($eCultivation['cSlice'] as $eSlice) {

					$slices[] = [
						'variety' => $eSlice['variety']['name'],
						'part' => $limit ? (int)($eSlice['part'.ucfirst($eCultivation['sliceUnit'])] / $limit * 100) : NULL
					];

				}


				if($eCultivation['sliceUnit'] !== Cultivation::PERCENT) {

					$required = match($eCultivation['sliceUnit']) {
						Cultivation::LENGTH => $limit ? (int)($eCultivation['cSlice']->sum('partLength') / $limit) : NULL,
						Cultivation::AREA => $limit ? (int)($eCultivation['cSlice']->sum('partArea') / $limit) : NULL,
						Cultivation::PLANT => 100,
						Cultivation::TRAY => 100,
					};

					$rest = $required - array_sum(array_column($slices, 'part'));

					for($i = 0; $i < $rest; $i++) {
						$slices[$i % count($slices)]['part']++;
					}

				}

				foreach($slices as $slice) {
					$line[] = $slice['variety'];
					$line[] = $slice['part'];
				}

			}

			$output[] = $line;

		}

		return $output;

	}

	public static function getExportSoil(\farm\Farm $eFarm, int $season, &$maxSpecies): array {

		$cPlace = Place::model()
			->select([
				'zone' => ['name'],
				'plot' => ['name', 'zoneFill'],
				'bed' => ['name', 'plotFill'],
				'length',
				'area',
				'series' => [
					'season',
					'name', 'bedStartCalculated', 'bedStopCalculated',
					'cCultivation' => Cultivation::model()
						->select([
							'series',
							'plant' => ['name']
						])
						->delegateCollection('series')
				]
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->whereSeries('!=', NULL)
			->getCollection()
			->sort([
				'zone' => ['name'],
				'plot' => ['name'],
				'bed' => ['name'],
			], natural: TRUE);

		$maxSpecies = 0;

		$output = [];

		foreach($cPlace as $ePlace) {

			$maxSpecies = max($maxSpecies, $ePlace['series']['cCultivation']->count());

			$line = [
				$ePlace['zone']['name'],
				$ePlace['plot']['zoneFill'] ? '' : $ePlace['plot']['name'],
				$ePlace['bed']['plotFill'] ? '' : $ePlace['bed']['name'],
				$ePlace['series']['id'],
				$ePlace['series']['name'],
				\util\TextUi::csvNumber($ePlace['length']),
				\util\TextUi::csvNumber($ePlace['area']),
				$ePlace['series']->getBedStart(),
				$ePlace['series']->getBedStop(),
			];

			foreach($ePlace['series']['cCultivation'] as $eCultivation) {
				$line[] = $eCultivation['plant']['name'];
			}

			$output[] = $line;

		}

		return $output;

	}

	public static function uploadCultivations(\farm\Farm $eFarm): bool {

		return \main\CsvLib::upload('import-cultivations-'.$eFarm['id'], function($csv) {

			$header = $csv[0];

			if(count(array_intersect($header, ['crop', 'in_greenhouse', 'planting_type', 'unit'])) === 4) {

				$csv = self::convertFromBrinjel($csv);

			} else if(count(array_intersect($header, ['series_name', 'season', 'mode', 'species', 'use', 'planting_type', 'harvest_unit'])) === 7) {
				$csv = self::convertFromOtf($csv);
				if($csv === NULL) {
					return NULL;
				}
			} else {
				\Fail::log('main\csvSource');
				return NULL;
			}

			return $csv;

		});

	}

	public static function convertFromBrinjel(array $cultivations): array {

		$import = [];

		$head = array_shift($cultivations);

		foreach($cultivations as $cultivation) {

			if(count($cultivation) < count($head)) {
				$cultivation = array_merge($cultivation, array_fill(0, count($head) - count($cultivation), ''));
			} else if(count($head) < count($cultivation)) {
				$cultivation = array_slice(0, count($head));
			}

			$line = array_combine($head, $cultivation) + [
				'crop' => '',
				'sowing_date' => '',
				'planting_date' => '',
				'first_harvest_date' => '',
				'last_harvest_date' => '',
				'length' => '',
				'rows' => '',
				'planting_type' => '',
				'variety' => '',
				'provider' => '',
				'finished' => '',
				'in_greenhouse' => '',
				'price_per_unit' => '',
				'spacing_plants' => '',
				'unit' => '',
				'yield_per_bed_meter' => '',
				'estimated_greenhouse_loss' => '',
				'seeds_per_gram' => '',
				'seeds_per_hole_seedling' => '',
				'seeds_per_hole_direct' => '',
				'seeds_extra_percentage' => '',
				'container_name' => '',
				'container_size' => ''
			];

			$sowing = \main\CsvLib::formatDateField($line['sowing_date'] ?: NULL);
			$planting = \main\CsvLib::formatDateField($line['planting_date'] ?: NULL);

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

			$firstHarvestDate = \main\CsvLib::formatDateField($line['first_harvest_date'] ?: NULL);
			$lastHarvestDate = \main\CsvLib::formatDateField($line['last_harvest_date'] ?: NULL);

			$season = (int)substr($firstHarvestDate ?? $planting ?? $sowing ?? currentDate(), 0, 4);
			$mode = (($line['in_greenhouse'] ?? 'false') === 'true') ? Series::GREENHOUSE : Series::OPEN_FIELD;

			$harvestUnit = CsvUi::convertUnit($line['unit']);
			$crop = $line['crop'] ?? NULL;

			$rows = (int)$line['rows'] ?: NULL;
			$spacingPlants = round((float)$line['spacing_plants'], 2) ?: NULL;

			$hash = md5($season.'-'.$mode.'-'.$crop.'-'.$plantingType.'-'.$rows.'-'.$spacingPlants.'-'.$sowing.'-'.$planting.'-'.$harvestUnit.'-'.$firstHarvestDate.'-'.$lastHarvestDate);

			$import[$hash] ??= [
				'series' => [
					'season' => $season,
					'name' => $crop,
					'mode' => $mode,
					'use' => Series::BED,
					'bed_length' => 0,
					'block_area' => NULL,
					'finished' => (($line['finished'] ?? 'false') === 'true'),
				],
				'cultivations' => [
					[
						'species' => $crop,
						'planting_type' => $plantingType,
						'seeds_per_hole' => match($plantingType) {
							Cultivation::SOWING => $line['seeds_per_hole_direct'] ? (int)$line['seeds_per_hole_direct'] : NULL,
							default => $line['seeds_per_hole_seedling'] ? (int)$line['seeds_per_hole_seedling'] : NULL
						},
						'young_plants_tray' => $line['container_name'] ?: NULL,
						'young_plants_tray_size' => $line['container_size'] ?: NULL,
						'sowing_date' => $sowing,
						'planting_date' => $planting,
						'first_harvest_date' => $firstHarvestDate,
						'last_harvest_date' => $lastHarvestDate,
						'block_density' => NULL,
						'block_spacing_rows' => NULL,
						'block_spacing_plants' => NULL,
						'bed_density' => NULL,
						'bed_rows' => $rows,
						'bed_spacing_plants' => $spacingPlants,
						'harvest_unit' => $harvestUnit,
						'yield_expected_area' => NULL,
						'yield_expected_length' => (float)$line['yield_per_bed_meter'] ? \main\CsvLib::formatFloat($line['yield_per_bed_meter']) : NULL,
						'varieties_unit' => Cultivation::LENGTH,
						'varieties_list' => [],
						'varieties' => []
					]
				]
			];

			$length = ($line['length'] ? (int)$line['length'] : 1);

			$import[$hash]['series']['bed_length'] += $length;

			if(
				$line['variety'] !== '' and
				in_array($line['variety'], $import[$hash]['cultivations'][0]['varieties_list']) === FALSE
			) {

				$import[$hash]['cultivations'][0]['varieties_list'][] = $line['variety'];
				$import[$hash]['cultivations'][0]['varieties'][] = [
					'variety' => $line['variety'],
					'eVariety' => new \plant\Variety(),
					'part' => $length
				];

			}

		}

		return $import;

	}

	public static function convertFromOtf(array $cultivations): ?array {

		$import = [];

		$head = array_shift($cultivations);

		// Recherche des colonnes liées aux variétés
		$varietiesIndex = [];

		foreach($head as $index => $column) {

			if($column !== 'variety_name') {
				continue;
			}

			if(
				array_key_exists($index + 1, $head) === FALSE or
				$head[$index + 1] !== 'variety_part'
			) {
				\Fail::log('csvVariety');
				return NULL;
			}

			$varietiesIndex[] = $index;

		}

		$seriesIndex = [];

		foreach($cultivations as $cultivation) {

			if(count($cultivation) < count($head)) {
				$cultivation = array_merge($cultivation, array_fill(0, count($head) - count($cultivation), ''));
			} else if(count($head) < count($cultivation)) {
				$cultivation = array_slice($cultivation, 0, count($head));
			}

			$line = array_combine($head, $cultivation) + [
				'season' => '',
				'series_id' => '',
				'series_name' => '',
				'mode' => '',
				'use' => '',
				'species' => '',
				'planting_type' => '',
				'seeds_per_hole' => '',
				'young_plants_tray' => '',
				'sowing_date' => '',
				'planting_date' => '',
				'first_harvest_date' => '',
				'last_harvest_date' => '',
				'block_area' => '',
				'block_density' => '',
				'block_spacing_rows' => '',
				'block_spacing_plants' => '',
				'bed_length' => '',
				'bed_density' => '',
				'bed_rows' => '',
				'bed_spacing_plants' => '',
				'finished' => '',
				'harvest_unit' => '',
				'yield_expected_area' => '',
			];

			$varieties = [];

			foreach($varietiesIndex as $varietyIndex) {

				if($cultivation[$varietyIndex] !== '') {

					$varieties[] = [
						'variety' => $cultivation[$varietyIndex],
						'part' => (int)$cultivation[$varietyIndex + 1],
						'eVariety' => new \plant\Variety()
					];

				}

			}

			$season = (int)($line['season'] ?: date('Y'));
			$seriesName = $line['series_name'] ?: NULL;
			$seriesId = $line['series_id'] ?: NULL;

			if($seriesId) {

				if(array_key_exists($seriesId, $seriesIndex)) {
					$index = $seriesIndex[$seriesId];
				} else {

					$index = count($import);
					$seriesIndex[$seriesId] = $index;

					$import[$index] = [
						'series' => [
							'season' => $season,
							'name' => $seriesName,
							'mode' => $line['mode'] ?: NULL,
							'use' => $line['use'] ?: NULL,
							'bed_length' => (int)round((float)$line['bed_length']) ?: NULL,
							'block_area' => (int)round((float)$line['block_area']) ?: NULL,
							'finished' => (($line['finished'] ?? 'no') === 'yes'),
						],
						'cultivations' => []
					];

				}

			} else {

				$index = count($import);

				$import[$index] = [
					'series' => [
						'season' => $season,
						'name' => NULL,
						'mode' => $line['mode'] ?: NULL,
						'use' => $line['use'] ?: NULL,
						'bed_length' => (int)round((float)$line['bed_length']) ?: NULL,
						'block_area' => (int)round((float)$line['block_area']) ?: NULL,
						'finished' => (($line['finished'] ?? 'no') === 'yes'),
					],
					'cultivations' => []
				];

			}

			$import[$index]['cultivations'][] = [
				'species' => $line['species'] ?? NULL,
				'planting_type' => $line['planting_type'] ?: NULL,
				'seeds_per_hole' => $line['seeds_per_hole'] ?: NULL,
				'young_plants_tray' => $line['young_plants_tray'] ?: NULL,
				'young_plants_tray_size' => NULL,
				'sowing_date' => $line['sowing_date'] ?: NULL,
				'planting_date' => $line['planting_date'] ?: NULL,
				'first_harvest_date' => $line['first_harvest_date'] ?: NULL,
				'last_harvest_date' => $line['last_harvest_date'] ?: NULL,
				'block_density' => (float)$line['block_density'] ? \main\CsvLib::formatFloat($line['block_density']) : NULL,
				'block_spacing_rows' => (int)round((float)$line['block_spacing_rows']) ?: NULL,
				'block_spacing_plants' => $line['block_spacing_plants'] ? \main\CsvLib::formatFloat($line['block_spacing_plants']) : NULL,
				'bed_density' => $line['bed_density'] ? \main\CsvLib::formatFloat($line['bed_density']) : NULL,
				'bed_rows' => (int)round((float)$line['bed_rows']) ?: NULL,
				'bed_spacing_plants' => $line['bed_spacing_plants'] ? \main\CsvLib::formatFloat($line['bed_spacing_plants']) : NULL,
				'harvest_unit' => $line['harvest_unit'] ?: NULL,
				'yield_expected_area' => (float)$line['yield_expected_area'] ? \main\CsvLib::formatFloat($line['yield_expected_area']) : NULL,
				'yield_expected_length' => NULL,
				'varieties_unit' => Cultivation::PERCENT,
				'varieties' => $varieties
			];

		}

		// Calcul des noms
		foreach($import as $key => ['series' => $series, 'cultivations' => $cultivations]) {

			if($series['name'] === NULL) {
				$import[$key]['series']['name'] = implode(' + ', array_column($cultivations, 'species'));
			}

		}

		return $import;

	}

	public static function reset(\farm\Farm $eFarm): bool {

		return \Cache::redis()->delete('import-cultivations-'.$eFarm['id']);

	}

	public static function importCultivations(\farm\Farm $eFarm, array $list): bool {

		$fw = new \FailWatch();
		$prepared = [];

		$eVarietyEmpty = \plant\VarietyLib::getByFqn('unknown');

		foreach($list as ['series' => $series, 'cultivations' => $cultivations]) {

			$input = [
				'farm' => $eFarm,
				'name' => $series['name'],
				'use' => $series['use'],
				'cycle' => Series::ANNUAL,
				'mode' => $series['mode'],
				'season' => $series['season'],
				'sequence' => new \sequence\Sequence(),
				'areaTarget' => ($series['block_area'] <= 0) ? NULL : $series['block_area'],
				'lengthTarget' => ($series['bed_length'] <= 0) ? NULL : $series['bed_length'],
				'bedWidth' => $series['use'] === Series::BED ? $eFarm['defaultBedWidth'] : NULL,
				'alleyWidth' => $series['use'] === Series::BED ? $eFarm['defaultAlleyWidth'] : NULL,
				'plant' => []
			];

			$position = 0;

			foreach($cultivations as $cultivation) {

				$input['plant'][$position] = $cultivation['ePlant']['id'];
				$input['sliceUnit'][$position] = $cultivation['varieties_unit'];
				$input['seedling'][$position] = $cultivation['planting_type'];
				$input['seedlingSeeds'][$position] = (int)($cultivation['seeds_per_hole'] ?: 1);
				$input['mainUnit'][$position] = $cultivation['harvest_unit'];
				$input['variety'][$position] = [
					'variety' => [],
					'varietyCreate' => [],
					'varietyPartPercent' => []
				];

				switch($input['seedling'][$position]) {

					case Cultivation::SOWING :
						$input['actions'][$position] = [
							ACTION_SEMIS_DIRECT => $cultivation['sowing_date'] ? toWeek($cultivation['sowing_date']) : NULL
						];
						break;

					case Cultivation::YOUNG_PLANT :
						$input['actions'][$position] = [
							ACTION_SEMIS_PEPINIERE => $cultivation['sowing_date'] ? toWeek($cultivation['sowing_date']) : NULL,
							ACTION_PLANTATION => $cultivation['planting_date'] ? toWeek($cultivation['planting_date']) : NULL,
						];
						break;

					case Cultivation::YOUNG_PLANT_BOUGHT :
						$input['actions'][$position] = [
							ACTION_PLANTATION => $cultivation['planting_date'] ? toWeek($cultivation['planting_date']) : NULL,
						];
						break;

				}

				switch($series['use']) {

					case Series::BED :
						$input['distance'][$position] = $cultivation['bed_density'] ? Cultivation::DENSITY : Cultivation::SPACING;
						$input['density'][$position] = $cultivation['bed_density'];
						$input['rows'][$position] = $cultivation['bed_rows'];
						$input['plantSpacing'][$position] = $cultivation['bed_spacing_plants'];
						$input['rowSpacing'][$position] = NULL;

						if($cultivation['yield_expected_area'] !== NULL) {
							$input['yieldExpected'][$position] = $cultivation['yield_expected_area'];
						} else if($cultivation['yield_expected_length'] !== NULL) {
							$input['yieldExpected'][$position] = round($cultivation['yield_expected_length'] * ($input['bedWidth'] + $input['alleyWidth'] ?? 0) / 100, 1);
						} else {
							$input['yieldExpected'][$position] = NULL;
						}
						break;

					case Series::BLOCK :
						$input['distance'][$position] = $cultivation['block_density'] ? Cultivation::DENSITY : Cultivation::SPACING;
						$input['density'][$position] = $cultivation['block_density'];
						$input['rows'][$position] = NULL;
						$input['plantSpacing'][$position] = $cultivation['block_spacing_plants'];
						$input['rowSpacing'][$position] = $cultivation['block_spacing_rows'];
						$input['yieldExpected'][$position] = $cultivation['yield_expected_area'];
						break;

				}

				// variety
				foreach($cultivation['varieties'] as ['variety' => $variety, 'eVariety' => $eVariety, 'part' => $part]) {

					$input['variety'][$position]['variety'][] = $eVariety->empty() ? 'new' : $eVariety['id'];
					$input['variety'][$position]['varietyCreate'][] = $eVariety->empty() ? $variety : NULL;
					$input['variety'][$position][match($cultivation['varieties_unit']) {
						Cultivation::LENGTH => 'varietyPartLength',
						Cultivation::PERCENT => 'varietyPartPercent'
					}][] = $part;

				}

				if($input['variety'][$position]['variety'] === []) {

					$input['sliceUnit'][$position] = Cultivation::PERCENT;

					$input['variety'][$position]['variety'][] = $eVarietyEmpty;
					$input['variety'][$position]['varietyCreate'][] = NULL;
					$input['variety'][$position]['varietyPartPercent'][] = 100;

				}

				// harvest
				if($cultivation['first_harvest_date']) {

					$weeks = [];

					$firstWeek = toWeek($cultivation['first_harvest_date']);
					$lastWeek = toWeek($cultivation['last_harvest_date']);

					for($week = $firstWeek; $week <= $lastWeek; $week = toWeek(strtotime($week.' + 1 WEEK'))) {
						$weeks[] = $week;
					}

					$input['harvestPeriodExpected'][$position] = Cultivation::WEEK;
					$input['harvestWeeksExpected'][$position] = $weeks;
					$input['harvestMonthsExpected'][$position] = NULL;


				} else {

					$input['harvestPeriodExpected'][$position] = Cultivation::WEEK;
					$input['harvestWeeksExpected'][$position] = NULL;
					$input['harvestMonthsExpected'][$position] = NULL;

				}

				$position++;

			}

			$prepare = \series\SeriesLib::prepareCreate($input);

			if($fw->ko()) {
				return FALSE;
			}

			$prepare[0]['status'] = ($series['finished'] === TRUE) ? Series::CLOSED : Series::OPEN;

			$prepared[] = $prepare;

		}

		if(self::reset($eFarm)) {

			foreach($prepared as $prepare) {
				\series\SeriesLib::createWithCultivations(...$prepare);
			}

		}

		return TRUE;

	}

	public static function getCultivations(\farm\Farm $eFarm): ?array {

		$import = \Cache::redis()->get('import-cultivations-'.$eFarm['id']);

		if($import === FALSE) {
			return NULL;
		}

		$errorsCount = 0;
		$errorsGlobal = [
			'beds' => FALSE,
			'harvestUnit' => [],
			'species' => [],
			'tools' => [],
			'seasons' => []
		];
		$infoGlobal = [
			'varieties' => [],
			'speciesPerennial' => [],
			'beds' => FALSE,
		];

		$cachePlants = [];
		$cacheVarieties = [];
		$cacheTools = [];

		foreach($import as $key1 => ['series' => $series, 'cultivations' => $cultivations]) {

			$errorsCommon = [];

			if(in_array($series['mode'], Series::model()->getPropertyEnum('mode')) === FALSE) {
				$errorsCommon[] = 'modeInvalid';
			}

			if(in_array($series['use'], Series::model()->getPropertyEnum('use')) === FALSE) {
				$errorsCommon[] = 'useInvalid';
			}

			if($series['use'] === Series::BED) {
				$infoGlobal['beds'] = TRUE;
				if($eFarm['defaultBedWidth'] === NULL) {
					$errorsGlobal['beds'] = TRUE;
				}
			}

			if(
				$series['season'] < $eFarm['seasonFirst'] or
				$series['season'] > $eFarm['seasonLast']
			) {
				$errorsGlobal['seasons'][] = $series['season'];
			}

			$species = array_count_values(array_column($cultivations, 'species'));

			foreach($cultivations as $key2 => $cultivation) {

				$ignore = FALSE;
				$errors = $errorsCommon;

				if($cultivation['species'] === NULL) {
					$errors[] = 'speciesEmpty';
					$ePlant = new \plant\Plant();
				} else {

					if($species[$cultivation['species']] >= 2) {
						$errors[] = 'speciesDuplicate';
					}

					// crop
					$plantFqn = toFqn($cultivation['species'], ' ');

					if(empty($cachePlants[$plantFqn])) {

						$cachePlants[$plantFqn] = \plant\Plant::model()
							->select(['id', 'vignette', 'fqn', 'name', 'cycle'])
							->whereFarm($eFarm)
							->whereStatus(\plant\Plant::ACTIVE)
							->or(
			                fn() => $this->whereName($cultivation['species']),
			                fn() => $this->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\plant\Plant::model()->format($plantFqn))
							)
							->get();

					}

					$ePlant = $cachePlants[$plantFqn];

					if($cachePlants[$plantFqn]->empty()) {
						$errorsGlobal['species'][] = $cultivation['species'];
					} else if($cachePlants[$plantFqn]['cycle'] === \plant\Plant::PERENNIAL) {
						$infoGlobal['speciesPerennial'][] = $cultivation['species'];
						$ignore = TRUE;
					}

					// varieties
					if($ignore === FALSE) {

						$varietyTotal = 0;

						foreach($cultivation['varieties'] as $key3 => ['variety' => $variety, 'part' => $part]) {

							$varietyFqn = toFqn($variety, ' ');
							$varietyTotal += (int)$part;

							$eVariety = new \plant\Variety();

							if($ePlant->notEmpty()) {

								$cacheKey = $ePlant['id'].'-'.$varietyFqn;

								if(empty($cacheVarieties[$cacheKey])) {

									$cacheVarieties[$cacheKey] = \plant\Variety::model()
										->select(['id', 'name'])
										->whereFarm($eFarm)
										->wherePlant($ePlant)
										->or(
						                fn() => $this->whereName($variety),
						                fn() => $this->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\plant\Variety::model()->format($varietyFqn))
										)
										->get();

								}

								$eVariety = $cacheVarieties[$cacheKey];

							}

							$import[$key1]['cultivations'][$key2]['varieties'][$key3]['eVariety'] = $eVariety;

							if($eVariety->empty()) {
								$infoGlobal['varieties'][] = $variety;
							}

						}

					}

					// trays
					if($cultivation['young_plants_tray']) {

						$tool = $cultivation['young_plants_tray'];
						$toolFqn = toFqn($cultivation['young_plants_tray'], ' ');

						if(empty($cacheTools[$toolFqn])) {

							$cacheTools[$toolFqn] = \farm\Tool::model()
								->select(['id', 'name'])
								->whereFarm($eFarm)
								->or(
				                fn() => $this->whereName($tool),
				                fn() => $this->where('REGEXP_REPLACE(REPLACE(name, "-", " "), " +", " ") = '.\farm\Tool::model()->format($toolFqn))
								)
								->get();

						}

						$eTool = $cacheTools[$toolFqn];

						if($eTool->empty()) {
							$errorsGlobal['tools'][] = $cultivation['young_plants_tray'];
						}

					} else {
						$eTool = new \farm\Tool();
					}

					$import[$key1]['cultivations'][$key2]['eTool'] = $eTool;

					if($cultivation['planting_type'] !== NULL and in_array($cultivation['planting_type'], Cultivation::model()->getPropertyEnum('seedling')) === FALSE) {
						$errors[] = 'seedlingInvalid';
					}

					$errors[] = \main\CsvLib::checkDateField($cultivation['sowing_date'], 'sowingDateFormat');
					$errors[] = \main\CsvLib::checkDateField($cultivation['planting_date'], 'plantingDateFormat');
					$errors[] = \main\CsvLib::checkDateField($cultivation['first_harvest_date'], 'firstHarvestDateFormat');
					$errors[] = \main\CsvLib::checkDateField($cultivation['last_harvest_date'], 'lastHarvestDateFormat');

					if(
						$cultivation['first_harvest_date'] === NULL xor
						$cultivation['last_harvest_date'] === NULL
					) {
						$errors[] = 'harvestDateNull';
					} else if(
						$cultivation['first_harvest_date'] !== NULL and
						$cultivation['first_harvest_date'] > $cultivation['last_harvest_date']
					) {
						$errors[] = 'harvestDateConsistency';
					}

					if($cultivation['harvest_unit'] === NULL) {
						$errors[] = 'harvestUnitEmpty';
					} else if(in_array($cultivation['harvest_unit'], Cultivation::model()->getPropertyEnum('mainUnit')) === FALSE) {
						$errorsGlobal['harvestUnit'][] = $cultivation['harvest_unit'];
					}

					if($cultivation['harvest_unit'] === NULL) {
						$errors[] = 'harvestUnitEmpty';
					} else if(in_array($cultivation['harvest_unit'], Cultivation::model()->getPropertyEnum('mainUnit')) === FALSE) {
						$errorsGlobal['harvestUnit'][] = $cultivation['harvest_unit'];
					}

					if(
						$cultivation['varieties'] and
						$cultivation['varieties_unit'] === Cultivation::PERCENT and
						$varietyTotal !== 100
					) {
						$errors[] = 'varietyParts';
					}

					switch($series['use']) {

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

				}

				$import[$key1]['cultivations'][$key2]['ePlant'] = $ePlant;

				$errors = array_filter($errors);
				$errors = array_unique($errors);

				$errorsCount += count($errors);

				$import[$key1]['cultivations'][$key2]['errors'] = $errors;
				$import[$key1]['cultivations'][$key2]['ignore'] = $ignore;

			}

		}

		$errorsGlobal['harvestUnit'] = array_unique($errorsGlobal['harvestUnit']);
		$errorsGlobal['species'] = array_unique($errorsGlobal['species']);
		$errorsGlobal['tools'] = array_unique($errorsGlobal['tools']);
		$errorsGlobal['seasons'] = array_unique($errorsGlobal['seasons']);

		$infoGlobal['varieties'] = array_unique($infoGlobal['varieties']);
		$infoGlobal['speciesPerennial'] = array_unique($infoGlobal['speciesPerennial']);

		return [
			'import' => $import,
			'errorsCount' => $errorsCount + (int)$errorsGlobal['beds'] + (int)$errorsGlobal['tools'] + count($errorsGlobal['harvestUnit']) + count($errorsGlobal['species']) + count($errorsGlobal['seasons']),
			'errorsGlobal' => $errorsGlobal,
			'infoGlobal' => $infoGlobal
		];

	}

}
?>
