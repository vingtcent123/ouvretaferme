<?php
namespace series;

class Cultivation extends CultivationElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'series' => [
				'name', 'area', 'areaTarget', 'bedWidth', 'alleyWidth', 'length', 'lengthTarget', 'season', 'cycle', 'use', 'mode', 'plants', 'perennialSeason', 'status'
			],
			'farm' => ['name'],
			'plant' => ['name', 'latinName', 'fqn', 'vignette'],
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

	public function calculateHarvestedNormalized() {

		$this->expects(['mainUnit', 'harvestedByUnit', 'bunchWeight', 'unitWeight' ]);

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
				return \production\CropUi::getYield($this, $property, 'mainUnit', $options);

			default :
				return parent::format($property, $options);

		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$this->expects([
			'series' => ['use']
		]);

		return parent::build($properties, $input, $callbacks + [

			'crop.check' => function(\production\Crop $eCrop): bool {

				return (
					$eCrop->empty() or
					\production\Crop::model()
						->select([
							'id'
						])
						->get($eCrop)
				);

			},

			'harvest.prepare' => function(&$harvest): bool {

				if($harvest === []) {
					$harvest = NULL;
				}

				return (
					$harvest === NULL or
					is_array($harvest)
				);

			},

			'harvest.check' => function(?array &$harvest): bool {

				if($harvest === NULL) {
					return TRUE;
				}

				foreach($harvest as $month) {
					if(\Filter::check('month', $month) === FALSE) {
						return FALSE;
					}
				}

				return TRUE;

			},

			'plant.check' => function(\plant\Plant $ePlant) use ($for): bool {

				if($for === 'update') {

					if($ePlant->empty()) {
						$ePlant->merge($this['plant']);
						return TRUE;
					}

				}

				if(
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm', 'name', 'latinName', 'cycle')
						->get($ePlant) and
					$ePlant->canRead()
				) {
					return TRUE;
				} else {
					throw new \BuildElementError();
				}

			},

			'plant.unused' => function(\plant\Plant $ePlant) use ($for): bool {

				if($for === 'update') {

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

			},

			'variety.check' => function(?array $varieties) {

				$this['cSlice'] = \production\SliceLib::prepare($this, $varieties);

				return TRUE;

			},

			'seedlingSeeds.prepare' => function(?int &$seeds): bool {

				$this->expects(['seedling']);

				if($this['seedling'] !== Cultivation::YOUNG_PLANT) {
					$seeds = NULL;
				} else {
					$seeds = (int)$seeds;
				}

				return TRUE;

			},

			'rowSpacing.check' => function(?int $rowSpacing): bool {

				switch($this['series']['use']) {

					case Series::BED :
						$this['rowSpacing'] = NULL;
						return TRUE;

					case Series::BLOCK :
						return Cultivation::model()->check('rowSpacing', $rowSpacing);

				}

			},

			'rows.check' => function(?int $rows): bool {

				switch($this['series']['use']) {

					case Series::BED :
						return Cultivation::model()->check('rows', $rows);

					case Series::BLOCK :
						$this['rows'] = NULL;
						return TRUE;

				}

			},

			'harvestMonthsExpected.check' => function(?array $months): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::MONTH) {
					return TRUE;
				}

				return ($months === NULL or array_filter($months, fn($month) => \Filter::check('month', $month) === FALSE) === []);

			},

			'harvestMonthsExpected.set' => function(?array $months): bool {

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

			},

			'harvestWeeksExpected.check' => function(?array $weeks): bool {

				$this->expects(['harvestPeriodExpected']);

				if($this['harvestPeriodExpected'] !== Cultivation::WEEK) {
					return TRUE;
				}

				return ($weeks === NULL or array_filter($weeks, fn($week) => \Filter::check('week', $week) === FALSE) === []);

			},

			'harvestWeeksExpected.set' => function(?array $weeks): bool {

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

			},

			'actions.set' => function(?array $actions): bool {

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

			},

		]);

	}

}
?>
