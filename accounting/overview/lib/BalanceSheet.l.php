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
			'amount' => new \Sql('SUM(IF(type = "debit", amount, -amount))', 'float') // Attention, calcul ici : débit - crédit (équivalent actif)
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'%"'))
			->where(new \Sql('accountLabel NOT LIKE "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'%"'))
			->group('class')
			->sort(['class' => SORT_ASC])
			->getCollection();

	}

}
