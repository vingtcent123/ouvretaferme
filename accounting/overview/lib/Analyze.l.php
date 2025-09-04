<?php
namespace overview;

class AnalyzeLib {

	public static function getResultForFinancialYear(\account\FinancialYear $eFinancialYear): array {

		$eOperation = new \journal\Operation();

		\journal\Operation::model()
       ->select([
         'charge' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'", amount, 0))'),
         'product' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'", amount, 0))'),
       ])
       ->whereDate('>=', $eFinancialYear['startDate'])
       ->whereDate('<=', $eFinancialYear['endDate'])
       ->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'" OR SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'"'))
       ->join(\account\Account::model(), 'm1.account = m2.id')
			->get($eOperation);

		return $eOperation->getArrayCopy();

	}

	public static function getResult(\account\FinancialYear $eFinancialYear): array {

		$cOperation = \journal\Operation::model()
			->select([
				'class' => new \Sql('m2.class'),
				'credit' => new \Sql('SUM(IF(type = "credit", amount, 0))'),
				'debit' => new \Sql('SUM(IF(type = "debit", amount, 0))'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::CHARGE_ACCOUNT_CLASS.'" OR SUBSTRING(m2.class, 1, 1) = "'.\account\AccountSetting::PRODUCT_ACCOUNT_CLASS.'"'))
			->join(\account\Account::model(), 'm1.account = m2.id')
			->group(['class'])
			->sort(['class' => SORT_ASC])
			->getCollection(NULL, NULL, ['class']);

		$cAccount = \account\AccountLib::getByClasses($cOperation->getColumn('class'), 'class');

		return [$cOperation->getArrayCopy(), $cAccount];

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

		$cOperation = \journal\Operation::model()
			->select([
				'month' => new \Sql('DATE_FORMAT(date, "%Y-%m")'),
				'credit' => new \Sql('SUM(IF(type = "credit", amount, 0))'),
				'debit' => new \Sql('SUM(IF(type = "debit", amount, 0))'),
				'total' => new \Sql('SUM(IF(type = "debit", amount, -amount))'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where('SUBSTRING(accountLabel, 1, '.strlen((string)$accountClass).') = "'.$accountClass.'"')
			->group(['month'])
			->sort(['month' => SORT_ASC])
			->getCollection();

		// Total en cumulatif
		$lastSolde = 0;
		foreach($cOperation as &$eOperation) {
			$eOperation['total'] += $lastSolde;
			$lastSolde = $eOperation['total'];
		}

		return $cOperation;
	}

}
?>
