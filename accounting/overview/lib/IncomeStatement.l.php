<?php
namespace overview;

/**
 * Traitements relatifs au Compte de Résultat
 */
Class IncomeStatementLib {

	public static function getResultOperationsByFinancialYear(\account\FinancialYear $eFinancialYear): \Collection {

		return \journal\Operation::model()
			->select([
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)'),
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%", 1, -1) * IF(type = "debit", amount, -amount))')
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->or(
				fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"')),
				fn() => $this->where(new \Sql('accountLabel LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"')),
			)
			->group('class')
			->sort(['class' => SORT_ASC])
			->getCollection();

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

			return round($operations[\account\AccountSetting::PRODUCT_ACCOUNT_CLASS]['amount'] - $operations[\account\AccountSetting::CHARGE_ACCOUNT_CLASS]['amount'], 2);
	}
}
