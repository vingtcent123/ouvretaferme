<?php
namespace preaccounting;

Class PreaccountingLib {

	public static function counts(\farm\Farm $eFarm, string $from, string $to, \Search $search): array {

		$numberImport = [
			'market' => \preaccounting\AccountingLib::countMarkets($eFarm, $from, $to),
			'invoice' => \preaccounting\AccountingLib::countInvoices($eFarm, $search),
			'sales' => \preaccounting\AccountingLib::countSales($eFarm, $search)
		];

		$numberReconciliate = \preaccounting\SuggestionLib::countWaiting();

		return [
			'import' => $numberImport,
			'reconciliate' => $numberReconciliate,
		];

	}
}
