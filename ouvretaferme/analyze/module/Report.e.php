<?php
namespace analyze;

class Report extends ReportElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette'],
			'plant' => ['name', 'vignette', 'fqn'],
			'grossMargin' => new \Sql('CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)', 'int'),
			'turnoverByArea' => new \Sql('IF(area > 0, turnover / area, NULL)', 'float'),
			'grossMarginByArea' => new \Sql('IF(area > 0, (CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)) / area, NULL)', 'float'),
			'grossMarginByWorkingTime' => new \Sql('IF(workingTime > 0, (CAST(turnover AS SIGNED) - CAST(costs AS SIGNED)) / workingTime, NULL)', 'float'),
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canAnalyze();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {
				return $eFarm->canWrite();
			},

			'season.check' => function(int $season): bool {
				$this->expects(['farm']);
				return $this['farm']->checkSeason($season);
			},

			'plant.check' => function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm')
						->get($ePlant) and
					$ePlant->canRead()
				);

			},

			'from.check' => function(mixed $from): bool {

				$eReportFrom = \analyze\ReportLib::getById($from);

				if($eReportFrom->notEmpty()) {
					$eReportFrom->validate('canWrite');
				}

				$this['from'] = $eReportFrom;

				return TRUE;

			},

			'cultivations.check' => function(mixed $ids): bool {

				$this->expects(['farm']);

				\series\Cultivation::model()->select([
					'firstTaskWeek' => \series\CultivationLib::delegateFirstTaskWeek($this['farm'])
				]);
				$cCultivationSeries = \series\CultivationLib::getByIds($ids, sort: ['startWeek' => SORT_ASC])
					->filter(fn($e) => $e['farm']['id'] === $this['farm']['id']);

				if($cCultivationSeries->empty()) {
					return FALSE;
				}

				$cCultivationAnalyze = new \Collection();

				$properties = POST('costsUser', 'bool') ? ['area', 'workingTime'] : ['area', 'workingTime', 'costs'];

				foreach($cCultivationSeries as $eCultivationSeries) {

					$eCultivationAnalyze = new Cultivation([
						'farm' => $this['farm'],
						'cultivation' => $eCultivationSeries['id'],
						'series' => $eCultivationSeries['series']['id'],
						'harvestedByUnit' => $eCultivationSeries['harvestedByUnit'],
						'firstTaskWeek' => $eCultivationSeries['firstTaskWeek'],
						'harvestWeeks' => $eCultivationSeries['harvestWeeks'],
						'harvestWeeksExpected' => $eCultivationSeries['harvestWeeksExpected'],
					]);
					$eCultivationAnalyze->buildIndex($properties, $_POST, $eCultivationSeries['id']);

					$cCultivationAnalyze[] = $eCultivationAnalyze;

				}

				if(POST('costsUser', 'bool')) {

					$costs = POST('costsTotal', 'int');
					if($costs < 0) {
						$costs = 0;
					}

					$area = $cCultivationAnalyze->sum('area');

					if($area > 0) {
						$cCultivationAnalyze->setColumn('costs', fn($eCultivationAnalyze) => (int)(($eCultivationAnalyze['area'] / $area) * $costs));
					} else {
						$cCultivationAnalyze->setColumn('costs', (int)($costs / $cCultivationAnalyze->count()));
					}

					// Gestion de l'arrondi
					$missing = $costs - $cCultivationAnalyze->sum('costs');
					for($i = 0; $i < $missing; $i++) {
						$cCultivationAnalyze->offsetGet($i)['costs']++;
					}

				}

				$this['cCultivation'] = $cCultivationAnalyze;

				return TRUE;

			},

			'products.check' => function(mixed $ids): bool {

				$this->expects(['farm']);

				$cProductSeries = \selling\ProductLib::getByIds($ids)->filter(fn($e) => $e['farm']['id'] === $this['farm']['id']);

				if($cProductSeries->empty()) {
					return FALSE;
				}

				$cProductAnalyze = new \Collection();

				foreach($cProductSeries as $eProductSeries) {

					$eProductAnalyze = new Product([
						'product' => $eProductSeries['id'],
						'farm' => $this['farm'],
						'unit' => match($eProductSeries['unit']) {
							\selling\Product::GRAM, \selling\Product::GRAM_100, \selling\Product::GRAM_250, \selling\Product::GRAM_500, \selling\Product::KG => Product::KG,
							\selling\Product::UNIT, \selling\Product::PLANT => Product::UNIT,
							\selling\Product::BUNCH => Product::BUNCH
						}
					]);
					$eProductAnalyze->buildIndex(['turnover', 'quantity'], $_POST, $eProductSeries['id']);

					if($eProductSeries['unit'] === \selling\Product::GRAM_100) {
						$eProductAnalyze['quantity'] /= 10;
					}

					if($eProductSeries['unit'] === \selling\Product::GRAM_250) {
						$eProductAnalyze['quantity'] /= 4;
					}

					if($eProductSeries['unit'] === \selling\Product::GRAM_500) {
						$eProductAnalyze['quantity'] /= 2;
					}

					$cProductAnalyze[] = $eProductAnalyze;

				}

				$this['cProduct'] = $cProductAnalyze;

				return TRUE;

			},

			'testArea.set' => function(?int $value) {
				if($this['area'] === 0) {
					$value = NULL;
				}
				$this['testArea'] = ($value === 0) ? NULL : $value;
			},

			'testAreaOperator.set' => function(?string $operator) {

				if(empty($this['testArea'])) {
					$this['testAreaOperator'] = NULL;
				} else {
					$this['testAreaOperator'] = $operator ?? Report::ABSOLUTE;
				}

			},

			'testWorkingTime.set' => function(?int $value) {
				if($this['workingTime'] === 0.0) {
					$value = NULL;
				}
				$this['testWorkingTime'] = ($value === 0.0) ? NULL : $value;
			},

			'testWorkingTimeOperator.set' => function(?string $operator) {

				if(empty($this['testWorkingTime'])) {
					$this['testWorkingTimeOperator'] = NULL;
				} else {
					$this['testWorkingTimeOperator'] = $operator ?? Report::ABSOLUTE;
				}

			},

			'testCosts.set' => function(?int $value) {
				if($this['costs'] === 0) {
					$value = NULL;
				}
				$this['testCosts'] = ($value === 0) ? NULL : $value;
			},

			'testCostsOperator.set' => function(?string $operator) {

				if(empty($this['testCosts'])) {
					$this['testCostsOperator'] = NULL;
				} else {
					$this['testCostsOperator'] = $operator ?? Report::ABSOLUTE;
				}

			},

			'testTurnover.set' => function(?int $value) {
				if($this['turnover'] === 0) {
					$value = NULL;
				}
				$this['testTurnover'] = ($value === 0) ? NULL : $value;
			},

			'testTurnoverOperator.set' => function(?string $operator) {

				if(empty($this['testTurnover'])) {
					$this['testTurnoverOperator'] = NULL;
				} else {
					$this['testTurnoverOperator'] = $operator ?? Report::ABSOLUTE;
				}

			}

		]);

	}

}
?>