<?php
namespace journal;

class AnalyzeLib {

	public static function getResultForFinancialYear(\accounting\FinancialYear $eFinancialYear): array {

		$eOperation = new Operation();

		Operation::model()
       ->select([
         'charge' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'", amount, 0))'),
         'product' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\productAccountClass').'", amount, 0))'),
       ])
       ->whereDate('>=', $eFinancialYear['startDate'])
       ->whereDate('<=', $eFinancialYear['endDate'])
       ->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'" OR SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\productAccountClass').'"'))
       ->join(\accounting\Account::model(), 'm1.account = m2.id')
			->get($eOperation);

		return $eOperation->getArrayCopy();

	}

	public static function getResult(\accounting\FinancialYear $eFinancialYear): array {

		$cOperation = Operation::model()
      ->select([
				'class' => new \Sql('m2.class'),
	      'credit' => new \Sql('SUM(IF(type = "credit", amount, 0))'),
	      'debit' => new \Sql('SUM(IF(type = "debit", amount, 0))'),
      ])
      ->whereDate('>=', $eFinancialYear['startDate'])
      ->whereDate('<=', $eFinancialYear['endDate'])
      ->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'" OR SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\productAccountClass').'"'))
      ->join(\accounting\Account::model(), 'm1.account = m2.id')
      ->group(['class'])
			->sort(['class' => SORT_ASC])
      ->getCollection(NULL, NULL, ['class']);

		$cAccount = \accounting\AccountLib::getByClasses($cOperation->getColumn('class'), 'class');

		return [$cOperation->getArrayCopy(), $cAccount];

	}
	public static function getResultOperationsByMonth(\accounting\FinancialYear $eFinancialYear): \Collection {

		return Operation::model()
			->select([
				'month' => new \Sql('DATE_FORMAT(date, "%Y-%m")'),
				'charge' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'", amount, 0))'),
				'product' => new \Sql('SUM(IF(SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\productAccountClass').'", amount, 0))'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'" OR SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\productAccountClass').'"'))
			->join(\accounting\Account::model(), 'm1.account = m2.id')
			->group(['m1_month'])
			->getCollection(NULL, NULL, ['month']);

	}

	public static function getChargeOperationsByMonth(\accounting\FinancialYear $eFinancialYear): array {

		$cAccount = \accounting\Account::model()
			->select([
				'class',
				'description' => new \Sql('LOWER(description)'),
			])
			->where(new \Sql('SUBSTRING(class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'"'))
			->where('LENGTH(class) = 2')
			->sort(['description' => SORT_ASC])
			->getCollection();

		$cOperation = Operation::model()
			->select([
				'big_class' => new \Sql('SUBSTRING(m2.class, 1, 2)'),
				'total' => new \Sql('SUM(amount)'),
			])
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->where(new \Sql('SUBSTRING(m2.class, 1, 1) = "'.\Setting::get('accounting\chargeAccountClass').'"'))
			->join(\accounting\Account::model(), 'm1.account = m2.id')
			->group(['m1_big_class'])
			->getCollection(NULL, NULL, 'big_class');

		return [$cOperation, $cAccount];

	}

	public static function getBankOperationsByMonth(\accounting\FinancialYear $eFinancialYear, string $type): \Collection {

		$accountClass = $type === 'bank' ? \Setting::get('accounting\bankAccountClass') : \Setting::get('accounting\cashAccountClass');

		$cOperation = Operation::model()
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
