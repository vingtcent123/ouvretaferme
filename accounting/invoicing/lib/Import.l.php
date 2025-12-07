<?php
namespace invoicing;

Class ImportLib {

	const IGNORED_SALE_HASH = '0000000000000000000';

	public static function ignoreSale(\selling\Sale $eSale): void {

		\selling\Sale::model()->update($eSale, ['accountingHash' => self::IGNORED_SALE_HASH]);

	}
	public static function ignoreInvoice(\selling\Invoice $eInvoice): void {

		\selling\Sale::model()->update($eInvoice, ['accountingHash' => self::IGNORED_SALE_HASH]);

	}

	public static function importInvoice(\farm\Farm $eFarm, \selling\Invoice $eInvoice, \account\FinancialYear $eFinancialYear): void {

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Sale::importNoFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\journal\Operation::model()->beginTransaction();

		// Get or create third party
		$eThirdParty = \account\ThirdPartyLib::getByCustomer($eInvoice['customer']);

		if($eThirdParty->empty()) {

			$eThirdParty = new \account\ThirdParty([
				'name' => $eInvoice['customer']->getName(),
				'customer' => $eInvoice['customer'],
				'clientAccountLabel' => \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS),
			]);

			\account\ThirdPartyLib::create($eThirdParty);
			$eThirdParty = \account\ThirdPartyLib::getByCustomer($eInvoice['customer']);

		}

		$cInvoice = new \Collection();
		$cInvoice->append($eInvoice);
		$fecData = \farm\AccountingLib::generateInvoiceFec($cInvoice, new \Collection([$eFinancialYear]), $cAccount);

		$eOperation = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => $eInvoice['name'],
		]);
		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperation);

		\selling\Invoice::model()->update($eInvoice, ['accountingHash' => $hash]);

		\journal\Operation::model()->commit();

	}

	public static function importMarket(\farm\Farm $eFarm, \selling\Sale $eSale, \account\FinancialYear $eFinancialYear): void {

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Sale::importNoFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\journal\Operation::model()->beginTransaction();

		// Get or create third party
		$eThirdParty = \account\ThirdPartyLib::getByCustomer($eSale['customer']);

		if($eThirdParty->empty()) {

			$eThirdParty = new \account\ThirdParty([
				'name' => $eSale['customer']->getName(),
				'customer' => $eSale['customer'],
				'clientAccountLabel' => \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS),
			]);

			\account\ThirdPartyLib::create($eThirdParty);
			$eThirdParty = \account\ThirdPartyLib::getByCustomer($eSale['customer']);

		}

		$cSale = new \Collection();
		$cSale->append($eSale);
		$fecData = \farm\AccountingLib::generateMarketFec($cSale, new \Collection([$eFinancialYear]), $cAccount);

		$eOperation = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => new ImportUi()->getDescription($eSale),
		]);
		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperation);

		\selling\Sale::model()->update($eSale, ['accountingHash' => $hash]);

		\journal\Operation::model()->commit();

	}

	private static function createOperations(\account\FinancialYear $eFinancialYear, array $fecData, \Collection $cAccount, \Collection $cPaymentMethod, \journal\Operation $eOperationBase): \Collection {

		$cOperation = new \Collection();

		foreach($fecData as $data) {

			$eAccount = $cAccount->find(fn($e) => $e['class'] === trim($data[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], '0'))->first();
			$date = mb_substr($data[\farm\AccountingLib::FEC_COLUMN_DATE], 0, 4).'-'.mb_substr($data[\farm\AccountingLib::FEC_COLUMN_DATE], 4, 2).'-'.mb_substr($data[\farm\AccountingLib::FEC_COLUMN_DATE], -2);
			$ePaymentMethod = $cPaymentMethod->find(fn($e) => $e['name'] === $data[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])->first();

			$eOperation = new \journal\Operation(array_merge($eOperationBase->getArrayCopy(), [
				'id' => NULL,
				'financialYear' => $eFinancialYear,
				'account' => $eAccount,
				'accountLabel' => $data[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL],
				'date' => $date,
				'document' => $data[\farm\AccountingLib::FEC_COLUMN_DOCUMENT],
				'documentDate' => $date,
				'amount' => abs($data[\farm\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]),
				'type' => $data[\farm\AccountingLib::FEC_COLUMN_DEBIT] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'vatRate' => 0,
				'vatAccount' => new \account\Account(),
				'operation' => new \journal\Operation(),
				'paymentDate' => $date,
				'paymentMethod' => $ePaymentMethod,
			]));

			$cOperation->append($eOperation);

		}

		\journal\Operation::model()->insert($cOperation);

		return $cOperation;

	}

	public static function getInvoiceSales(\farm\Farm $eFarm, string $from, string $to): \Collection {

		$cFinancialYear = \account\FinancialYearLib::getAll();
		$cAccount = \account\AccountLib::getAll();
		$extraction = \farm\AccountingLib::extractInvoice($eFarm, $from, $to, $cFinancialYear, $cAccount, forImport: TRUE);

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'name', 'customer' => ['id', 'name', 'type', 'destination'],
				'date', 'accountingHash',
				'taxes', 'hasVat', 'vat', 'priceExcludingVat', 'priceIncludingVat'
			])
			->whereName('IN', array_column($extraction, \farm\AccountingLib::FEC_COLUMN_DOCUMENT))
			->whereFarm($eFarm)
			->sort(['date' => SORT_ASC])
			->getCollection(NULL, NULL, 'name');

		foreach($cInvoice as &$eInvoice) {
			$operations = array_filter($extraction, fn($line) => $line[\farm\AccountingLib::FEC_COLUMN_DOCUMENT] === (string)$eInvoice['name']);
			usort($operations, function($entry1, $entry2) {
				if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] < (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
					return -1;
				}
				if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] > (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
					return 1;
				}
				return strcmp($entry1[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD], $entry2[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
			});
			$eInvoice['operations'] = $operations;
		}

		return $cInvoice;

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

