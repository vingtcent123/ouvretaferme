<?php
namespace preaccounting;

Class ImportLib {

	public static function getCash(\farm\Farm $eFarm, \Search $search): \Collection {

		$eFarm->expects(['cFinancialYear']);

		$cAccount = \account\AccountLib::getAll(new \Search(['withVat' => TRUE, 'withJournal' => TRUE]));

		$cCash = CashLib::getForAccounting($eFarm, $search, TRUE);

		if($cCash->empty()) {
			return new \Collection();
		}

		[$fec, ] = AccountingLib::generateCashFec($cCash, $cAccount, $search);

		// Rattacher les opérations aux invoices
		foreach($cCash as &$eCash) {

			$document = match($eCash['source']) {
				\cash\Cash::SELL_INVOICE => $eCash['invoice']['number'] ?? '',
				\cash\Cash::SELL_SALE => $eCash['register']['id'].'-'.$eCash['position'],
				default => $eCash['register']['id'].'-'.$eCash['position'],
			};

			$eCash['operations'] = self::filterOperations($fec, $document);

		}

		return $cCash;

	}

	public static function getPayments(\farm\Farm $eFarm, \Search $search): \Collection {

		$eFarm->expects(['cFinancialYear']);

		$cAccount = \account\AccountLib::getAll(new \Search(['withVat' => TRUE, 'withJournal' => TRUE]));

		$cPayment = InvoiceLib::getForAccounting($eFarm, $search, TRUE);

		if($cPayment->empty()) {
			return new \Collection();
		}

		[$fec, ] = AccountingLib::generatePaymentsFec($cPayment, $cAccount, TRUE);

		// Rattacher les opérations aux invoices
		foreach($cPayment as &$ePayment) {

			$reference = (string)match($ePayment['source']) {
				\selling\Payment::INVOICE => $ePayment['invoice']['number'],
				\selling\Payment::SALE => $ePayment['sale']['document'],
			};

			$ePayment['operations'] = self::filterOperations($fec, $reference);

		}

		return $cPayment;

	}

	public static function ignorePayment(\selling\Payment $ePayment): void {

		\selling\Payment::model()->update($ePayment, ['accountingReady' => NULL]);

	}

	public static function importCollection(\farm\Farm $eFarm, \Search $search): void {

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($search->get('from'));

		if($eFinancialYear['status'] !== \account\FinancialYear::OPEN) {
			\Fail::log('preaccounting\Import::importCannotWriteInFinancialYear');
			return;
		}

		if($eFinancialYear->empty()) {
			\Fail::log('preaccounting\Import::importNoFinancialYear');
			return;
		}

		\selling\Payment::model()->beginTransaction();

			$lastValidationDate = \journal\OperationLib::getLastValidationDate($eFinancialYear);
			$cAccount = \account\AccountLib::getAll(new \Search(['withVat' => TRUE, 'withJournal' => TRUE]));
			$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

			$cPayment = InvoiceLib::getForAccounting($eFarm, $search, TRUE);

			if($cPayment->notEmpty()) {
				foreach($cPayment as $ePayment) {
					if(
						(empty($lastValidationDate) === FALSE and $lastValidationDate < $ePayment['paidAt']) or
						$ePayment->acceptAccountingImport() === FALSE
					) {
						continue;
					}
					[$fec, ] = AccountingLib::generatePaymentsFec(new \Collection([$ePayment]), $cAccount, TRUE);
					if(self::isFecDataImportable($fec)) {
						self::importPayment($eFinancialYear, $ePayment, $fec, $cAccount, $cPaymentMethod);
					}
				}
			}

			$cCash = \preaccounting\ImportLib::getCash($eFarm, $search);

			if($cCash->notEmpty()) {
				foreach($cCash as $eCash) {
					if(
						empty($lastValidationDate) === FALSE and $lastValidationDate < $ePayment['paidAt']
					) {
						continue;
					}
					[$fec, ] = AccountingLib::generateCashFec(new \Collection([$eCash]), $cAccount, $search);

					if(self::isFecDataImportable($fec)) {
						self::importCash($eFinancialYear, $eCash, $fec, $cAccount, $cPaymentMethod);
					}
				}

			}

		\selling\Payment::model()->commit();

	}

	private static function isFecDataImportable(array $fecData): bool {

		foreach($fecData as $line) {
			if(empty($line[AccountingLib::FEC_COLUMN_ACCOUNT_LABEL])) {
				return FALSE;
			}
		}

		return TRUE;
	}

	private static function importPayment(\account\FinancialYear $eFinancialYear, \selling\Payment $ePayment, array $fecData, \Collection $cAccount, \Collection $cPaymentMethod): void {

		$fw = new \FailWatch();

		$date = $ePayment['paidAt'];

		if(\account\FinancialYearLib::isDateInFinancialYear($date, $eFinancialYear) === FALSE) {
			\Fail::log('preaccounting\Import::importNotBelongsToFinancialYear');
			return;
		}

		$fw->validate();

		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;

		\selling\Payment::model()->beginTransaction();

			$eCustomer = match($ePayment['source']) {
				\selling\Payment::INVOICE => $ePayment['invoice']['customer'],
				\selling\Payment::SALE => $ePayment['sale']['customer'],
			};

			$eThirdParty = self::getOrCreateThirdParty($eCustomer);

			$eOperationBase = new \journal\Operation([
				'thirdParty' => $eThirdParty,
				'hash' => $hash,
			]);

			self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperationBase, $ePayment['cashflow']);

			\selling\PaymentAccountingLib::setImported($ePayment, $hash);
			\bank\Cashflow::model()->update($ePayment['cashflow'], ['status' => \bank\Cashflow::ALLOCATED, 'hash' => $hash]);

		\selling\Payment::model()->commit();

	}

	private static function importCash(\account\FinancialYear $eFinancialYear, \cash\Cash $eCash, array $fecData, \Collection $cAccount, \Collection $cPaymentMethod): void {

		$fw = new \FailWatch();

		$date = $eCash['date'];

		if(\account\FinancialYearLib::isDateInFinancialYear($date, $eFinancialYear) === FALSE) {
			\Fail::log('preaccounting\Import::importNotBelongsToFinancialYear');
			return;
		}

		$fw->validate();

		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;

		\selling\Payment::model()->beginTransaction();

			$eCustomer = $eCash['customer'];

			if($eCustomer->notEmpty()) {
				$eThirdParty = self::getOrCreateThirdParty($eCustomer);
			} else {
				$eThirdParty = new \account\ThirdParty();
			}

			$eOperationBase = new \journal\Operation([
				'thirdParty' => $eThirdParty,
				'hash' => $hash,
			]);

			if($eCash['cashflow']->notEmpty()) {
				$eCashflow = $eCash['cashflow'];
				\bank\Cashflow::model()->update($eCashflow, ['status' => \bank\Cashflow::ALLOCATED, 'hash' => $hash]);
			} else {
				$eCashflow = new \bank\Cashflow();
			}

			self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperationBase, $eCashflow);

			if($eCash['payment']->exists()) {
				\selling\PaymentAccountingLib::setImported($eCash['payment'], $hash);
			}
			if($eCash['invoice']->exists()) {
				\selling\PaymentAccountingLib::setImportedByExternal($eCash['invoice'], $hash);
			}
			if($eCash['sale']->exists()) {
				\selling\PaymentAccountingLib::setImportedByExternal($eCash['sale'], $hash);
			}
			\cash\Cash::model()->update($eCash, ['accountingHash' => $hash]);

		\selling\Payment::model()->commit();

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

	private static function createOperations(
		\account\FinancialYear $eFinancialYear,
		array $fecData,
		\Collection $cAccount,
		\Collection $cPaymentMethod,
		\journal\Operation $eOperationBase,
		\bank\Cashflow $eCashflow,
	): void {

		$cOperation = new \Collection();

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

			$eJournalCode = ($eAccount->notEmpty() and $eAccount['journalCode']->notEmpty()) ? \journal\JournalCodeLib::ask($eAccount['journalCode']['id']) : new \journal\JournalCode();

			$date = mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 0, 4).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], 4, 2).'-'.mb_substr($data[\preaccounting\AccountingLib::FEC_COLUMN_DATE], -2);
			$ePaymentMethod = $cPaymentMethod->find(fn($e) => $e['name'] === $data[\preaccounting\AccountingLib::FEC_COLUMN_PAYMENT_METHOD])->first();

			if(\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::BANK_ACCOUNT_CLASS) === FALSE) {
				$vatRule = \account\AccountUi::getVatRuleByAccount($eAccount, $eFinancialYear);
			} else {
				$vatRule = NULL;
			}

			$fw = new \FailWatch();

			$eOperation = new \journal\Operation();

			$eOperation->build([
				'financialYear', 'date',
				'account', 'accountLabel',
				'description', 'journalCode',
				'document', 'documentDate',
				'amount', 'type',
				'vatRate', 'vatAccount', 'vatRule',
				'operation', 'paymentDate', 'paymentMethod',
				'thirdParty', 'hash',
			], [
				'financialYear' => $eFinancialYear['id'],
				'date' => $date,
				'account' => $eAccount['id'],
				'accountLabel' => $data[\preaccounting\AccountingLib::FEC_COLUMN_ACCOUNT_LABEL],
				'description' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DESCRIPTION],
				'journalCode' => $eJournalCode['id'] ?? NULL,
				'document' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DOCUMENT],
				'documentDate' => $date,
				'amount' => abs($data[\preaccounting\AccountingLib::FEC_COLUMN_DEVISE_AMOUNT]),
				'type' => $data[\preaccounting\AccountingLib::FEC_COLUMN_DEBIT] > 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'vatRate' => 0.0,
				'vatAccount' => NULL,
				'operation' => NULL,
				'paymentDate' => $date,
				'paymentMethod' => $ePaymentMethod['id'] ?? NULL,
				'vatRule' => $vatRule,
				'thirdParty' => $eOperationBase['thirdParty']['id'] ?? NULL,
				'hash' => $eOperationBase['hash'],
			]);

			$fw->validate();

			// On essaie de rattacher les opérations liées (type TVA) à leurs copines
			if(
				$data[\preaccounting\AccountingLib::FEC_COLUMN_NUMBER] !== NULL and
				strpos($data[\preaccounting\AccountingLib::FEC_COLUMN_NUMBER], '-') !== FALSE
			) {

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

			if($eCashflow->notEmpty()) {

				$eOperationCashflow = new \journal\OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'amount' => min($eOperation['amount'], abs($eCashflow['amount'])),
				]);

				\journal\OperationCashflow::model()->insert($eOperationCashflow);

			}

			$cOperation->offsetSet($offset, $eOperation);

		}

	}

}

