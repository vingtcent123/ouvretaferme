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

			if($cProduct->offsetExists($eFarm['id'])) {
				$eFarm['nProduct'] = $cProduct[$eFarm['id']]['count'];
			} else {
				$eFarm['nProduct'] = 0;
			}

			$eFarm['nBankAccount'] = \bank\BankAccount::model()->count();

			if($eFarm['nBankAccount'] === 0) {

				$eFarm['nCashflow'] = 0;
				$eFarm['nBankImport'] = 0;

				$eFarm['suggestion-'.\preaccounting\Suggestion::VALIDATED] = 0;
				$eFarm['suggestion-'.\preaccounting\Suggestion::REJECTED] = 0;

			} else {

				$eFarm['nCashflow'] = \bank\Cashflow::model()->count();
				$eFarm['nBankImport'] = \bank\Import::model()->count();

				$cSuggestion = \preaccounting\Suggestion::model()
					->select([
						'status', 'count' => new \Sql('COUNT(*)', 'int')
					])
					->group(['status'])
					->getCollection(NULL, NULL, 'status');
				foreach($cSuggestion as $eSuggestion) {
					$eFarm['suggestion-'.$eSuggestion['status']] = $eSuggestion['count'];
				}

			}

			if($eFarm->usesAccounting()) {

				$eFarm['nOperation'] = \journal\Operation::model()->count();

				$eFarm['nAsset'] = \asset\Asset::model()->count();
				$eFarm['nFinancialYear'] = \account\FinancialYear::model()->count();

			} else {

				$eFarm['nOperation'] = 0;
				$eFarm['nAsset'] = 0;
				$eFarm['nFinancialYear'] = 0;

			}
		}

		switch($search->getSort()) {
			case 'nBankAccount':
			case 'nFinancialYear':
			case 'nAsset':
			case 'nCashflow':
			case 'nOperation':
			case 'nProduct':
			case 'suggestion-'.\preaccounting\Suggestion::VALIDATED:
			case 'suggestion-'.\preaccounting\Suggestion::REJECTED:
				$cFarm->sort([$search->getSort() => SORT_ASC]);
				break;

			case 'nFinancialYear-':
			case 'nBankAccount-':
			case 'nAsset-':
			case 'nCashflow-':
			case 'nOperation-':
			case 'nProduct-':
			case 'suggestion-'.\preaccounting\Suggestion::VALIDATED.'-':
			case 'suggestion-'.\preaccounting\Suggestion::REJECTED.'-':
				$cFarm->sort([mb_substr($search->getSort(), 0, -1) => SORT_DESC]);
				break;

		}


	}

}
