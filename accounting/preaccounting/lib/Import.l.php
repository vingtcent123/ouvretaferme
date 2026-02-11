<?php
namespace preaccounting;

Class ImportLib {

	public static function getPayments(\farm\Farm $eFarm, \Search $search): \Collection {

		$eFarm->expects(['cFinancialYear']);

		$cAccount = \account\AccountLib::getAll(new \Search(['withVat' => TRUE, 'withJournal' => TRUE]));

		$cPayment = PaymentLib::getForAccounting($eFarm, $search, TRUE);

		if($cPayment->empty()) {
			return new \Collection();
		}

		[$fec, ] = AccountingLib::generatePaymentsFec($cPayment, $eFarm['cFinancialYear'], $cAccount, TRUE);

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

		\selling\Payment::model()->update($ePayment, ['readyForAccounting' => NULL]);

	}
	public static function ignorePayments(\Collection $cPayment): void {

		\selling\Payment::model()
			->whereId('IN', $cPayment->getIds())
			->update(['readyForAccounting' => NULL]);

	}

	public static function getPaymentById(int $id): \selling\Payment {

		return \selling\PaymentLib::getById($id, PaymentLib::getPaymentSelection());

	}

	public static function getPaymentsByIds(array $ids): \Collection {

		return \selling\PaymentLib::getByIds($ids, PaymentLib::getPaymentSelection());

	}

	public static function importPayments(\farm\Farm $eFarm, \Collection $cPayment): void {

		foreach($cPayment as $ePayment) {

			self::importPayment($eFarm, $ePayment);

		}

	}

	public static function importPayment(\farm\Farm $eFarm, \selling\Payment $ePayment): void {

		$eFarm->expects(['eFinancialYear']);


		if($ePayment->acceptAccountingImport() === FALSE or $ePayment['readyForAccounting'] === FALSE) {
			return;
		}

		$fw = new \FailWatch();

		$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($ePayment['cashflow']['date']);

		if($eFinancialYear->empty()) {
			\Fail::log('Payment::importNoFinancialYear');
			return;
		}

		if(\account\FinancialYearLib::isDateInFinancialYear($ePayment['cashflow']['date'], $eFinancialYear) === FALSE) {
			\Fail::log('Payment::importNotBelongsToFinancialYear');
			return;
		}

		$fw->validate();

		$cAccount = \account\AccountLib::getAll(new \Search(['withVat' => TRUE, 'withJournal' => TRUE]));
		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE;
		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, NULL, FALSE);

		\selling\Invoice::model()->beginTransaction();

		$eCustomer = match($ePayment['source']) {
			\selling\Payment::INVOICE => $ePayment['invoice']['customer'],
			\selling\Payment::SALE => $ePayment['sale']['customer'],
		};

		$eThirdParty = self::getOrCreateThirdParty($eCustomer);

		[$fecData, ] = \preaccounting\AccountingLib::generatePaymentsFec(new \Collection([$ePayment]), new \Collection([$eFinancialYear]), $cAccount, TRUE);

		$eOperationBase = new \journal\Operation([
			'thirdParty' => $eThirdParty,
			'hash' => $hash,
		]);

		self::createOperations($eFinancialYear, $fecData, $cAccount, $cPaymentMethod, $eOperationBase, $ePayment);

		\selling\Payment::model()->update($ePayment, ['accountingHash' => $hash]);
		\bank\Cashflow::model()->update($ePayment['cashflow'], ['status' => \bank\Cashflow::ALLOCATED, 'hash' => $hash]);

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

	private static function createOperations(
		\account\FinancialYear $eFinancialYear,
		array $fecData,
		\Collection $cAccount,
		\Collection $cPaymentMethod,
		\journal\Operation $eOperationBase,
		\selling\Payment $ePayment,
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

			if($ePayment->notEmpty() and $ePayment['cashflow']->notEmpty()) {

				$eOperationCashflow = new \journal\OperationCashflow([
					'operation' => $eOperation,
					'cashflow' => $ePayment['cashflow'],
					'amount' => min($eOperation['amount'], abs($ePayment['cashflow']['amount'])),
				]);

				\journal\OperationCashflow::model()->insert($eOperationCashflow);

			}

			$cOperation->offsetSet($offset, $eOperation);

		}

	}

}

