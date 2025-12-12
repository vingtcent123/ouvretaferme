<?php
namespace invoicing;

Class InvoiceLib {

	public static function counts(\farm\Farm $eFarm, string $from, string $to, \Search $search): array {

		$numberImport = [
			'market' => \farm\AccountingLib::countMarkets($eFarm, $from, $to),
			'invoice' => \farm\AccountingLib::countInvoices($eFarm, $search),
			'sales' => \farm\AccountingLib::countSales($eFarm, $search)
		];

		$numberReconciliate = \invoicing\SuggestionLib::countWaitingOperations();

		$numberInvoice = [
			'sell' => 0,
			'buy' => 0,
		];

		return [
			'import' => $numberImport,
			'reconciliate' => $numberReconciliate,
			'invoice' => $numberInvoice,
		];

	}
}
