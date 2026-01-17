<?php
namespace overview;

class AnalyzeLib {

	const TAB_FINANCIAL_YEAR = 'financial-year';
	const TAB_BANK = 'bank';
	const TAB_CHARGES = 'charges';
	const TAB_SIG = 'sig';
	const TAB_INCOME_STATEMENT = 'income-statement';
	const TAB_BALANCE_SHEET = 'balance-sheet';
	const TAB_VAT = 'vat';

	public static function getViews(): array {

		return [self::TAB_BANK, self::TAB_CHARGES, self::TAB_SIG, self::TAB_INCOME_STATEMENT, self::TAB_BALANCE_SHEET, self::TAB_VAT];

	}

	public static function getResultOperationsByMonth(\account\FinancialYear $eFinancialYear): \Collection {

		return \journal\Operation::model()
			->select([
				'month' => new \Sql('DATE_FORMAT(date, "%Y-%m")'),
				'charge' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'", amount, 0))'),
				'product' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'", amount, 0))'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'" OR SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'"'))
			->join(\account\Account::model(), 'm1.account = m2.id')
			->group(['m1_month'])
			->getCollection(NULL, NULL, ['month']);

	}

	public static function getChargeOperationsByMonth(\account\FinancialYear $eFinancialYear): array {

		$cAccount = \account\Account::model()
			->select([
				'class',
				'description' => new \Sql('LOWER(description)'),
			])
			->where(new \Sql('SUBSTRING(class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'"'))
			->where('LENGTH(class) = 2')
			->sort(['description' => SORT_ASC])
			->getCollection();

		$cOperation = \journal\Operation::model()
			->select([
				'big_class' => new \Sql('SUBSTRING(m2.class, 1, 2)'),
				'total' => new \Sql('SUM(amount)'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'"'))
			->join(\account\Account::model(), 'm1.account = m2.id')
			->group(['m1_big_class'])
			->getCollection(NULL, NULL, 'big_class');

		return [$cOperation, $cAccount];

	}

	public static function getBankOperationsByMonth(\account\FinancialYear $eFinancialYear, string $type): \Collection {

		$accountClass = $type === 'bank' ? \account\AccountSetting::BANK_ACCOUNT_CLASS : \account\AccountSetting::CASH_ACCOUNT_CLASS;

		$ccOperation = \journal\Operation::model()
			->select([
				'accountLabel',
				'month' => new \Sql('DATE_FORMAT(date, "%Y-%m")'),
				'credit' => new \Sql('SUM(IF(type = "credit", amount, 0))'),
				'debit' => new \Sql('SUM(IF(type = "debit", amount, 0))'),
				'total' => new \Sql('SUM(IF(type = "debit", amount, -amount))'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where('SUBSTRING(accountLabel, 1, '.strlen((string)$accountClass).') = "'.$accountClass.'"')
			->group(['accountLabel', 'month'])
			->sort(['accountLabel' => SORT_ASC, 'month' => SORT_ASC])
			->getCollection(NULL, NULL, ['accountLabel', 'month']);

		// Total en cumulatif
		foreach($ccOperation as $cOperation) {
			$lastSolde = 0;
			foreach($cOperation as &$eOperation) {
				$eOperation['total'] += $lastSolde;
				$lastSolde = $eOperation['total'];
			}
		}

		return $ccOperation;
	}

}
?>
