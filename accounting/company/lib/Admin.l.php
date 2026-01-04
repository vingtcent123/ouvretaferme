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

	public static function getFarms(int $page): array {

		$number = 100;
		$position = $page * $number;

		\farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasAccounting(TRUE)
			->option('count');

		$cFarm = \farm\Farm::model()->getCollection($position, $number);

		return [$cFarm, \farm\Farm::model()->found()];

	}

	public static function loadAccountingData(\Collection $cFarm): void {

		$cProduct = \selling\Product::model()
			->select([
				'farm',
				'count' => new \Sql('COUNT(*)', 'int')
			])
			->or(
				fn() => $this->whereProAccount('!=', NULL),
				fn() => $this->wherePrivateAccount('!=', NULL),
			)
			->whereFarm('IN', $cFarm->getIds())
			->group(['farm'])
			->getCollection(NULL, NULL, 'farm');

		foreach($cFarm as &$eFarm) {

			CompanyLib::connectDatabase($eFarm);

			$eFarm['nCashflow'] = \bank\Cashflow::model()->count();
			$eFarm['nOperation'] = \journal\Operation::model()->count();
			$eFarm['cSuggestion'] = \preaccounting\Suggestion::model()
				->select([
					'status', 'count' => new \Sql('COUNT(*)', 'int')
				])
				->group(['status'])
				->getCollection(NULL, NULL, 'status');

			if($cProduct->offsetExists($eFarm['id'])) {
				$eFarm['nProduct'] = $cProduct[$eFarm['id']]['count'];
			} else {
				$eFarm['nProduct'] = 0;
			}

		}


	}

}
