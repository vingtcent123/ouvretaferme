<?php
namespace preaccounting;

Class ImportLib {

	public static function getInvoiceSales(\farm\Farm $eFarm, \Search $search): \Collection {

		$eFarm->expects(['cFinancialYear']);

		$cAccount = \account\AccountLib::getAll();

		$cInvoice = AccountingLib::getInvoices($eFarm, $search, TRUE);

		$fec = AccountingLib::generateInvoiceFec($cInvoice, $eFarm['cFinancialYear'], $cAccount, TRUE);

		// Rattacher les opérations aux invoices
		foreach($cInvoice as &$eInvoice) {

			$eInvoice['operations'] = self::filterOperations($fec, (string)$eInvoice['number']);

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
			'cashflow' => \bank\Cashflow::getSelection() + ['account' => \bank\BankAccount::getSelection()],
			'cSale' => \selling\Sale::model()
				->select([
					'id', 'shipping', 'shippingExcludingVat', 'shippingVatRate',
					'cPayment' => \selling\Payment::model()
						->select(\selling\Payment::getSelection())
						->or(
							fn() => $this->whereOnlineStatus(NULL),
							fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
						)
						->delegateCollection('sale'),
					'cItem' => \selling\Item::model()
						->select(['id', 'price', 'priceStats', 'vatRate', 'account', 'type', 'product' => ['id', 'proAccount', 'privateAccount']])
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

		foreach($cInvoice as $eInvoice) {

			self::importInvoice($eFarm, $eInvoice);

		}

	}
	public static function importInvoice(\farm\Farm $eFarm, \selling\Invoice $eInvoice): void {

		$eFarm->expects(['eFinancialYear']);

		$eFinancialYear = $eFarm['eFinancialYear'];

		if($eInvoice->acceptAccountingImport() === FALSE) {
			return;
		}

		$fw = new \FailWatch();

		if($eFinancialYear->empty()) {
			\Fail::log('Invoice::importNoFinancialYear');
			return;
		}

		if(\account\FinancialYearLib::isDateInFinancialYear($eInvoice['cashflow']['date'], $eFinancialYear) === FALSE) {
			\Fail::log('Invoice::importNotBelongsToFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll();
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\selling\Invoice::model()->beginTransaction();

		$eThirdParty = self::getOrCreateThirdParty($eInvoice['customer']);

		$cInvoice = new \Collection();
		$cInvoice->append($eInvoice);
		$fecData = \preaccounting\AccountingLib::generateInvoiceFec($cInvoice, new \Collection([$eFinancialYear]), $cAccount, TRUE);

		$eOperationBase = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
		]);

		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperationBase, $eInvoice);

		\selling\Invoice::model()->update($eInvoice, ['accountingHash' => $hash]);
		\selling\Sale::model()->whereInvoice($eInvoice)->update(['accountingHash' => $hash]);
		\bank\Cashflow::model()->update($eInvoice['cashflow'], ['status' => \bank\Cashflow::ALLOCATED, 'hash' => $hash]);

		\selling\Invoice::model()->commit();

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
				'normalizedName' => \account\ThirdPartyLib::normalizeName($eCustomer->getName()),
			]);

			\account\ThirdPartyLib::create($eThirdParty);

			$eThirdParty = \account\ThirdPartyLib::getByCustomer($eCustomer);

		}

		return $eThirdParty;

	}

	protected static function filterOperations(array $extraction, string $document) {

		$operations = array_filter($extraction, fn($line) => $line[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT] === $document);

		return AccountingLib::sortOperations($operations);

	}

	// Note : les opérations doivent concerner une même facture.
	private static function createOperations(
		\account\FinancialYear $eFinancialYear,
		array $fecData,
		\Collection $cAccount,
		\Collection $cPaymentMethod,
		\journal\Operation $eOperationBase,
		\selling\Invoice $eInvoice,
	): void {

		$cOperation = new \Collection();
		$cJournalCode = \journal\JournalCodeLib::getAll();

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
			if($eAccount->notEmpty() and $eAccount['journalCode']->notEmpty() and $cJournalCode->offsetExists($eAccount['journalCode']['id'])) {
				$eJournalCode = $eAccount['journalCode'];
			} else {
				$eJournalCode = new \journal\JournalCode();
			}
			$date = mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 0, 4).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 4, 2).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], -2);
			$ePaymentMethod = $cPaymentMethod->find(fn($e) => $e['name'] === $data[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])->first();

			if(\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::BANK_ACCOUNT_CLASS) === FALSE) {
				$details = new \Set(\account\AccountUi::getVatCodeByClass($eAccount['class'], $eFinancialYear));
			} else {
				$details = NULL;
			}

			$eOperation = new \journal\Operation(array_merge($eOperationBase->getArrayCopy(), [
				'id' => NULL,
				'financialYear' => $eFinancialYear,
				'account' => $eAccount,
				'accountLabel' => $data[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL],
				'description' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DESCRIPTION],
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
				'details' => $details,
			]));

			// On essaie de rattacher les opérations liées (type TVA) à leurs copines
			if(strpos($data[\preaccounting\AccountingLib::FEC_COLUMN_NUMBER], '-') !== FALSE) {

				[$currentNumber, $number] = array_map('intval', explode('-', $data[\preaccounting\AccountingLib::FEC_COLUMN_NUMBER]));

				if($cOperation->offsetExists($number)) {

					$eOperationOrigin = $cOperation->offsetGet($number);
					$eOperation['operation'] = $eOperationOrigin;

					\journal\Operation::model()
						->update($eOperationOrigin, [
							'vatRate' => round($eOperation['amount'] / $cOperation[$number]['amount'], 4) * 100,
							'vatAccount' => $eOperation['account']]
						);

				}
				$offset = $currentNumber;
			} else {
				$offset = (int)$data[\preaccounting\AccountingLib::FEC_COLUMN_NUMBER];
			}

			\journal\Operation::model()->insert($eOperation);

			if($eInvoice->notEmpty() and $eInvoice['cashflow']->notEmpty()) {

				$eOperationCashflow = new \journal\OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $eInvoice['cashflow'],
					'amount' => min($eOperation['amount'], abs($eInvoice['cashflow']['amount'])),
				]);

				\journal\OperationCashflow::model()->insert($eOperationCashflow);

			}

			$cOperation->offsetSet($offset, $eOperation);

		}

	}

}

