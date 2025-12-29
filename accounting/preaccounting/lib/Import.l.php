<?php
namespace preaccounting;

Class ImportLib {

	public static function getInvoiceSales(\farm\Farm $eFarm, \Search $search): \Collection {

		$eFarm->expects(['cFinancialYear']);

		$cAccount = \account\AccountLib::getAll();
		$extraction = \preaccounting\AccountingLib::extractInvoice($eFarm, $search, $eFarm['cFinancialYear'], $cAccount, forImport: TRUE);

		if($search->get('reconciliated') === 0) {

			\selling\Invoice::model()->whereCashflow('=', NULL);

		} else if($search->get('reconciliated') === 1) {

			\selling\Invoice::model()->whereCashflow('!=', NULL);

		}

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'document', 'name', 'customer' => ['id', 'name', 'type', 'destination'],
				'date', 'accountingHash',
				'taxes', 'hasVat', 'vat', 'priceExcludingVat', 'priceIncludingVat',
				'readyForAccounting',
			])
			->whereReadyForAccounting(TRUE)
			->whereName('IN', array_column($extraction, \preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT))
			->whereFarm($eFarm)
			->sort(['date' => SORT_ASC])
			->getCollection(NULL, NULL, 'name');

		foreach($cInvoice as &$eInvoice) {

			$eInvoice['operations'] = self::sortOperations($extraction, (string)$eInvoice['name']);;

		}

		return $cInvoice;

	}


	public static function ignoreInvoice(\selling\Invoice $eInvoice): void {

		\selling\Invoice::model()->update($eInvoice, ['readyForAccounting' => NULL]);

	}
	public static function ignoreInvoices(\Collection $cInvoice): void {

		\selling\Invoice::model()
			->whereId('IN', $cInvoice->getIds())
			->update(['readyForAccounting' => NULL]);

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

	public static function importInvoices(\farm\Farm $eFarm, \Collection $cInvoice): void {

		\journal\Operation::model()->beginTransaction();

		foreach($cInvoice as $eInvoice) {

			self::importInvoice($eFarm, $eInvoice);

		}

		\journal\Operation::model()->commit();

	}
	public static function importInvoice(\farm\Farm $eFarm, \selling\Invoice $eInvoice): void {

		$eFarm->expects(['eFinancialYear']);

		$eFinancialYear = $eFarm['eFinancialYear'];

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Sale::importNoFinancialYear');
			return;
		}

		$fw->validate();

		// On regarde s'il y a eu un rapprochement => pour créer la contrepartie en banque
		$eCashflow = \bank\CashflowLib::getByInvoice($eInvoice);
		$eInvoice['cashflow'] = $eCashflow;

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\journal\Operation::model()->beginTransaction();

		$eThirdParty = self::getOrCreateThirdParty($eInvoice['customer']);

		$cInvoice = new \Collection();
		$cInvoice->append($eInvoice);
		$fecData = \preaccounting\AccountingLib::generateInvoiceFec($cInvoice, new \Collection([$eFinancialYear]), $cAccount);

		$eOperationBase = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
			'description' => $eInvoice['name'],
		]);

		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperationBase);

		\selling\Invoice::model()->update($eInvoice, ['accountingHash' => $hash]);
		\selling\Sale::model()->whereInvoice($eInvoice)->update(['accountingHash' => $hash]);

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

		$operations = array_filter($extraction, fn($line) => $line[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT] === $document);

		usort($operations, function($entry1, $entry2) {
			if((int)$entry1[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] < (int)$entry2[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
				return -1;
			}
			if((int)$entry1[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL] > (int)$entry2[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL]) {
				return 1;
			}
			return strcmp($entry1[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD], $entry2[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD]);
		});

		return $operations;

	}

	private static function createOperations(\account\FinancialYear $eFinancialYear, array $fecData, \Collection $cAccount, \Collection $cPaymentMethod, \journal\Operation $eOperationBase): void {

		$cOperation = new \Collection();
		$eJournalCode = new \journal\JournalCode();

		foreach($fecData as $data) {

			$eAccount = $cAccount->find(fn($e) => $e['class'] === trim($data[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], '0'))->first();
			if($eAccount === NULL) {
				if(\account\AccountLabelLib::isFromClass($data[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL], \account\AccountSetting::BANK_ACCOUNT_CLASS)) {
					$eAccount = $cAccount->find(fn($e) => $e['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS)->first();
				}
			}
			if($eAccount === NULL) {
				$eAccount = new \account\Account();
			}
			if($eAccount->notEmpty() and $eAccount['journalCode']->notEmpty()) {
				$eJournalCode = $eAccount['journalCode'];
			}
			$date = mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 0, 4).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 4, 2).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], -2);
			$ePaymentMethod = $cPaymentMethod->find(fn($e) => $e['name'] === $data[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])->first();

			$eOperation = new \journal\Operation(array_merge($eOperationBase->getArrayCopy(), [
				'id' => NULL,
				'financialYear' => $eFinancialYear,
				'account' => $eAccount,
				'accountLabel' => $data[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL],
				'journalCode' => $eJournalCode,
				'date' => $date,
				'document' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT],
				'documentDate' => $date,
				'amount' => abs($data[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]),
				'type' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DEBIT] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'vatRate' => 0,
				'vatAccount' => new \account\Account(),
				'operation' => new \journal\Operation(),
				'paymentDate' => $date,
				'paymentMethod' => $ePaymentMethod,
			]));

			$cOperation->append($eOperation);

		}

		\journal\Operation::model()->insert($cOperation);

	}

}

