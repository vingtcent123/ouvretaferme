<?php
namespace overview;

/**
 * Traitements relatifs au Bilan Comptable
 */
Class BalanceSheetLib {

	public static function getData(\account\FinancialYear $eFinancialYear): \Collection {

		return \journal\Operation::model()
			->select([
				'class' => new \Sql('SUBSTRING(accountLabel, 1, 3)'),
				// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float')
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->group('class')
			->having('amount != 0.0')
			->sort(['class' => SORT_ASC])
			->getCollection();

	}

	public static function getDetailData(\account\FinancialYear $eFinancialYear): \Collection {

		return \journal\Operation::model()
			->select([
				'class' => new \Sql('accountLabel'),
				// Attention pour les classes 4 et 5, calcul ici : débit - crédit (équivalent actif)
				'amount' => new \Sql('SUM(IF(accountLabel LIKE "1%", -1, 1) * IF(type = "debit", amount, -amount))', 'float'),
				'description' => new \Sql('MIN(description)')
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->group('class')
			->having('amount != 0.0')
			->sort(['class' => SORT_ASC])
			->getCollection();

	}

}
