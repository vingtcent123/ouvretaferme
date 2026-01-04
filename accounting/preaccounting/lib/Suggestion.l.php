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
				'invoice' => ['id', 'name', 'customer' => ['id', 'name', 'type', 'legalName'], 'priceIncludingVat', 'date'],
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
				->whereInvoice($eSuggestion['invoice'] ?? new \selling\Invoice())
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
					'invoice' => ['id', 'name', 'document', 'customer' => ['id', 'name']],
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

		$methodFqn = self::determinePaymentMethod($eCashflow['memo']);
		if($methodFqn !== NULL) {
			$eMethod = \payment\MethodLib::getByFqn($methodFqn);
		} else {
			$eMethod = new \payment\Method();
		}

		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'priceIncludingVat', 'name', 'customer' => ['id', 'name', 'legalName'], 'date',
				'thirdParty' => \account\ThirdParty::model()
					->select('id', 'name', 'normalizedName')
					->delegateCollection('customer'),
				'paymentMethod' => ['id', 'fqn'],
			])
			->whereFarm($eFarm)
			->whereStatus('!=', \selling\Invoice::DRAFT)
			->where('priceIncludingVat BETWEEN '.($eCashflow['amount'] - 1).' AND '.($eCashflow['amount'] + 1))
			->where(new \Sql('date <= '.\selling\Invoice::model()->format(date('Y-m-d', strtotime($eCashflow['date'].' + 1 MONTH')))))
			->whereCashflow('=', NULL)
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			list($weight, $reason) = self::weightCashflowInvoice($eCashflow, $eInvoice);

			if($weight > 50) {

				self::createSuggestion(
					new Suggestion([
						'invoice' => $eInvoice,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eMethod,
					]),
					$eCashflow['amount'] === $eInvoice['priceIncludingVat']
				);
			}

		}

		\bank\Cashflow::model()->update($eCashflow, ['isSuggestionCalculated' => TRUE]);

	}

	public static function weightCashflowInvoice(\bank\Cashflow $eCashflow, \selling\Invoice $eInvoice): array {

		$weight = \account\ThirdPartyLib::scoreNameMatch($eInvoice['customer']['legalName'] ?? $eInvoice['customer']['name'], $eCashflow['name']);

		if(abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) > self::AMOUNT_DIFFERENCE_MAX) {
			return [0, new \Set()];
		}

		if(
			($eCashflow['amount'] < 0 and $eInvoice['priceIncludingVat'] > 0) or
			($eCashflow['amount'] > 0 and $eInvoice['priceIncludingVat'] < 0)
		) {
			return [0, new \Set()];
		}

		$reason = new \Set();

		if($weight > 0) {
			$reason->value(Suggestion::THIRD_PARTY, TRUE);
		}

		$score = InvoiceLib::scoreInvoiceReference($eInvoice['name'], $eCashflow['memo']);
		if($score > 250 or mb_strpos(mb_strtolower($eCashflow['memo']), mb_strtolower($eInvoice['name'])) !== FALSE) {
			$weight += 100;
			$reason->value(Suggestion::REFERENCE, TRUE);
		}

		if(abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.05) { // 5 cts d'écart

			$weight += 100;
			$reason->value(Suggestion::AMOUNT, TRUE);

		} else if( // On autorise l'ouverture du montant à 50cts d'écart que si on a déjà le tiers ou la référence.
			($reason->get() & Suggestion::REFERENCE or $reason->get() & Suggestion::THIRD_PARTY) and
			abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.5
		) {

			$weight += 50;
			$reason->value(Suggestion::AMOUNT, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eInvoice['date'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(Suggestion::DATE, TRUE);

			}

		}

		if($eInvoice['paymentMethod']->notEmpty()) {

			$fqn = self::determinePaymentMethod($eCashflow);
			if($fqn === $eInvoice['paymentMethod']['fqn']) {

					$weight += 80;
					$reason->value(Suggestion::PAYMENT_METHOD, TRUE);

			}

		}
		return [$weight, $reason];
	}

	private static function countReasons(\Set $reasons): int {

		$count = 0;

		foreach(Suggestion::model()->getPropertySet('reason') as $reason) {
			if($reasons->value($reason)) {
				$count++;
			}
		}

		return $count;
	}

}
