<?php
namespace series;

class Cultivation extends CultivationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'series' => SeriesElement::getSelection(),
			'farm' => ['name'],
			'plant' => ['name', 'color', 'fqn', 'vignette', 'seedsSafetyMargin', 'plantsSafetyMargin'],
			'sliceTool' => ['name', 'routineValue'],
			'cSlice' => SliceLib::delegateByCultivation(),
			'sequence' => ['name', 'mode'],
			'harvestedByUnit' => function(Cultivation $e): ?array {

				if($e['harvestedByUnit'] === NULL) {
					return NULL;
				}
				$e->sortUnits('harvestedByUnit');

				return $e['harvestedByUnit'];

			},
			'yieldByUnit' => function(Cultivation $e): ?array {

				if($e['harvestedByUnit'] === NULL) {
					return NULL;
				}

				$yield = $e['harvestedByUnit'];

				if($e['mainUnit'] === Cultivation::KG) {

					$yield[Cultivation::KG] ??= 0;

					if(isset($yield[Cultivation::BUNCH]) and $e['bunchWeight']) {
						$yield[Cultivation::KG] += $yield[Cultivation::BUNCH] * $e['bunchWeight'];
						unset($yield[Cultivation::BUNCH]);
					}

					if(isset($yield[Cultivation::UNIT]) and $e['unitWeight']) {
						$yield[Cultivation::KG] += $yield[Cultivation::UNIT] * $e['unitWeight'];
						unset($yield[Cultivation::UNIT]);
					}

				}

				return $yield;

			},
			'harvestExpected' => function(Cultivation $e): ?float {

				if($e['yieldExpected'] === NULL) {
					return NULL;
				}

				if($e['area'] !== NULL) {
					return round($e['yieldExpected'] * $e['area']);
				} else {
					return NULL;
				}

			},
			'harvestExpectedTarget' => function(Cultivation $e): ?float {

				if($e['yieldExpected'] === NULL) {
					return NULL;
				}

				return $e['series']['areaTarget'] ? round($e['yieldExpected'] * $e['series']['areaTarget']) : NULL;

			},
			'yield' => new \Sql('IF(area IS NOT NULL AND harvested IS NOT NULL, ROUND(harvested / area * 10) / 10, NULL)', 'float'),
		];

	}

	public function getStartWeek(int $year): string {

		$week = sprintf('%02d', ($this['startWeek'] + 100) % 100);

		if($this['startWeek'] < 0) {
			return ($year - 1).'-W'.$week;
		} else if($this['startWeek'] > 100) {
			return ($year - 1).'-W'.$week;
		} else {
			return $year.'-W'.$week;
		}

	}

	public function getYoungPlants(Slice $eSlice = new Slice(),  ?bool &$targeted = NULL, ?int $safetyMargin = NULL, ?string &$error = NULL): ?int {

		$this->expects([
			'series' => ['use', 'alleyWidth', 'bedWidth'],
			'distance',
		]);

		$safetyMarginMultiplier = (1 + ($safetyMargin ?? 0) / 100);

		if($eSlice->empty()) {
			$slicePercent = 100;
			$sliceUnit = Cultivation::PERCENT;
		} else {

			switch($this['sliceUnit']) {

				case Cultivation::PLANT :
					return round($eSlice['partPlant'] * $safetyMarginMultiplier);

				case Cultivation::TRAY :
					$this->expects([
						'sliceTool' => ['routineValue']
					]);
					return round($eSlice['partTray'] * $this['sliceTool']['routineValue']['value'] * $safetyMarginMultiplier);

			}

			$slicePercent = $eSlice['partPercent'];
			$sliceUnit = $this['sliceUnit'];

		}

		if($this['density'] === NULL) {
			$error = 'density';
			return NULL;
		}

		if(
			($this['series']['use'] === Series::BLOCK) or
			($this['series']['use'] === Series::BED and $this['distance'] === Cultivation::DENSITY)
		) {

			if($this['series']['area'] !== NULL) {
				$area = $this['series']['area'];
				$targeted = FALSE;
			} else if($this['series']['areaTarget'] !== NULL) {
				$area = $this['series']['areaTarget'];
				$targeted = TRUE;
			} else {
				$error = 'area';
				return NULL;
			}

			switch($sliceUnit) {

				case Cultivation::PERCENT :
					return round($area * $this['density'] * $slicePercent / 100 * $safetyMarginMultiplier);

				case Cultivation::AREA :
					return round($eSlice['partArea'] * $this['density'] * $safetyMarginMultiplier);

				case Cultivation::LENGTH :
					return round($eSlice['partLength'] * $this['density'] * $safetyMarginMultiplier);

			};

		} else {

			if($this['series']['length'] !== NULL) {
				$length = $this['series']['length'];
				$targeted = FALSE;
			} else if($this['series']['lengthTarget'] !== NULL) {
				$length = $this['series']['lengthTarget'];
				$targeted = TRUE;
			} else {
				$error = 'length';
				return NULL;
			}

			$density = (100 / $this['plantSpacing']) * $this['rows'];

			switch($sliceUnit) {

				case Cultivation::PERCENT :
					return round($length * $density * $slicePercent / 100 * $safetyMarginMultiplier);

				case Cultivation::LENGTH :
					return round($eSlice['partLength'] * $density * $safetyMarginMultiplier);

			}

		}

	}

	public function getSeeds(Slice $eSlice = new Slice(),  ?bool &$targeted = NULL, ?int $safetyMargin = NULL, ?string &$error = NULL): ?int {

		$youngPlants = self::getYoungPlants($eSlice, $targeted, $safetyMargin, $error);

		if($youngPlants !== NULL) {

			switch($this['seedling']) {

				case Cultivation::SOWING :
				case Cultivation::YOUNG_PLANT :
					return $youngPlants * $this['seedlingSeeds'];

				case Cultivation::YOUNG_PLANT_BOUGHT :
					return NULL;

			}

		}

		return NULL;

	}

	public function calculateHarvestedNormalized() {

		$this->expects(['mainUnit', 'harvestedByUnit', 'bunchWeight', 'unitWeight']);

		if($this['harvestedByUnit'] === NULL) {
			$this['harvestedNormalized'] = NULL;
			return;
		}

		if($this['mainUnit'] === Cultivation::KG) {

			$result = $this['harvestedByUnit'][Cultivation::KG] ?? 0;

			if(isset($this['harvestedByUnit'][Cultivation::BUNCH]) and $this['bunchWeight']) {
				$result += $this['harvestedByUnit'][Cultivation::BUNCH] * $this['bunchWeight'];
			}

			if(isset($this['harvestedByUnit'][Cultivation::UNIT]) and $this['unitWeight']) {
				$result += $this['harvestedByUnit'][Cultivation::UNIT] * $this['unitWeight'];
			}

			$this['harvestedNormalized'] = ($result > 0) ? $result : NULL;
		} else {
			$this['harvestedNormalized'] = $this['harvested'];
		}

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canWrite();

	}

	public function sortUnits(string $property): void {

		$this->expects(['harvestedByUnit']);

		if($this['harvestedByUnit'] === NULL) {
			throw new \Exception('harvestedByUnit must not be NULL');
		}

		uksort($this['harvestedByUnit'], function($key1, $key2) {

			if($key1 === $this['mainUnit']) {
				return -1;
			}

			if($key2 === $this['mainUnit']) {
				return 1;
			}

			return strcmp($key1, $key2);

		});

	}

	public function getHarvestBounds(): array {

		$this->expects(['harvestWeeks', 'harvestWeeksExpected']);

		$min = NULL;
		$max = NULL;

		if($this['harvestWeeksExpected'] !== NULL) {

			$min = min($this['harvestWeeksExpected']);
			$max = max($this['harvestWeeksExpected']);

		}

		if($this['harvestWeeks'] !== NULL) {

			$min = ($min === NULL) ? min($this['harvestWeeks']) : min($min, min($this['harvestWeeks']));
			$max = ($max === NULL) ? max($this['harvestWeeks']) : max($max, max($this['harvestWeeks']));

		}

		return [$min, $max];

	}

	public static function validateBatch(\Collection $cCultivation, \farm\Farm $eFarm): \plant\Plant {

		$eFarm->expects(['id']);

		if($cCultivation->empty()) {
			return new \plant\Plant();
		}

		$season = $cCultivation->first()['season'];
		$ePlant = $cCultivation->first()['plant'];

		foreach($cCultivation as $eCultivation) {

			$eCultivation->validate('canWrite');

			if($eCultivation['farm']['id'] !== $eFarm['id']) {
				throw new \NotExpectedAction('Wrong farm');
			}

			if($eCultivation['season'] !== $season) {
				throw new \NotExpectedAction('Different seasons');
			}

			if(
				$ePlant->notEmpty() and
				$eCultivation['plant']['id'] !== $ePlant['id']
			) {
				$ePlant = new \plant\Plant();
			}

		}

		return $ePlant;

	}

	public function format(string $property, array $options = []): ?string {

		switch($property) {

			case 'yield' :
			case 'yieldExpected' :
			case 'harvested' :
			case 'harvestExpected' :
			case 'harvestExpectedTarget' :
				return \sequence\CropUi::getYield($this, $property, 'mainUnit', $options);

			default :
				return parent::format($property, $options);

		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$this->expects([
			'series' => ['use']
		]);
		
		$p
			->setCallback('crop.check', function(\sequence\Crop $eCrop): bool {

				return (
					$eCrop->empty() or
					\sequence\Crop::model()
						->select([
							'id'
						])
						->get($eCrop)
				);

			})
			->setCallback('harvest.prepare', function(&$harvest): bool {

				if($harvest === []) {
					$harvest = NULL;
				}

				return (
					$harvest === NULL or
					is_array($harvest)
				);

			})
			->setCallback('harvest.check', function(?array &$harvest): bool {

				if($harvest === NULL) {
					return TRUE;
				}

				foreach($harvest as $month) {
					if(\Filter::check('month', $month) === FALSE) {
						return FALSE;
					}
				}

				return TRUE;

			})

			->setCallback('plant.check', function(\plant\Plant $ePlant) use($p): bool {

				if($p->for === 'update') {

					if($ePlant->empty()) {
						$ePlant->merge($this['plant']);
						return TRUE;
					}

				}

				if(
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm', 'name', 'cycle')
						->get($ePlant) and
					$ePlant->canRead()
				) {
					return TRUE;
				} else {
					throw new \BuildElementError();
				}

			})
			->setCallback('plant.unused', function(\plant\Plant $ePlant) use($p): bool {

				if($p->for === 'update') {

					if($ePlant['id'] === $this['plant']['id']) {
						return TRUE;
					}

					$this->expects('series');

					return Cultivation::model()
						->whereSeries($this['series'])
						->wherePlant($ePlant)
						->whereId('!=', $this['plant'])
						->exists() === FALSE;

				} else {
					return TRUE;
				}

			})
			->setCallback('sliceTool.check', function(\farm\Tool $eTool) use($p) {

				$p->expectsBuilt('sliceUnit');

				if($this['sliceUnit'] === Cultivation::TRAY) {

					return \farm\Tool::model()
						->whereFarm($this['farm'])
						->whereRoutineName('tray')
						->exists($eTool);

				} else {
					return $eTool->empty();
				}

			})
			->setCallback('variety.check', function(?array $varieties, \Properties $p, string $wrapper) {

				$p->expectsBuilt(['sliceUnit', 'sliceTray']);

				$this['cSlice'] = \sequence\SliceLib::prepare($this, $varieties, $wrapper);

				if($this['cSlice']->empty()) {
					$this['sliceUnit'] = Cultivation::PERCENT;
					$this['sliceTool'] = new \farm\Tool();
				}

				return TRUE;

			})
			->setCallback('seedlingSeeds.prepare', function(?int &$seeds): bool {

				$this->expects(['seedling']);

				if(in_array($this['seedling'], [Cultivation::SOWING, Cultivation::YOUNG_PLANT]) === FALSE) {
					$seeds = NULL;
				} else {
					$seeds = (int)$seeds;
				}

				return TRUE;

			})
			->setCallback('rowSpacing.check', function(?int $rowSpacing): bool {

				switch($this['series']['use']) {

					case Series::BED :
						$this['rowSpacing'] = NULL;
						return TRUE;

					case Series::BLOCK :
						return Cultivation::model()->check('rowSpacing', $rowSpacing);

				}

			})
			->setCallback('rows.check', function(?int $rows): bool {

				switch($this['series']['use']) {

					case Series::BED :
						return Cultivation::model()->check('rows', $rows);

					case Series::BLOCK :
						$this['rows'] = NULL;
						return TRUE;

				}

			})
			->setCallback('harvestMonthsExpected.check', function(?array $months): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::MONTH) {
					return TRUE;
				}

				return ($months === NULL or array_filter($months, fn($month) => \Filter::check('month', $month) === FALSE) === []);

			})
			->setCallback('harvestMonthsExpected.set', function(?array $months): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::MONTH) {
					return TRUE;
				}

				$this['harvestMonthsExpected'] = $months;

				if($months === NULL) {
					$this['harvestWeeksExpected'] = NULL;
				} else {
					$this['harvestWeeksExpected'] = \util\DateLib::convertMonthsToWeeks($months);
				}

				return TRUE;

			})
			->setCallback('harvestWeeksExpected.check', function(?array $weeks): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::WEEK) {
					return TRUE;
				}

				return ($weeks === NULL or array_filter($weeks, fn($week) => \Filter::check('week', $week) === FALSE) === []);

			})
			->setCallback('harvestWeeksExpected.set', function(?array $weeks): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::WEEK) {
					return TRUE;
				}

				$this['harvestWeeksExpected'] = $weeks;

				if($weeks === NULL) {
					$this['harvestMonthsExpected'] = NULL;
				} else {
					$this['harvestMonthsExpected'] = \util\DateLib::convertWeeksToMonths($weeks);
				}

				return TRUE;

			})
			->setCallback('actions.set', function(?array $actions): bool {

				$this->expects(['seedling']);

				$this['actions'] = [];

				switch($this['seedling']) {

					case Cultivation::SOWING :
						if(\Filter::check('week', $actions[ACTION_SEMIS_DIRECT] ?? NULL)) {
							$this['actions'][ACTION_SEMIS_DIRECT] = $actions[ACTION_SEMIS_DIRECT];
						}
						break;

					case Cultivation::YOUNG_PLANT :
						if(\Filter::check('week', $actions[ACTION_SEMIS_PEPINIERE] ?? NULL)) {
							$this['actions'][ACTION_SEMIS_PEPINIERE] = $actions[ACTION_SEMIS_PEPINIERE];
						}
						if(\Filter::check('week', $actions[ACTION_PLANTATION] ?? NULL)) {
							$this['actions'][ACTION_PLANTATION] = $actions[ACTION_PLANTATION];
						}
						break;

					case Cultivation::YOUNG_PLANT_BOUGHT :
						if(\Filter::check('week', $actions[ACTION_PLANTATION] ?? NULL)) {
							$this['actions'][ACTION_PLANTATION] = $actions[ACTION_PLANTATION];
						}
						break;

				}

				return TRUE;

			});

		parent::build($properties, $input, $p);

	}

}
?>
