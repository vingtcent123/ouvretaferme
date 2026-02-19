<?php
namespace journal;

Class FecLib {

	public static function extractOperations(\account\FinancialYear $eFinancialYear): array {

		$cOperation = Operation::model()
			->select([
				'description', 'number', 'date', 'accountLabel', 'document', 'type', 'amount',
				'account' => ['description'],
				'journalCode' => ['code', 'name'],
				'paymentMethod' => ['name'],
			])
			->whereFinancialYear($eFinancialYear)
			->sort(['date' => SORT_ASC, 'hash' => SORT_ASC])
			->getCollection();

		return $cOperation->makeArray(function($eOperation) {

			return [
				$eOperation['journalCode']['code'] ?? '',
				$eOperation['journalCode']['name'] ?? '',
				$eOperation['number'] ?? '',
				str_replace('-', '', $eOperation['date']),
				$eOperation['accountLabel'],
				$eOperation['account']['description'],
				'',
				'',
				$eOperation['document'],
				str_replace('-', '', $eOperation['documentDate'] ?? $eOperation['date']),
				$eOperation['description'],
				$eOperation['type'] === Operation::DEBIT ? $eOperation['amount'] : '',
				$eOperation['type'] === Operation::CREDIT ? $eOperation['amount'] : '',
				'',
				str_replace('-', '', $eOperation['date']),
				str_replace('-', '', $eOperation['date']),
				$eOperation['type'] === Operation::DEBIT ? $eOperation['amount'] : -1 * $eOperation['amount'],
				'',
				str_replace('-', '', $eOperation['date']),
				$eOperation['paymentMethod']['name'] ?? '',
				''
			];
		});
	}

}
