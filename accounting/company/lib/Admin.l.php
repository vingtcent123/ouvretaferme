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

	public static function loadAccountingData(\Collection $cFarm, \Search $search): void {

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
			$cSuggestion = \preaccounting\Suggestion::model()
				->select([
					'status', 'count' => new \Sql('COUNT(*)', 'int')
				])
				->group(['status'])
				->getCollection(NULL, NULL, 'status');
			foreach($cSuggestion as $eSuggestion) {
				$eFarm['suggestion-'.$eSuggestion['status']] = $eSuggestion['count'];
			}
			if($cProduct->offsetExists($eFarm['id'])) {
				$eFarm['nProduct'] = $cProduct[$eFarm['id']]['count'];
			} else {
				$eFarm['nProduct'] = 0;
			}

		}

		switch($search->getSort()) {
			case 'cashflow':
				$cFarm->sort(['nCashflow' => SORT_ASC]);
				break;
			case 'cashflow-':
				$cFarm->sort(['nCashflow' => SORT_DESC]);
				break;

			case 'operation':
				$cFarm->sort(['nOperation' => SORT_ASC]);
				break;
			case 'operation-':
				$cFarm->sort(['nOperation' => SORT_DESC]);
				break;

			case 'product':
				$cFarm->sort(['nProduct' => SORT_ASC]);
				break;
			case 'product-':
				$cFarm->sort(['nProduct' => SORT_DESC]);
				break;

			case 'reconciliationValidated':
				$cFarm->sort(['suggestion-'.\preaccounting\Suggestion::VALIDATED => SORT_ASC]);
				break;
			case 'reconciliationValidated-':
				$cFarm->sort(['suggestion-'.\preaccounting\Suggestion::VALIDATED => SORT_DESC]);
				break;

			case 'reconciliationRejected':
				$cFarm->sort(['suggestion-'.\preaccounting\Suggestion::REJECTED => SORT_ASC]);
				break;
			case 'reconciliationRejected-':
				$cFarm->sort(['suggestion-'.\preaccounting\Suggestion::REJECTED => SORT_DESC]);
				break;

		}


	}

}
