<?php
namespace invoicing;

Class ImportLib {

	const IGNORED_SALE_HASH = '0000000000000000000';

	public static function getSales(\farm\Farm $eFarm, \Search $search): \Collection {

		$cFinancialYear = \account\FinancialYearLib::getAll();
		$cAccount = \account\AccountLib::getAll();
		$extraction = \farm\AccountingLib::extractSales($eFarm, $search, $cFinancialYear, $cAccount, forImport: TRUE);

		$cSale = \selling\Sale::model()
			->select([
				'id', 'document', 'customer' => ['id', 'name', 'type', 'destination'],
				'deliveredAt', 'accountingHash', 'profile', 'invoice',
				'taxes', 'hasVat', 'vat', 'priceExcludingVat', 'priceIncludingVat', 'readyForAccounting'
			])
			->whereDocument('IN', array_column($extraction, \farm\AccountingLib::FEC_COLUMN_DOCUMENT))
			->whereFarm($eFarm)
			->sort(['deliveredAt' => SORT_ASC])
			->getCollection(NULL, NULL, 'document');

		foreach($cSale as &$eSale) {

			$eSale['operations'] = self::sortOperations($extraction, (string)$eSale['document']);

		}

		return $cSale;

	}

	public static function getInvoiceSales(\farm\Farm $eFarm, \Search $search): \Collection {

		$cFinancialYear = \account\FinancialYearLib::getAll();
		$cAccount = \account\AccountLib::getAll();
		$extraction = \farm\AccountingLib::extractInvoice($eFarm, $search, $cFinancialYear, $cAccount, forImport: TRUE);

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'name', 'customer' => ['id', 'name', 'type', 'destination'],
				'date', 'accountingHash',
				'taxes', 'hasVat', 'vat', 'priceExcludingVat', 'priceIncludingVat',
				'readyForAccounting',
			])
			->whereReadyForAccounting(TRUE)
			->whereName('IN', array_column($extraction, \farm\AccountingLib::FEC_COLUMN_DOCUMENT))
			->whereFarm($eFarm)
			->sort(['date' => SORT_ASC])
			->getCollection(NULL, NULL, 'name');

		foreach($cInvoice as &$eInvoice) {

			$eInvoice['operations'] = self::sortOperations($extraction, (string)$eInvoice['name']);;

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

			$eSale['operations'] = self::sortOperations($extraction, (string)$eSale['document']);

		}

		return $cSale;
	}

	public static function ignoreSale(\selling\Sale $eSale): void {

		\selling\Sale::model()->update($eSale, ['accountingHash' => self::IGNORED_SALE_HASH]);

	}
	public static function ignoreInvoice(\selling\Invoice $eInvoice): void {

		\selling\Invoice::model()->update($eInvoice, ['accountingHash' => self::IGNORED_SALE_HASH]);

	}
	public static function ignoreInvoices(\Collection $cInvoice): void {

		\selling\Invoice::model()
			->whereId('IN', $cInvoice->getIds())
			->update(['accountingHash' => self::IGNORED_SALE_HASH]);

	}
	public static function ignoreSales(\Collection $cSale): void {

		\selling\Sale::model()
			->whereId('IN', $cSale->getIds())
			->update(['accountingHash' => self::IGNORED_SALE_HASH]);

	}

	public static function saleSelection(): array {

		return [
			'id',
			'document', 'invoice', 'accountingHash', 'preparationStatus', 'closed',
			'type', 'profile', 'priceIncludingVat', 'readyForAccounting',
			'customer' => [
			'id', 'name',
			'thirdParty' => \account\ThirdParty::model()
				->select('id', 'clientAccountLabel')
				->delegateElement('customer')
			],
			'deliveredAt',
			'cItem' => \selling\Item::model()
				->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
				->delegateCollection('sale'),
			'cPayment' => \selling\Payment::model()
	      ->select(\selling\Payment::getSelection())
	      ->or(
	        fn() => $this->whereOnlineStatus(NULL),
	        fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
	      )
	      ->delegateCollection('sale'),
		];

	}
	public static function getSaleById(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, int $saleId): \selling\Sale {

		$eSale = \selling\SaleLib::filterForAccounting(
				$eFarm, new \Search(['from' => $eFinancialYear['startDate'], 'to' => $eFinancialYear['endDate']])
			)
			->select(self::saleSelection())
			->whereId($saleId)
			->whereAccountingHash(NULL)
			->get();

		return $eSale;

	}

	public static function getSalesByIds(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, array $saleIds): \Collection {

		$cSale = \selling\SaleLib::filterForAccounting(
				$eFarm, new \Search(['from' => $eFinancialYear['startDate'], 'to' => $eFinancialYear['endDate']])
			)
			->select(self::saleSelection())
			->whereId('IN', $saleIds)
			->whereAccountingHash(NULL)
			->getCollection();

		return $cSale;

	}

	public static function importSales(\farm\Farm $eFarm, \Collection $cSale, \account\FinancialYear $eFinancialYear): void {

		\journal\Operation::model()->beginTransaction();

		foreach($cSale as $eSale) {

			self::importSale($eFarm, $eSale, $eFinancialYear);

		}

		\journal\Operation::model()->commit();

	}

	public static function importSale(\farm\Farm $eFarm, \selling\Sale $eSale, \account\FinancialYear $eFinancialYear): void {

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Sale::importNoFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_SALE;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);
		\journal\Operation::model()->beginTransaction();

		$eThirdParty = self::getOrCreateThirdParty($eSale['customer']);

		$cSale = new \Collection();
		$cSale->append($eSale);
		$fecData = \farm\AccountingLib::generateSalesFec($cSale, new \Collection([$eFinancialYear]), $cAccount);

		$eOperation = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => new ImportUi()->getSaleDescription($eSale),
		]);
		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperation);

		if($eFinancialYear->isCashAccrualAccounting()) {

			$date = $eSale['deliveredAt'];
			$eAccount = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS)->first();

			if($eSale['cPayment']->count() === 1) {
				$ePaymentMethod = $eSale['cPayment']->first()['method'];
			} else {
				$ePaymentMethod = new \payment\Method();
			}
			$eOperationThirdParty = new \journal\Operation($eOperation->getArrayCopy() + [
				'id' => NULL,
				'financialYear' => $eFinancialYear,
				'account' => $eAccount,
				'accountLabel' => $eThirdParty['clientAccountLabel'],
				'date' => $date,
				'document' => $eSale['document'],
				'documentDate' => $date,
				'amount' => abs($eSale['priceIncludingVat']),
				'type' => $eSale['priceIncludingVat'] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'vatRate' => 0,
				'vatAccount' => new \account\Account(),
				'operation' => new \journal\Operation(),
				'paymentDate' => $date,
				'paymentMethod' => $ePaymentMethod,
			]);

			\journal\Operation::model()->insert($eOperationThirdParty);

			SuggestionLib::calculateForOperation($eOperationThirdParty);

		}

		\selling\Sale::model()->update($eSale, ['accountingHash' => $hash]);

		\journal\Operation::model()->commit();

	}

	public static function invoiceSelection(): array {

		return \selling\Invoice::getSelection() + [
			'cSale' => \selling\Sale::model()
				->select([
					'id',
					'cPayment' => \selling\Payment::model()
						->select(\selling\Payment::getSelection())
						->or(
							fn() => $this->whereOnlineStatus(NULL),
							fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
						)
						->delegateCollection('sale'),
					'cItem' => \selling\Item::model()
						->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
						->delegateCollection('sale')
				])
				->wherePreparationStatus(\selling\Sale::DELIVERED)
				->delegateCollection('invoice'),];

	}

	public static function getInvoiceById(int $id): \selling\Invoice {

		return \selling\InvoiceLib::getById($id, self::invoiceSelection());

	}

	public static function getInvoicesByIds(array $ids): \Collection {

		return \selling\InvoiceLib::getByIds($ids, self::invoiceSelection());

	}

	public static function importInvoices(\farm\Farm $eFarm, \Collection $cInvoice, \account\FinancialYear $eFinancialYear): void {

		\journal\Operation::model()->beginTransaction();

		foreach($cInvoice as $eInvoice) {

			self::importInvoice($eFarm, $eInvoice, $eFinancialYear);

		}

		\journal\Operation::model()->commit();

	}
	public static function importInvoice(\farm\Farm $eFarm, \selling\Invoice $eInvoice, \account\FinancialYear $eFinancialYear): void {

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Sale::importNoFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\journal\Operation::model()->beginTransaction();

		$eThirdParty = self::getOrCreateThirdParty($eInvoice['customer']);

		$cInvoice = new \Collection();
		$cInvoice->append($eInvoice);
		$fecData = \farm\AccountingLib::generateInvoiceFec($cInvoice, new \Collection([$eFinancialYear]), $cAccount);

		$eOperation = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => $eInvoice['name'],
		]);
		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperation);

		if($eFinancialYear->isCashAccrualAccounting()) {

			$date = $eInvoice['date'];
			$eAccount = $cAccount->find(fn($e) => (int)$e['class'] === (int)\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS)->first();

			$eOperationThirdParty = new \journal\Operation($eOperation->getArrayCopy() + [
				'id' => NULL,
				'financialYear' => $eFinancialYear,
				'account' => $eAccount,
				'accountLabel' => $eThirdParty['clientAccountLabel'],
				'date' => $date,
				'document' => $eInvoice['name'],
				'documentDate' => $date,
				'amount' => abs($eInvoice['priceIncludingVat']),
				'type' => $eInvoice['priceIncludingVat'] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'vatRate' => 0,
				'vatAccount' => new \account\Account(),
				'operation' => new \journal\Operation(),
				'paymentDate' => $date,
				'paymentMethod' => $eInvoice['paymentMethod'],
			]);

			\journal\Operation::model()->insert($eOperationThirdParty);

			SuggestionLib::calculateForOperation($eOperationThirdParty);
		}

		\selling\Invoice::model()->update($eInvoice, ['accountingHash' => $hash]);

		\journal\Operation::model()->commit();

	}

	public static function marketSelection(): array {

		$saleModule = clone \selling\Sale::model();

		return [
			'id',
			'document', 'invoice', 'accountingHash', 'preparationStatus', 'closed',
			'type', 'profile', 'marketParent',
			'customer' => [
				'id', 'name',
				'thirdParty' => \account\ThirdParty::model()
					->select('id', 'clientAccountLabel')
					->delegateElement('customer')
			],
			'deliveredAt',
			'cSale' => $saleModule
				->select([
					'id',
					'cPayment' => \selling\Payment::model()
						->select(\selling\Payment::getSelection())
						->or(
							fn() => $this->whereOnlineStatus(NULL),
							fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
						)
						->delegateCollection('sale'),
					'cItem' => \selling\Item::model()
						->select(['id', 'price', 'priceStats', 'vatRate', 'account'])
						->delegateCollection('sale')
				])
				->wherePreparationStatus(\selling\Sale::DELIVERED)
				->delegateCollection('marketParent'),
		];

	}

	public static function getMarketById(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, int $id): \selling\Sale {

		return \selling\SaleLib::filterForAccounting(
					$eFarm, new \Search(['from' => $eFinancialYear['startDate'], 'to' => $eFinancialYear['endDate']])
				)
			->select(self::marketSelection())
			->whereId($id)
			->whereAccountingHash(NULL)
			->get();

	}

	public static function getMarketsByIds(\farm\Farm $eFarm, \account\FinancialYear $eFinancialYear, array $ids): \Collection {

		return \selling\SaleLib::filterForAccounting(
					$eFarm, new \Search(['from' => $eFinancialYear['startDate'], 'to' => $eFinancialYear['endDate']])
				)
			->select(self::marketSelection())
			->whereId('IN', $ids)
			->whereAccountingHash(NULL)
			->getCollection();

	}

	public static function importMarkets(\farm\Farm $eFarm, \Collection $cSale, \account\FinancialYear $eFinancialYear): void {

		\journal\Operation::model()->beginTransaction();

		foreach($cSale as $eSale) {

			self::importMarket($eFarm, $eSale, $eFinancialYear);

		}

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
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_MARKET;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\journal\Operation::model()->beginTransaction();

		$eThirdParty = self::getOrCreateThirdParty($eSale['customer']);

		$cSale = new \Collection();
		$cSale->append($eSale);
		$fecData = \farm\AccountingLib::generateMarketFec($cSale, new \Collection([$eFinancialYear]), $cAccount);

		$eOperation = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => new ImportUi()->getSaleDescription($eSale),
		]);
		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperation);

		\selling\Sale::model()->update($eSale, ['accountingHash' => $hash]);

		\journal\Operation::model()->commit();

	}

	protected static function getOrCreateThirdParty(\selling\Customer $eCustomer): \account\ThirdParty {

		$eThirdParty = \account\ThirdPartyLib::getByCustomer($eCustomer);

		if($eThirdParty->empty()) {

			$eThirdParty = \account\ThirdParty::model()
	      ->select(\account\ThirdParty::getSelection())
	      ->whereName($eCustomer->getName())
	      ->get();

		}

		if($eThirdParty->empty()) {
			$eThirdParty = new \account\ThirdParty([
				'name' => $eCustomer->getName(),
				'customer' => $eCustomer,
				'clientAccountLabel' => \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS),
				'normalizedName' => \account\ThirdPartyLib::normalizeName($eCustomer->getName()),
			]);

			\account\ThirdPartyLib::create($eThirdParty);

			$eThirdParty = \account\ThirdPartyLib::getByCustomer($eCustomer);

		} else if($eThirdParty['clientAccountLabel'] === NULL) {

			$label = \account\ThirdPartyLib::getNextThirdPartyAccountLabel('clientAccountLabel', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);

			\account\ThirdParty::model()->update($eThirdParty, ['clientAccountLabel' => $label]);
			$eThirdParty['clientAccountLabel'] = $label;

		}

		return $eThirdParty;

	}

	protected static function sortOperations(array $extraction, string $document) {

		$operations = array_filter($extraction, fn($line) => $line[\farm\AccountingLib::FEC_COLUMN_DOCUMENT] === $document);

		usort($operations, function($entry1, $entry2) {
			if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] < (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
				return -1;
			}
			if((int)$entry1[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] > (int)$entry2[\farm\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
				return 1;
			}
			return strcmp($entry1[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD], $entry2[\farm\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
		});

		return $operations;

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

}

