<?php
namespace preaccounting;

Class SuggestionLib extends SuggestionCrud {

	const AMOUNT_DIFFERENCE_MAX = 5;
	const REASON_MIN = 2; // Au moins 2 critères donnent quelque chose

	public static function countWaiting(): array {

		$countForOperations = Suggestion::model()
			->select([new \Sql('DISTINCT(cashflow)')])
			->whereStatus(Suggestion::WAITING)
			->whereOperation('!=', NULL)
			->getCollection()
			->count();

		$countForSales = Suggestion::model()
			->select([new \Sql('DISTINCT(cashflow)')])
			->whereStatus(Suggestion::WAITING)
			->whereOperation(NULL)
			->getCollection()
			->count();

		return ['operations' => $countForOperations, 'sales' => $countForSales];
	}

	public static function getAllWaitingGroupByOperation(): \Collection {

		return Suggestion::model()
			->select([
				'id',
				'cashflow' => ['id', 'name', 'memo', 'type', 'date', 'amount'],
				'operation' => [
					'id', 'date', 'description', 'accountLabel',
					'account' => ['id', 'class', 'description'],
					'amount', 'type',
					'thirdParty' => ['id', 'name', 'clientAccountLabel'],
					'cOperationLinked' => new \journal\OperationModel()
						->select('id', 'type', 'amount')
						->delegateCollection('operation'),
				],
				'weight', 'reason',
			])
			->whereStatus(Suggestion::WAITING)
			->whereOperation('!=', NULL)
			->getCollection(NULL, NULL, ['operation', NULL]);

	}

	public static function countWaitingByInvoice(): int {

		return (Suggestion::model()
			->select(['nInvoice' => new \Sql('COUNT(DISTINCT(invoice))')])
			->whereInvoice('!=', NULL)
			->whereStatus(Suggestion::WAITING)
			->get()['nInvoice'] ?? 0);

	}

	public static function countWaitingBySale(): int {

		return (Suggestion::model()
			->select(['nSale' => new \Sql('COUNT(DISTINCT(sale))')])
			->whereSale('!=', NULL)
			->whereStatus(Suggestion::WAITING)
			->get()['nSale'] ?? 0);

	}

	public static function countWaitingByOperation(): int {

		return (Suggestion::model()
			->select(['nOperation' => new \Sql('COUNT(DISTINCT(operation))')])
			->whereOperation('!=', NULL)
			->whereStatus(Suggestion::WAITING)
			->get()['nOperation'] ?? 0);

	}

	public static function countWaitingByCashflow(): int {

		return Suggestion::model()
			->select(['nCashflow' => new \Sql('DISTINCT(cashflow)')])
			->or(
				fn() => $this->whereInvoice('!=', NULL),
				fn() => $this->whereSale('!=', NULL),
			)
			->whereStatus(Suggestion::WAITING)
			->count();

	}

	public static function getAllWaitingGroupByCashflow(): \Collection {

		return Suggestion::model()
			->select([
				'id', 'status',
				'cashflow' => ['id', 'name', 'memo', 'type', 'date', 'amount'],
				'invoice' => ['id', 'name', 'customer' => ['id', 'name'], 'priceIncludingVat', 'date'],
				'sale' => ['id', 'customer' => ['id', 'name'], 'priceIncludingVat', 'deliveredAt', 'document'],
				'weight', 'reason',
				'paymentMethod' => ['id', 'fqn', 'name'],
			])
			->whereStatus(Suggestion::WAITING)
			->whereOperation(NULL)
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

	public static function createSuggestion(Suggestion $eSuggestion): void {

		if(
			($eSuggestion['reason']->get() & Suggestion::THIRD_PARTY) === FALSE and
			($eSuggestion['reason']->get() & Suggestion::REFERENCE) === FALSE
		) {
			return;
		}

		try {

			Suggestion::model()->insert($eSuggestion);

		} catch (\DuplicateException) {

			Suggestion::model()
				->select('reason', 'weight') // On met à jour raison et poids mais pas le statut et uniquement pour les suggestions en attente de traitement.
				->whereStatus(Suggestion::WAITING)
				->whereCashflow($eSuggestion['cashflow'])
				->whereInvoice($eSuggestion['invoice'] ?? new \selling\Invoice())
				->whereSale($eSuggestion['sale'] ?? new \selling\Sale())
				->whereOperation($eSuggestion['operation'] ?? new \journal\Operation())
				->update($eSuggestion);

		}

	}

	public static function calculateSuggestionsByFarm(\farm\Farm $eFarm): void {

		Suggestion::model()->beginTransaction();

		$cImport = \bank\Import::model()
			->select('id')
			->whereReconciliation(\bank\Import::WAITING)
			->getCollection();

		d($eFarm['id'].' : '.$cImport->count());
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
					'sale' => ['id', 'document', 'customer' => ['id', 'name']],
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

		// OPÉRATIONS \\
		$cThirdParty = \account\ThirdParty::model()
			->select('id', 'name', 'memos', 'normalizedName')
			->getCollection(NULL, NULL, 'id');

		$cThirdParty = \account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow)->filter(fn($e) => $e['weight'] > 50);

		$methodFqn = self::determinePaymentMethod($eCashflow['memo']);
		if($methodFqn !== NULL) {
			$eMethod = \payment\MethodLib::getByFqn($methodFqn);
		} else {
			$eMethod = new \payment\Method();
		}

		// Par le tiers
		$cOperationThirdParty = \journal\Operation::model()
			->select(['id', 'amount', 'thirdParty', 'date', 'description', 'type', 'paymentMethod' => ['id', 'fqn']])
			->whereThirdParty('IN', $cThirdParty)
			->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
			->where(new \Sql('letteringStatus IS NULL OR letteringStatus = '.\journal\Operation::model()->format(\journal\Operation::PARTIAL)))
			->whereDate('<=', $eCashflow['date'])
			->getCollection();

		foreach($cOperationThirdParty as $eOperation) {

			$eOperation['thirdParty'] = $cThirdParty->offsetGet($eOperation['thirdParty']['id']);
			list($weight, $reason) = self::weightCashflowOperation($eCashflow, $eOperation);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

				self::createSuggestion(
					new Suggestion([
						'operation' => $eOperation,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eMethod,
					])
				);

			}

		}

		// Par le montant exact
		$cOperation = \journal\Operation::model()
			->select(['id', 'amount', 'thirdParty', 'date'])
			->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
			->where(new \Sql('letteringStatus IS NULL OR letteringStatus = '.\journal\Operation::model()->format(\journal\Operation::PARTIAL)))
			->where(new \Sql('ROUND(amount, 2) = ROUND('.abs($eCashflow['amount']).', 2) '))
			->whereType($eCashflow['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT)
			->whereDate('<=', $eCashflow['date'])
			->getCollection();

		foreach($cOperation as $eOperation) {

			$reason = new \Set();
			$reason->value(Suggestion::AMOUNT, TRUE);
			$weight = 50;

			$interval = abs((int)(\util\DateLib::interval($eOperation['date'], $eCashflow['date']) / 60 / 60 / 24));
			if($interval < 30) {
				$weight += 80;
				$reason->value(Suggestion::DATE, TRUE);
			}

			if(self::countReasons($reason) > self::REASON_MIN) {

				self::createSuggestion(
					new Suggestion([
						'operation' => $eOperation,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eMethod,
					])
				);
			}

		}

		// FACTURES \\
		$foundInvoice = FALSE;
		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'priceIncludingVat', 'name', 'customer' => ['id', 'name'], 'date',
				'thirdParty' => \account\ThirdParty::model()
					->select('id', 'name', 'normalizedName')
					->delegateCollection('customer'),
				'paymentMethod' => ['id', 'fqn'],
			])
			->whereFarm($eFarm)
			->highlight()
			->where('priceIncludingVat BETWEEN '.($eCashflow['amount'] - 10).' AND '.($eCashflow['amount'] + 10))
			->wherePaymentStatus(\selling\Invoice::NOT_PAID)
			->whereDate('<=', $eCashflow['date'])
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			list($weight, $reason) = self::weightCashflowInvoice($eCashflow, $eInvoice);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

				$foundInvoice = TRUE;
				self::createSuggestion(
					new Suggestion([
						'invoice' => $eInvoice,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eMethod,
					])
				);
			}

		}

		// VENTES \\
		if($foundInvoice === FALSE) {

			$cSale = \selling\Sale::model()
				->select([
					'id', 'priceIncludingVat', 'customer' => ['id', 'name'], 'deliveredAt',
					'thirdParty' => \account\ThirdParty::model()
						->select('id', 'name', 'normalizedName')
						->delegateCollection('customer'),
				  'cPayment' => \selling\Payment::model()
						->select(\selling\Payment::getSelection())
						->or(
							fn() => $this->whereOnlineStatus(NULL),
							fn() => $this->whereOnlineStatus(\selling\Payment::SUCCESS)
						)
						->delegateCollection('sale'),
				])
				->whereFarm($eFarm)
				->where('priceIncludingVat BETWEEN '.($eCashflow['amount'] - 10).' AND '.($eCashflow['amount'] + 10))
				->wherePaymentStatus(\selling\Invoice::NOT_PAID)
				->whereInvoice(NULL)
				->whereProfile('IN', [\selling\Sale::SALE, \selling\Sale::MARKET])
				->whereDeliveredAt('<=', $eCashflow['date'])
				->getCollection();

			foreach($cSale as $eSale) {

				list($weight, $reason) = self::weightCashflowSale($eCashflow, $eSale);

				if($weight > 50) {
					$foundSale = TRUE;
					self::createSuggestion(
						new Suggestion([
							'sale' => $eSale,
							'cashflow' => $eCashflow,
							'reason' => $reason,
							'weight' => $weight,
							'paymentMethod' => $eMethod,
						])

					);
				}

			}

		}
	}

	public static function calculateForOperation(\journal\Operation $eOperation): void {

		$cCashflow = \bank\Cashflow::model()
			->select('id', 'name', 'memo', 'amount', 'date')
			->whereStatus(\bank\Cashflow::WAITING)
			->getCollection();

		foreach($cCashflow as $eCashflow) {

			list($weight, $reason) = self::weightCashflowOperation($eCashflow, $eOperation);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

				self::createSuggestion(
					new Suggestion([
						'operation' => $eOperation,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
						'paymentMethod' => $eOperation['paymentMethod'],
					])
				);

			}

		}

	}

	public static function weightCashflowOperation(\bank\Cashflow $eCashflow, \journal\Operation $eOperation): array {

		if(
			abs(abs($eOperation['amount']) - abs($eCashflow['amount'])) > 10 or  // + de 10€ d'écart
			$eCashflow['date'] < $eOperation['date'] // On ne paye avant.
		) {
			return [0, new \Set()];
		}

		if($eOperation['type'] === \journal\Operation::DEBIT) {
			if($eCashflow['amount'] < 0) {
				return [0, new \Set()];
			}
		}
		if($eOperation['type'] === \journal\Operation::CREDIT) {
			if($eCashflow['amount'] > 0) {
				return [0, new \Set()];
			}
		}

		$reason = new \Set();
		$weight = 0;

		if($eOperation['thirdParty']->notEmpty()) {

			$weight += \account\ThirdPartyLib::extractWeightByCashflow($eOperation['thirdParty'], $eCashflow);

			if($weight > 0) {
				$reason->value(Suggestion::THIRD_PARTY, TRUE);
			}

		}


		if(abs(abs($eOperation['amount']) - abs($eCashflow['amount'])) < 0.1) {

			$weight += 100;
			$reason->value(Suggestion::AMOUNT, TRUE);

		} else if(abs(abs($eOperation['amount']) - abs($eCashflow['amount'])) < 0.5) {

			$weight += 50;
			$reason->value(Suggestion::AMOUNT, TRUE);

		}

		if(mb_strpos(mb_strtolower($eCashflow['memo']), mb_strtolower($eOperation['description']))) {

			$weight += 100;
			$reason->value(Suggestion::REFERENCE, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eOperation['date'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(Suggestion::DATE, TRUE);


			}

		}

		if($eOperation['paymentMethod']->notEmpty()) {

			$fqn = self::determinePaymentMethod($eCashflow);
			if($fqn === $eOperation['paymentMethod']['fqn']) {

					$weight += 80;
					$reason->value(Suggestion::PAYMENT_METHOD, TRUE);

			}

		}

		return [$weight, $reason];
	}

	public static function weightCashflowInvoice(\bank\Cashflow $eCashflow, \selling\Invoice $eInvoice): array {

		$weight = \account\ThirdPartyLib::scoreNameMatch($eInvoice['customer']['name'], $eCashflow['name']);

		if(
			abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) > self::AMOUNT_DIFFERENCE_MAX or
			$eCashflow['date'] < $eInvoice['date'] // On ne paye avant.
		) {
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

		if(mb_strpos(mb_strtolower($eCashflow['memo']), mb_strtolower($eInvoice['name']))) {

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

	public static function weightCashflowSale(\bank\Cashflow $eCashflow, \selling\Sale $eSale): array {

		$weight = \account\ThirdPartyLib::scoreNameMatch($eSale['customer']['name'], $eCashflow['name']);

		if(
			abs(abs($eSale['priceIncludingVat']) - abs($eCashflow['amount'])) > self::AMOUNT_DIFFERENCE_MAX or  // + de 10€ d'écart
			$eCashflow['date'] < $eSale['deliveredAt'] // On ne paye avant.
		) {
			return [0, new \Set()];
		}

		if(
			($eCashflow['amount'] < 0 and $eSale['priceIncludingVat'] > 0) or
			($eCashflow['amount'] > 0 and $eSale['priceIncludingVat'] < 0)
		) {
			return [0, new \Set()];
		}

		$reason = new \Set();

		if($weight > 0) {
			$reason->value(Suggestion::THIRD_PARTY, TRUE);
		}

		if(abs(abs($eSale['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.05) { // 5cts

			$weight += 100;
			$reason->value(Suggestion::AMOUNT, TRUE);

		} else if(abs(abs($eSale['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.1) { // 10 cts

			$weight += 50;
			$reason->value(Suggestion::AMOUNT, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eSale['deliveredAt'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(Suggestion::DATE, TRUE);

			}

		}

		$fqn = self::determinePaymentMethod($eCashflow);

		foreach($eSale['cPayment'] as $ePayment) {

			if($fqn === $ePayment['method']['fqn']) {

				$weight += 80;
				$reason->value(Suggestion::PAYMENT_METHOD, TRUE);

			}
		}

		// Il n'y a pas le montant et on en a au moins 2 ou alors le montant est exact (à 5ct près et on en a au moins 2)
		if(($reason->get() & Suggestion::AMOUNT === FALSE) and self::countReasons($reason) > self::REASON_MIN) {
			return [$weight, $reason];
		}

		// Ou alors s'il y a le montant pas exact il en faut au moins 3 dont le tiers et la référence
		if(
			($reason->get() & Suggestion::AMOUNT) and
			(($reason->get() & Suggestion::THIRD_PARTY) or ($reason->get() & Suggestion::REFERENCE)) and
			(
				self::countReasons($reason) > (self::REASON_MIN + 1) or
				(self::countReasons($reason) > self::REASON_MIN and abs(abs($eCashflow['amount']) - abs($eSale['priceIncludingVat']) < 0.05))
			)
		) {
			return [$weight, $reason];
		}

		return [0, new \Set()];
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
