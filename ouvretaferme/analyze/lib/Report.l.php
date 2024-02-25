<?php
namespace analyze;

class ReportLib extends ReportCrud {

	public static function getPropertiesCreate(): array {
		return ['farm', 'plant', 'name', 'description', 'cultivations', 'products', 'firstSaleAt', 'lastSaleAt', 'from'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'description'];
	}

	public static function getByFarm(\farm\Farm $eFarm, int $season, \Search $search = new \Search()): \Collection {

		if($search->get('name')) {
			Report::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		$search->validateSort(['name', 'area', 'workingTime', 'turnover', 'turnoverByArea', 'costs', 'grossMargin', 'grossMarginByArea', 'grossMarginByWorkingTime']);

		$sort = $search->buildSort();

		$cReport = Report::model()
			->select(Report::getSelection())
			->select([
				'cCultivation' => Cultivation::model()
					->select(Cultivation::getSelection())
					->sort('id')
					->delegateCollection('report')
			])
			->whereFarm($eFarm)
			->whereSeason($season)
			->sort($sort)
			->getCollection();

		if(array_keys($sort) === ['name']) {

			$factor = ($sort['name'] === SORT_ASC ? 1 : -1);
			$cReport->sort(fn(Report $e1, Report $e2) => \L::getCollator()->compare($e1['plant']['name'], $e2['plant']['name']) * $factor);

		}

		return $cReport;

	}

	public static function getSiblings(Report $e): \Collection {

		$e->expects(['farm', 'name', 'season']);

		return Report::model()
			->select(Report::getSelection())
			->select([
				'cCultivation' => Cultivation::model()
					->select(Cultivation::getSelection())
					->delegateCollection('report')
			])
			->whereFarm($e['farm'])
			->wherePlant($e['plant'])
			->whereName($e['name'])
			->sort([
				'season' => SORT_DESC,
				'id' => SORT_ASC
			])
			->getCollection();

	}

	public static function getTest(Report $e): Report {

		if($e['testArea'] === NULL and $e['testWorkingTime'] === NULL and $e['testCosts'] === NULL and $e['testTurnover'] === NULL) {
			return new Report();
		}

		$eTest = clone $e;

		// Gestion de la surface
		if($e['testAreaOperator'] !== NULL) {

			$growth = match($e['testAreaOperator']) {
				Report::RELATIVE => 1 + ($e['testArea'] / 100),
				Report::ABSOLUTE => ($e['testArea'] + $e['area']) / $e['area']
			};

			$eTest['area'] = max(0, (int)($eTest['area'] * $growth));

			if($eTest['area'] === 0) {
				$eTest['workingTime'] = 0;
				$eTest['costs'] = 0;
				$eTest['turnover'] = 0;
			} else {
				$eTest['workingTime'] = ($eTest['workingTime'] * $growth);
				$eTest['costs'] = (int)($eTest['costs'] * $growth);
				$eTest['turnover'] = (int)($eTest['turnover'] * $growth);
			}

		}

		// Gestion du temps de travail
		if($e['testWorkingTime'] !== NULL) {

			$growth = match($e['testWorkingTimeOperator']) {
				Report::RELATIVE => 1 + ($e['testWorkingTime'] / 100),
				Report::ABSOLUTE => ($e['testWorkingTime'] + $e['workingTime']) / $e['workingTime']
			};

			$eTest['workingTime'] = max(0, ($eTest['workingTime'] * $growth));

		}

		// Gestion des coûts
		if($e['testCosts'] !== NULL) {

			$growth = match($e['testCostsOperator']) {
				Report::RELATIVE => 1 + ($e['testCosts'] / 100),
				Report::ABSOLUTE => ($e['testCosts'] + $e['costs']) / $e['costs']
			};

			$eTest['costs'] = max(0, ($eTest['costs'] * $growth));

		}

		// Gestion des ventes
		if($e['testTurnover'] !== NULL) {

			$growth = match($e['testTurnoverOperator']) {
				Report::RELATIVE => 1 + ($e['testTurnover'] / 100),
				Report::ABSOLUTE => ($e['testTurnover'] + $e['turnover']) / $e['turnover']
			};

			$eTest['turnover'] = max(0, ($eTest['turnover'] * $growth));

		}

		// Champs calculés
		$eTest['grossMargin'] = ($eTest['turnover'] - $eTest['costs']);
		$eTest['turnoverByArea'] = ($eTest['area'] > 0) ? ($eTest['turnover'] / $eTest['area']) : NULL;
		$eTest['grossMarginByArea'] = ($eTest['area'] > 0) ? ($eTest['grossMargin'] / $eTest['area']) : NULL;
		$eTest['grossMarginByWorkingTime'] = ($eTest['workingTime'] > 0) ? ($eTest['grossMargin'] / $eTest['workingTime']) : NULL;

		return $eTest;

	}

	public static function create(Report $e): void {

		$e->expects(['cCultivation', 'cProduct', 'from']);

		[
			'cCultivation' => $cCultivation,
			'cProduct' => $cProduct,
		] = $e;

		// Chiffre d'affaire total par unité
		$turnoverByUnit = $cProduct->reduce(function($eProduct, $v) {
			$v[$eProduct['unit']] += $eProduct['turnover'];
			return $v;
		}, [
			Product::UNIT => 0,
			Product::BUNCH => 0,
			Product::KG => 0
		]);

		// Récoltes totales par unité
		$harvestedByUnit = $cCultivation->reduce(function($eCultivation, $v) {

			if($eCultivation['harvestedByUnit'] !== NULL) {

				foreach($eCultivation['harvestedByUnit'] as $unit => $harvested) {
					$v[$unit] += $harvested;
				}

			}

			return $v;

		}, [
			Product::UNIT => 0,
			Product::BUNCH => 0,
			Product::KG => 0
		]);

		foreach($cCultivation as $eCultivation) {

			$eCultivation['turnoverByUnit'] = [
				Product::UNIT => 0,
				Product::BUNCH => 0,
				Product::KG => 0
			];

			foreach($turnoverByUnit as $unit => $turnover) {

				if($turnover > 0) {

					// Calcul du chiffre d'affaires par unité pour la culture
					if($harvestedByUnit[$unit] > 0) {
						if(isset($eCultivation['harvestedByUnit'][$unit])) {
							$eCultivation['turnoverByUnit'][$unit] = (int)(($eCultivation['harvestedByUnit'][$unit] / $harvestedByUnit[$unit]) * $turnover);
						}
					} else {
						$eCultivation['turnoverByUnit'][$unit] = (int)($turnover / $cCultivation->count());
					}

				}

			}

		}

		// Gestion de l'arrondi
		foreach($turnoverByUnit as $unit => $turnover) {

			$missing = $turnover - array_sum(array_column($cCultivation->getColumn('turnoverByUnit'), $unit));

			for($i = 0; $i < $missing; $i++) {
				$cCultivation->offsetGet($i)['turnoverByUnit'][$unit]++;
			}

		}

		$cCultivation->map(function($eCultivation) {
			$eCultivation['turnover'] = array_sum($eCultivation['turnoverByUnit']);
			$eCultivation['turnoverByUnit'] = array_filter($eCultivation['turnoverByUnit'], fn($turnover) => ($turnover > 0));
		});

		$e->add([
			'turnover' => $cCultivation->sum('turnover'),
			'area' => $cCultivation->sum('area'),
			'workingTime' => $cCultivation->sum('workingTime'),
			'costs' => $cCultivation->sum('costs'),
		]);

		Report::model()->beginTransaction();

		if($e['from']->notEmpty()) {
			self::delete($e['from']);
		}

		try {

			Report::model()->insert($e);

			$cCultivation->setColumn('report', $e);
			$cProduct->setColumn('report', $e);

			Cultivation::model()->insert($cCultivation);
			Product::model()->insert($cProduct);

			Report::model()->commit();

		} catch(\DuplicateException) {

			Report::model()->rollBack();

			Report::fail('name.duplicate');

		}

	}

	public static function delete(Report $e): void {

		$e->expects(['id']);

		Report::model()->beginTransaction();

		Product::model()
			->whereReport($e)
			->delete();

		Cultivation::model()
			->whereReport($e)
			->delete();

		Report::model()->delete($e);

		Report::model()->commit();

	}

	public static function recalculate(Report $e) {

		$newValues = Cultivation::model()
			->select([
				'area' => new \Sql('SUM(area)', 'int'),
				'workingTime' => new \Sql('SUM(workingTime)', 'float'),
				'costs' => new \Sql('SUM(costs)', 'int'),
				'turnover' => new \Sql('SUM(turnover)', 'int')
			])
			->whereReport($e)
			->get()
			->getArrayCopy();

		Report::model()->update($e, $newValues);

	}

}
?>
