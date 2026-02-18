<?php
namespace company;

Class AdminLib {

	public static function countFarms(): array {

		return \farm\Farm::model()
			->select([
				'hasAccounting' => new \Sql('SUM(hasAccounting)'),
				'hasFinancialYears' => new \Sql('SUM(hasFinancialYears)'),
			])
			->get()
			->getArrayCopy();

	}

	public static function loadAccountingData(\Search $search): \Collection {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection() + [
				'cFarmData' => \data\Farm::model()
					->select(['data' => ['id', 'fqn'], 'value'])
					->delegateCollection('farm', index: ['data'])
				])
			->whereHasAccounting(TRUE)
			->getCollection();

		if(empty($search->getSort())) {
			return $cFarm;
		}

		$cData = \data\DataLib::deferred();

		$sort = $search->getSort();
		if(mb_substr($sort, -1) === '-') {
			$sort = mb_substr($search->getSort(), 0, mb_strlen($search->getSort()) - 1);
			$direction = 'desc';
		} else {
			$direction = 'asc';
		}

		$cDataSort = $cData->find(fn($e) => $e['fqn'] === $sort);

		if($cDataSort->notEmpty()) {

			$eDataSort = $cDataSort->first();

			$cFarm->sort(function(\farm\Farm $e1, \farm\Farm $e2) use ($eDataSort, $direction)  {
				if($direction === 'desc') {
					return ($e2['cFarmData'][$eDataSort['id']]['value'] ?? 0) <=> ($e1['cFarmData'][$eDataSort['id']]['value'] ?? 0);
				}
				return ($e1['cFarmData'][$eDataSort['id']]['value'] ?? 0) <=> ($e2['cFarmData'][$eDataSort['id']]['value'] ?? 0);
			});
		}

		return $cFarm;
	}

}
