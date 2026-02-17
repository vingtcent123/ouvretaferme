<?php
namespace preaccounting;

Class PreaccountingLib {

	public static function extractMonths(\account\FinancialYear $eFinancialYear): array {

		$date = $eFinancialYear['startDate'];
		$dates = [];
		while($date <= $eFinancialYear['endDate']) {

			$nextDate = date('Y-m-d', strtotime($date.' + 1 month'));
			$dates[] = mb_substr($date, 0, 7);
			$date = $nextDate;

		}

		return $dates;

	}

	public static function checkInvoices(\farm\Farm $eFarm, array $dates): \Collection {

		return \selling\Payment::model()
			->select(['month' => new \Sql('SUBSTRING(paidAt, 1, 7)', 'string'), 'count' => new \Sql('COUNT(*)', 'int')])
			->whereFarm($eFarm)
			->whereStatus(\selling\Payment::PAID)
			->whereCashflow('!=', NULL)
			->whereAccountingHash(NULL)
			->whereAccountingReady(TRUE)
			->whereSource(\selling\Payment::INVOICE)
			->group(['month'])
			->having('month LIKE "'.join('%" OR month LIKE "', $dates).'%"')
			->getCollection(index: ['month']);

	}

	public static function checkImportedInvoices(\farm\Farm $eFarm, array $dates): \Collection {

		return \selling\Payment::model()
			->select(['month' => new \Sql('SUBSTRING(paidAt, 1, 7)', 'string'), 'count' => new \Sql('COUNT(*)', 'int')])
			->whereFarm($eFarm)
			->whereStatus(\selling\Payment::PAID)
			->whereCashflow('!=', NULL)
			->whereAccountingHash('!=', NULL)
			->whereAccountingReady(TRUE)
			->whereSource(\selling\Payment::INVOICE)
			->group(['month'])
			->having('month LIKE "'.join('%" OR month LIKE "', $dates).'%"')
			->getCollection(index: ['month']);

	}

	/**
	 * Exclut d'emblée les factures rapprochées qui ont déjà été importées
	 */
	public static function checkCash(\Collection $cRegister, array $dates): \Collection {

		return \cash\Cash::model()
			->select(['month' => new \Sql('SUBSTRING(m1.date, 1, 7)', 'string'), 'status',  'register' => ['id', 'account'], 'count' => new \Sql('COUNT(*)', 'int')])
			->join(\selling\Payment::model(), 'm1.payment = m2.id AND m2.accountingHash IS NULL', 'LEFT')
			->where('m1.date LIKE "'.join('%" OR m1.date LIKE "', $dates).'%"')
			->where(new \Sql('m1.accountingHash IS NULL'))
			->whereRegister('IN', $cRegister->getIds())
			->where('m1.source != "'.\cash\Cash::INITIAL.'"')
			->where(new \Sql('m1.status = "'.\cash\Cash::VALID.'"'))
			->group(['register', 'm1_month', 'm1_status'])
			->getCollection(index: ['register', 'month']);

	}

	public static function checkImportedCash(\Collection $cRegister, array $dates): \Collection {

		return \cash\Cash::model()
			->select(['month' => new \Sql('SUBSTRING(m1.date, 1, 7)', 'string'), 'status',  'register' => ['id', 'account'], 'count' => new \Sql('COUNT(*)', 'int')])
			->join(\selling\Payment::model(), 'm1.payment = m2.id AND m2.accountingHash IS NULL', 'LEFT')
			->where('m1.date LIKE "'.join('%" OR m1.date LIKE "', $dates).'%"')
			->where(new \Sql('m1.accountingHash IS NOT NULL'))
			->whereRegister('IN', $cRegister->getIds())
			->where('m1.source != "'.\cash\Cash::INITIAL.'"')
			->where(new \Sql('m1.status = "'.\cash\Cash::VALID.'"'))
			->group(['register', 'm1_month', 'm1_status'])
			->getCollection(index: ['register', 'month']);
	}

}
