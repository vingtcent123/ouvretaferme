<?php
namespace preaccounting;

Class PreaccountingLib {

	public static function countImports(\farm\Farm $eFarm, string $from, string $to, \Search $search): array {

		return [
			'market' => \preaccounting\AccountingLib::countMarkets($eFarm, $from, $to),
			'invoice' => \preaccounting\AccountingLib::countInvoices($eFarm, $search),
			'sales' => \preaccounting\AccountingLib::countSales($eFarm, $search)
		];

	}

}
