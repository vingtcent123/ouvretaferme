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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('farm.check', function(\farm\Farm $eFarm): bool {
				return $eFarm->canWrite();
			})
			->setCallback('season.check', function(int $season): bool {
				$this->expects(['farm']);
				return $this['farm']->checkSeason($season);
			})
			->setCallback('plant.check', function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm')
						->get($ePlant) and
					$ePlant->canRead()
				);

			})
			->setCallback('from.check', function(mixed $from): bool {

				$eReportFrom = \analyze\ReportLib::getById($from);

				if($eReportFrom->notEmpty()) {
					$eReportFrom->validate('canWrite');
				}

				$this['from'] = $eReportFrom;

				return TRUE;

			})
			->setCallback('cultivations.check', function(mixed $ids): bool {

				$this->expects(['farm', 'workingTimeAdditional']);

				\series\Cultivation::model()->select([
					'firstTaskWeek' => \series\CultivationLib::delegateFirstTaskWeek($this['farm'])
				]);
				$cCultivationSeries = \series\CultivationLib::getByIds($ids, sort: ['startWeek' => SORT_ASC])
					->filter(fn($e) => $e['farm']['id'] === $this['farm']['id']);

				if($cCultivationSeries->empty()) {
					return FALSE;
				}

				$fw = new \FailWatch();

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

				if($this['workingTimeAdditional'] > 0) {

					$totalTime = $cCultivationAnalyze->sum('workingTime');
					$timeFactor = ($totalTime + $this['workingTimeAdditional']) / $totalTime;

					foreach($cCultivationAnalyze as $eCultivationAnalyze) {
						$eCultivationAnalyze['workingTime'] = $eCultivationAnalyze['workingTime'] * $timeFactor;
					}

				}

				if(
					$fw->ok() and
					POST('costsUser', 'bool')
				) {

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

			})
			->setCallback('products.check', function(mixed $ids): bool {

				$this->expects(['farm']);

				$cProductSeries = \selling\ProductLib::getByIds($ids)->filter(fn($e) => $e['farm']['id'] === $this['farm']['id']);

				if($cProductSeries->empty()) {
					$this['cProduct'] = new \Collection();
					return TRUE;
				}

				$cProductAnalyze = new \Collection();

				foreach($cProductSeries as $eProductSeries) {

					$fqn = $eProductSeries['unit']->empty() ? NULL : $eProductSeries['unit']['fqn'];

					$eProductAnalyze = new Product([
						'product' => $eProductSeries['id'],
						'farm' => $this['farm'],
						'unit' => match($fqn) {
							'gram', 'gram-100', 'gram-250', 'gram-500', 'kg' => Product::KG,
							'bunch' => Product::BUNCH,
							default => Product::UNIT
						}
					]);
					$eProductAnalyze->buildIndex(['turnover', 'quantity'], $_POST, $eProductSeries['id']);

					if($fqn === 'gram-100') {
						$eProductAnalyze['quantity'] /= 10;
					}

					if($fqn === 'gram-250') {
						$eProductAnalyze['quantity'] /= 4;
					}

					if($fqn === 'gram-500') {
						$eProductAnalyze['quantity'] /= 2;
					}

					$cProductAnalyze[] = $eProductAnalyze;

				}

				$this['cProduct'] = $cProductAnalyze;

				return TRUE;

			})
			->setCallback('testArea.set', function(?int $value) {
				if($this['area'] === 0) {
					$value = NULL;
				}
				$this['testArea'] = ($value === 0) ? NULL : $value;
			})
			->setCallback('testAreaOperator.set', function(?string $operator) {

				if(empty($this['testArea'])) {
					$this['testAreaOperator'] = NULL;
				} else {
					$this['testAreaOperator'] = $operator ?? Report::ABSOLUTE;
				}

			})
			->setCallback('testWorkingTime.set', function(?int $value) {
				if($this['workingTime'] === 0.0) {
					$value = NULL;
				}
				$this['testWorkingTime'] = ($value === 0.0) ? NULL : $value;
			})
			->setCallback('testWorkingTimeOperator.set', function(?string $operator) {

				if(empty($this['testWorkingTime'])) {
					$this['testWorkingTimeOperator'] = NULL;
				} else {
					$this['testWorkingTimeOperator'] = $operator ?? Report::ABSOLUTE;
				}

			})
			->setCallback('testCosts.set', function(?int $value) {
				if($this['costs'] === 0) {
					$value = NULL;
				}
				$this['testCosts'] = ($value === 0) ? NULL : $value;
			})
			->setCallback('testCostsOperator.set', function(?string $operator) {

				if(empty($this['testCosts'])) {
					$this['testCostsOperator'] = NULL;
				} else {
					$this['testCostsOperator'] = $operator ?? Report::ABSOLUTE;
				}

			})
			->setCallback('testTurnover.set', function(?int $value) {
				if($this['turnover'] === 0) {
					$value = NULL;
				}
				$this['testTurnover'] = ($value === 0) ? NULL : $value;
			})
			->setCallback('testTurnoverOperator.set', function(?string $operator) {

				if(empty($this['testTurnover'])) {
					$this['testTurnoverOperator'] = NULL;
				} else {
					$this['testTurnoverOperator'] = $operator ?? Report::ABSOLUTE;
				}

			});
		
		parent::build($properties, $input, $p);

	}

}
?>