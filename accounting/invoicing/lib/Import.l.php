<?php
namespace invoicing;

Class ImportLib {

	const IGNORED_SALE_HASH = '0000000000000000000';

	public static function ignoreSale(\selling\Sale $eSale): void {

		\selling\Sale::model()->update($eSale, ['accountingHash' => self::IGNORED_SALE_HASH]);

	}

	public static function getMarketSales(\farm\Farm $eFarm, string $from, string $to): \Collection {

		$cFinancialYear = \account\FinancialYearLib::getAll();
		$cAccount = \account\AccountLib::getAll();
		$extraction = \farm\AccountingLib::extractMarket($eFarm, $from, $to, $cFinancialYear, $cAccount, forImport: TRUE);

		$documents = array_unique(array_column($extraction, \farm\AccountingLib::FEC_COLUMN_DOCUMENT));

		$cSale = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereDocument('IN', $documents)
			->getCollection(NULL, NULL, 'document');

		foreach($cSale as &$eSale) {
			$operations = array_filter($extraction, fn($line) => $line[\farm\AccountingLib::FEC_COLUMN_DOCUMENT] === (string)$eSale['document']);
			usort($operations, function($entry1, $entry2) {
				if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] < (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
					return -1;
				}
				if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] > (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
					return 1;
				}
				return strcmp($entry1[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD], $entry2[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
			});
			$eSale['operations'] = $operations;
		}

		return $cSale;
	}

}

