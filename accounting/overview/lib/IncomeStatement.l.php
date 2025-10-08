<?php
namespace overview;

/**
 * Traitements relatifs au Compte de Résultat
 */
Class IncomeStatementLib {

	const VIEW_BASIC = 'basic';
	const VIEW_DETAILED = 'detailed';

	public static function getResultOperationsByFinancialYear(\account\FinancialYear $eFinancialYear, bool $isDetailed, \account\FinancialYear $eFinancialYearComparison): array {

		$dateWhere = 'date BETWEEN '.\journal\Operation::model()->format($eFinancialYear['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYear['endDate']);

		if($eFinancialYearComparison->empty()) {

			\journal\Operation::model()->where($dateWhere);

		} else {

			\journal\Operation::model()
				->or(
					fn() => $this->where('date BETWEEN '.\journal\Operation::model()->format($eFinancialYear['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYear['endDate'])),
					fn() => $this->where('date BETWEEN '.\journal\Operation::model()->format($eFinancialYearComparison['startDate']).' AND '.\journal\Operation::model()->format($eFinancialYearComparison['endDate']), if: $eFinancialYearComparison->notEmpty()),
				);
		}

		$cOperations = \journal\Operation::model()
			->select([
				'financialYear',
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)', 'int'),
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%", 1, -1) * IF(type = "debit", amount, -amount))')
			])
			->or(
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%'),
				fn() => $this->whereAccountLabel('LIKE', \account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%'),
			)
			->group(['class', 'financialYear'])
			->sort(['class' => SORT_ASC])
			->getCollection();

		$expenses = [];
		$incomes = [];

		foreach($cOperations as $eOperation) {

			$isExpense = ((int)mb_substr($eOperation['class'], 0, 1) === \account\AccountSetting::CHARGE_ACCOUNT_CLASS);
			$isIncome = ((int)mb_substr($eOperation['class'], 0, 1) === \account\AccountSetting::PRODUCT_ACCOUNT_CLASS);

			if($isExpense) {
				self::affectResultData($expenses, $eFinancialYear, $eOperation);
			}
			if($isIncome) {
				self::affectResultData($incomes, $eFinancialYear, $eOperation);
			}

		}

		$resultData = [
			'expenses' => ['operating' => [], 'financial' => [], 'exceptional' => []],
			'incomes' => ['operating' => [], 'financial' => [], 'exceptional' => []],
		];

		$currentSum = [
			'comparison' => 0,
			'current' => 0,
			'class' => NULL,
			'isSummary' => TRUE,
		];
		$currentSumClass = NULL;

		foreach(array_merge($expenses, $incomes) as $data) {

			if($currentSumClass === NULL) {
				$currentSum['class'] = (int)mb_substr((string)$data['class'], 0, 2);
			}

			if($isDetailed and $currentSumClass !== NULL and $currentSumClass !== (int)mb_substr((string)$data['class'], 0, 2)) {

				list($type, $category) = self::getTypeAndCategory($currentSumClass);
				$resultData[$type][$category][] = $currentSum;

				$currentSum = [
					'comparison' => 0,
					'current' => 0,
					'class' => (int)mb_substr((string)$data['class'], 0, 2),
					'isSummary' => TRUE,
				];

			}

			list($type, $category) = self::getTypeAndCategory($data['class']);
			$resultData[$type][$category][] = $data;
			$currentSum['comparison'] += $data['comparison'];
			$currentSum['current'] += $data['current'];

			$currentSumClass = (int)mb_substr((string)$data['class'], 0, 2);

		}

		if($isDetailed) {
			list($type, $category) = self::getTypeAndCategory($currentSumClass);
			$resultData[$type][$category][] = $currentSum;
		}

		return $resultData;

	}

	private static function getTypeAndCategory(int $class): array {

		switch((int)mb_substr((string)$class, 0, 2)) {

			case \account\AccountSetting::CHARGE_FINANCIAL_ACCOUNT_CLASS:
				return ['expenses', 'financial'];

			case \account\AccountSetting::CHARGE_EXCEPTIONAL_ACCOUNT_CLASS:
				return ['expenses', 'exceptional'];

			case \account\AccountSetting::PRODUCT_FINANCIAL_ACCOUNT_CLASS:
				return ['incomes', 'financial'];

			case \account\AccountSetting::PRODUCT_EXCEPTIONAL_ACCOUNT_CLASS:
				return ['incomes', 'exceptional'];

			default:
				if((int)mb_substr((string)$class, 0, 1) === \account\AccountSetting::CHARGE_ACCOUNT_CLASS) {
					return ['expenses', 'operating'];
				} else {
					return ['incomes', 'operating'];
				}

		}


	}

	private static function affectResultData(array &$array, \account\FinancialYear $eFinancialYear, \journal\Operation $eOperation) {
		if(isset($array[$eOperation['class']]) === FALSE) {
			$array[$eOperation['class']] = ['comparison' => 0, 'current' => 0, 'class' => $eOperation['class']];
		}
		if($eOperation['financialYear']->is($eFinancialYear)) {
			$array[$eOperation['class']]['current'] = $eOperation['amount'];
		} else {
			$array[$eOperation['class']]['comparison'] = $eOperation['amount'];
		}

	}

	public static function computeResult(\account\FinancialYear $eFinancialYear): float {

			$operations = \journal\Operation::model()
				->select([
					'class' => new \Sql('SUBSTRING(accountLabel, 1, 1)', 'int'),
					'amount' => new \Sql('SUM(IF(accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%", 1, -1) * IF(type = "debit", amount, -amount))')
				])
				->whereDate('>=', $eFinancialYear['startDate'])
				->whereDate('<=', $eFinancialYear['endDate'])
				->or(
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"')),
					fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"')),
				)
				->group('class')
				->getCollection(NULL, NULL, 'class');

			return round(
				$operations[\account\AccountSetting::PRODUCT_ACCOUNT_CLASS]['amount'] ?? 0
				- $operations[\account\AccountSetting::CHARGE_ACCOUNT_CLASS]['amount'] ?? 0,
				2);
	}
}
