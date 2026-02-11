<?php
namespace preaccounting;

Class SuggestionLib extends SuggestionCrud {

	const AMOUNT_DIFFERENCE_MAX = 5;
	const REASON_MIN = 2; // Au moins 2 critères donnent quelque chose

	public static function countWaiting(): int {

		return Suggestion::model()
			->select(['nCashflow' => new \Sql('COUNT(DISTINCT(cashflow))', 'int')])
			->whereStatus(Suggestion::WAITING)
			->get()['nCashflow'] ?? 0;

	}

	public static function getAllWaitingGroupByCashflow(): \Collection {

		return Suggestion::model()
			->select([
				'id', 'status',
				'cashflow' => ['id', 'name', 'memo', 'type', 'date', 'amount'],
				'invoice' => ['id', 'number', 'document', 'date', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']],
				'payment' => \selling\Payment::getSelection() + [
					'invoice' => ['id', 'number', 'document', 'date', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']],
					'sale' => ['id', 'document', 'deliveredAt', 'priceIncludingVat', 'customer' => ['id', 'legalName', 'name', 'type']]
				],
				'weight', 'reason',
				'paymentMethod' => ['id', 'fqn', 'name'],
			])
			->whereStatus(Suggestion::WAITING)
			->getCollection(NULL, NULL, ['cashflow', NULL]);

	}

	public static function ignore(Suggestion $eSuggestion): void {

		Suggestion::model()
			->whereId($eSuggestion['id'])
			->whereStatus(Suggestion::WAITING)
			->update(['status' => Suggestion::REJECTED]);

	}

	public static function ignoreCollection(\Collection $cSuggestion): void {

		Suggestion::model()->beginTransaction();

		$updated = Suggestion::model()
			->whereId('IN', $cSuggestion->getIds())
			->whereStatus(Suggestion::WAITING)
			->update(['status' => Suggestion::REJECTED]);

		if($updated === count($cSuggestion)) {

			Suggestion::model()->commit();

		} else {

			throw new \NotExpectedAction('Unable to ignore collection');

		}

	}

	public static function determinePaymentMethod(string $detail): ?string {

		$words = [
			\payment\MethodLib::TRANSFER => 'virement',
			\payment\MethodLib::DIRECT_DEBIT => 'prelevement',
			\payment\MethodLib::CHECK => 'cheque',
		];

		$winner = NULL;
		foreach($words as $fqn => $word) {

			if(mb_strpos(mb_strtolower($detail), $word) !== FALSE) {
				$winner = $fqn;
			}

		}

		return $winner;

	}

	public static function createSuggestion(Suggestion $eSuggestion, bool $exactAmount): void {

		if(
			($eSuggestion['reason']->get() & Suggestion::THIRD_PARTY) === FALSE and
			($eSuggestion['reason']->get() & Suggestion::REFERENCE) === FALSE and
			$exactAmount === FALSE
		) {
			return;
		}

		try {

			Suggestion::model()->insert($eSuggestion);

		} catch (\DuplicateException) {

			Suggestion::model()
				->whereStatus(Suggestion::WAITING)
				->whereCashflow($eSuggestion['cashflow'])
				->wherePayment($eSuggestion['payment'] ?? new \selling\Payment())
				->update($eSuggestion->extracts(['reason', 'weight']));

		}

	}

	public static function calculateSuggestionsByFarm(\farm\Farm $eFarm): void {

		Suggestion::model()->beginTransaction();

		$cImport = \bank\Import::model()
			->select('id')
			->whereReconciliation(\bank\Import::WAITING)
			->getCollection();

		foreach($cImport as $eImport) {

			$updated = \bank\Import::model()->update($eImport, ['reconciliation' => \bank\Import::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			$cCashflow = \bank\Cashflow::model()
				->select(\bank\Cashflow::getSelection() + [
					'cOperationCashflow' =>
						\journal\OperationCashflow::model()->select(['operation'])->delegateCollection('cashflow'),
				])
				->whereImport($eImport)
				->whereIsReconciliated(FALSE)
				->getCollection();

			foreach($cCashflow as $eCashflow) {
				self::calculateForCashflow($eFarm, $eCashflow);
			}

			\bank\Import::model()->update($eImport, ['reconciliation' => \bank\Import::DONE]);
		}

		Suggestion::model()->commit();

	}

	public static function calculateForCashflow(\farm\Farm $eFarm, \bank\Cashflow $eCashflow): void {

		$customerSelection = ['id', 'legalName', 'name'];

		$methodFqn = self::determinePaymentMethod($eCashflow->getMemo());
		if($methodFqn !== NULL) {
			$eMethod = \payment\MethodLib::getByFqn($methodFqn);
		} else {
			$eMethod = new \payment\Method();
		}

		$notPaidConditions = [
			'm1.paidAt IS NULL',
			'm1.status = "'.\selling\Payment::NOT_PAID.'"',
			'm2.id IS NOT NULL',
			'm2.priceIncludingVat BETWEEN '.($eCashflow['amount'] - 1).' AND '.($eCashflow['amount'] + 1),
			'm2.date <= '.\selling\Payment::model()->format(date('Y-m-d', strtotime($eCashflow['date'].' + 1 MONTH'))),
		];
		$paidConditions = [
			'm1.paidAt IS NOT NULL',
			'm1.status = "'.\selling\Payment::PAID.'"',
			'm1.paidAt <= '.\selling\Payment::model()->format(date('Y-m-d', strtotime($eCashflow['date'].' + 1 MONTH'))).'',
			'amountIncludingVat BETWEEN '.($eCashflow['amount'] - 1).' AND '.($eCashflow['amount'] + 1),
		];

		// Par rapport aux paiements
		$cPayment = \selling\Payment::model()
			->select(\selling\Payment::getSelection() + [
				'invoice' => ['id', 'priceIncludingVat', 'number', 'date', 'customer' => $customerSelection]
			])
			->join(\selling\Invoice::model(), 'm1.invoice = m2.id', 'LEFT')
			->where('m1.farm = '.$eFarm['id'])
			->or(
				fn() => $this->where(new \Sql(join(' AND ', $notPaidConditions))),
				//fn() => $this->where(new \Sql(join(' AND ', $paidConditions)))
			)
			->where('m1.accountingHash IS NULL')
			->whereSource(\selling\Payment::INVOICE)
			->where('m1.invoice IS NOT NULL') // Rapprochement que sur factures pour le moment
			->where('m1.cashflow IS NULL')
			->getCollection();

		// Par rapport aux restants de factures
		$cInvoice = \selling\Invoice::model()
			->select(
				['id', 'priceIncludingVat', 'paymentAmount', 'number', 'date', 'customer' => $customerSelection]
			)
			->whereFarm($eFarm)
			->where('priceIncludingVat - paymentAmount BETWEEN '.($eCashflow['amount'] - 1).' AND '.($eCashflow['amount'] + 1))
			->where(new \Sql('date <= '.\selling\Payment::model()->format(date('Y-m-d', strtotime($eCashflow['date'].' + 1 MONTH'))).'',
			'amountIncludingVat BETWEEN '.($eCashflow['amount'] - 1).' AND '.($eCashflow['amount'] + 1)))
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			$cPayment->append(new \selling\Payment([
				'invoice' => $eInvoice,
				'status' => \selling\Payment::NOT_PAID,
				'amountIncludingVat' => round($eInvoice['priceIncludingVat'] - $eInvoice['paymentAmount'], 2),
				'method' => new \payment\Method(),
			]));

		}

		foreach($cPayment as $ePayment) {

			list($weight, $reason) = self::weightCashflow($eCashflow, $ePayment);

			if($weight > 50) {

				self::createSuggestion(
					new Suggestion([
						'payment' => $ePayment,
						'invoice' => $ePayment['invoice'],
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eMethod,
					]),
					$eCashflow['amount'] === ($ePayment['amountIncludingVat'] ?? $ePayment['invoice']['priceIncludingVat'])
				);
			}

		}

		\bank\Cashflow::model()->update($eCashflow, ['isSuggestionCalculated' => TRUE]);

	}

	public static function weightCashflow(\bank\Cashflow $eCashflow, \selling\Payment $ePayment): array {

		$eInvoice = $ePayment['invoice'];

		if($eInvoice['customer']->notEmpty()) {
			$weight = \account\ThirdPartyLib::scoreNameMatch($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name'], $eCashflow['name']);
		} else {
			$weight = 0;
		}

		if(abs(abs($ePayment['amountIncludingVat'] ?? $eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) > self::AMOUNT_DIFFERENCE_MAX) {
			return [0, new \Set()];
		}

		if(
			($eCashflow['amount'] < 0 and ($ePayment['amountIncludingVat'] ?? $eInvoice['priceIncludingVat']) > 0) or
			($eCashflow['amount'] > 0 and ($ePayment['amountIncludingVat'] ?? $eInvoice['priceIncludingVat']) < 0)
		) {
			return [0, new \Set()];
		}

		$reason = new \Set();

		if($weight > 0) {
			$reason->value(Suggestion::THIRD_PARTY, TRUE);
		}

		$score = SuggestionInvoiceLib::scoreInvoiceReference($eInvoice['number'], $eCashflow->getMemo());
		if($score > 250 or mb_strpos(mb_strtolower($eCashflow->getMemo()), mb_strtolower($eInvoice['number'])) !== FALSE) {
			$weight += 100;
			$reason->value(Suggestion::REFERENCE, TRUE);
		}

		if(abs(abs(($ePayment['amountIncludingVat'] ?? $eInvoice['priceIncludingVat'])) - abs($eCashflow['amount'])) < 0.05) { // 5 cts d'écart

			$weight += 100;
			$reason->value(Suggestion::AMOUNT, TRUE);

		} else if( // On autorise l'ouverture du montant à 50cts d'écart que si on a déjà le tiers ou la référence.
			($reason->get() & Suggestion::REFERENCE or $reason->get() & Suggestion::THIRD_PARTY) and
			abs(abs(($ePayment['amountIncludingVat'] ?? $eInvoice['priceIncludingVat'])) - abs($eCashflow['amount'])) < 0.5
		) {

			$weight += 50;
			$reason->value(Suggestion::AMOUNT, TRUE);

		}

		if($weight > 0) {

			if($ePayment['status'] === \selling\Payment::PAID) {
				$date = $ePayment['paidAt'];
			} else {
				$date = $eInvoice['date'];
			}

			$interval = abs((int)(\util\DateLib::interval($date, $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(Suggestion::DATE, TRUE);

			}

		}

		$fqn = self::determinePaymentMethod($eCashflow);

		if($ePayment['method']->notEmpty() and $fqn === $ePayment['method']['fqn']) {

			$weight += 80;
			$reason->value(Suggestion::PAYMENT_METHOD, TRUE);

		}
		return [$weight, $reason];
	}

}
