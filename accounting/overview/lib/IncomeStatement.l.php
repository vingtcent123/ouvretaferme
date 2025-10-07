<?php
namespace overview;

/**
 * Traitements relatifs au Compte de Résultat
 */
Class IncomeStatementLib {

	public static function getResultOperationsByFinancialYear(\account\FinancialYear $eFinancialYear, bool $hasSummary): array {

		$eFinancialYearPrevious = \account\FinancialYearLib::getPreviousFinancialYear($eFinancialYear);
		$startDate = $eFinancialYearPrevious['startDate'] ?? $eFinancialYear['startDate'];
		$endDate = $eFinancialYear['endDate'];

		$cOperations = \journal\Operation::model()
			->select([
				'financialYear',
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)', 'int'),
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%", 1, -1) * IF(type = "debit", amount, -amount))')
			])
			->where('date BETWEEN '.\journal\Operation::model()->format($startDate).' AND '.\journal\Operation::model()->format($endDate))
			->or(
				fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"')),
				fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"')),
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
			'previous' => 0,
			'current' => 0,
			'class' => NULL,
			'isSummary' => TRUE,
		];
		$currentSumClass = NULL;

		foreach(array_merge($expenses, $incomes) as $data) {

			if($currentSumClass === NULL) {
				$currentSum['class'] = (int)mb_substr((string)$data['class'], 0, 2);
			}

			if($hasSummary and $currentSumClass !== NULL and $currentSumClass !== (int)mb_substr((string)$data['class'], 0, 2)) {

				list($type, $category) = self::getTypeAndCategory($currentSumClass);
				$resultData[$type][$category][] = $currentSum;

				$currentSum = [
					'previous' => 0,
					'current' => 0,
					'class' => (int)mb_substr((string)$data['class'], 0, 2),
					'isSummary' => TRUE,
				];

			}

			list($type, $category) = self::getTypeAndCategory($data['class']);
			$resultData[$type][$category][] = $data;
			$currentSum['previous'] += $data['previous'];
			$currentSum['current'] += $data['current'];

			$currentSumClass = (int)mb_substr((string)$data['class'], 0, 2);

		}

		if($hasSummary) {
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
			$array[$eOperation['class']] = ['previous' => 0, 'current' => 0, 'class' => $eOperation['class']];
		}
		if($eOperation['financialYear']->is($eFinancialYear)) {
			$array[$eOperation['class']]['current'] = $eOperation['amount'];
		} else {
			$array[$eOperation['class']]['previous'] = $eOperation['amount'];
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
				$operations[\account\AccountSetting::PRODUCT_ACCOUNT_CLASS]['amount']
				- $operations[\account\AccountSetting::CHARGE_ACCOUNT_CLASS]['amount'],
				2);
	}
}
