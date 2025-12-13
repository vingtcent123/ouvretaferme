<?php
namespace preaccounting;

Class SuggestionLib extends \preaccounting\SuggestionCrud {

	const AMOUNT_DIFFERENCE_MAX = 5;
	const REASON_MIN = 2; // Au moins 2 critères donnent quelque chose

	public static function countWaiting(): array {

		$countForOperations = \preaccounting\Suggestion::model()
			->select([new \Sql('DISTINCT(cashflow)')])
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->whereOperation('!=', NULL)
			->getCollection()
			->count();

		$countForSales = \preaccounting\Suggestion::model()
			->select([new \Sql('DISTINCT(cashflow)')])
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->whereOperation(NULL)
			->getCollection()
			->count();

		return ['operations' => $countForOperations, 'sales' => $countForSales];
	}

	public static function getAllWaitingGroupByOperation(): \Collection {

		return \preaccounting\Suggestion::model()
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
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->whereOperation('!=', NULL)
			->getCollection(NULL, NULL, ['operation', NULL]);

	}

	public static function getAllWaitingGroupByCashflow(): \Collection {

		return \preaccounting\Suggestion::model()
			->select([
				'id',
				'cashflow' => ['id', 'name', 'memo', 'type', 'date', 'amount'],
				'invoice' => ['id', 'name', 'customer' => ['id', 'name'], 'priceIncludingVat', 'date'],
				'sale' => ['id', 'customer' => ['id', 'name'], 'priceIncludingVat', 'deliveredAt', 'document'],
				'weight', 'reason',
			])
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->whereOperation(NULL)
			->getCollection(NULL, NULL, ['cashflow', NULL]);

	}

	public static function ignore(\preaccounting\Suggestion $eSuggestion): void {

		\preaccounting\Suggestion::model()
			->whereId($eSuggestion['id'])
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->update(['status' => \preaccounting\Suggestion::REJECTED]);

	}

	public static function ignoreCollection(\Collection $cSuggestion): void {

		\preaccounting\Suggestion::model()->beginTransaction();

		$updated = \preaccounting\Suggestion::model()
			->whereId('IN', $cSuggestion->getIds())
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->update(['status' => \preaccounting\Suggestion::REJECTED]);

		if($updated === count($cSuggestion)) {

			\preaccounting\Suggestion::model()->commit();

		} else {

			throw new \NotExpectedAction('Unable to ignore collection');

		}

	}

	public static function calculateForCashflow(\farm\Farm $eFarm, \bank\Cashflow $eCashflow): void {

		// OPÉRATIONS \\
		$cThirdParty = \account\ThirdParty::model()
			->select('id', 'name', 'memos', 'normalizedName')
			->getCollection(NULL, NULL, 'id');

		$cThirdParty = \account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow)->filter(fn($e) => $e['weight'] > 50);

		// Par le tiers
		$cOperationThirdParty = \journal\Operation::model()
			->select(['id', 'amount', 'thirdParty', 'date', 'description', 'type'])
			->whereThirdParty('IN', $cThirdParty)
			->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
			->where(new \Sql('letteringStatus IS NULL OR letteringStatus = '.\journal\Operation::model()->format(\journal\Operation::PARTIAL)))
			->whereDate('<=', $eCashflow['date'])
			->getCollection();

		foreach($cOperationThirdParty as $eOperation) {

			$eOperation['thirdParty'] = $cThirdParty->offsetGet($eOperation['thirdParty']['id']);
			list($weight, $reason) = self::weightCashflowOperation($eCashflow, $eOperation);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

				$eSuggestion = new \preaccounting\Suggestion([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'reason' => $reason,
					'weight' => $weight,
				]);

				\preaccounting\Suggestion::model()->option('add-replace')->insert($eSuggestion);

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
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);
			$weight = 50;

			$interval = abs((int)(\util\DateLib::interval($eOperation['date'], $eCashflow['date']) / 60 / 60 / 24));
			if($interval < 30) {
				$weight += 80;
				$reason->value(\preaccounting\Suggestion::DATE, TRUE);
			}

			if(self::countReasons($reason) > self::REASON_MIN) {

				$eSuggestion = new \preaccounting\Suggestion([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'reason' => $reason,
					'weight' => $weight,
				]);

				\preaccounting\Suggestion::model()->option('add-replace')->insert($eSuggestion);
			}

		}

		// VENTES \\
		$cSale = \selling\Sale::model()
			->select([
				'id', 'priceIncludingVat', 'customer' => ['id', 'name'], 'deliveredAt',
				'thirdParty' => \account\ThirdParty::model()
					->select('id', 'name', 'normalizedName')
					->delegateCollection('customer')
			])
			->whereFarm($eFarm)
			->where('priceIncludingVat BETWEEN '.($eCashflow['amount'] - 10).' AND '.($eCashflow['amount'] + 10))
			//->whereReadyForAccounting(TRUE)
			//->whereClosed(TRUE)
			->wherePaymentStatus(\selling\Invoice::NOT_PAID)
			->whereInvoice(NULL)
			->whereProfile('IN', [\selling\Sale::SALE, \selling\Sale::MARKET])
			->whereAccountingHash(NULL)
			->whereDeliveredAt('<=', $eCashflow['date'])
			->getCollection();

		foreach($cSale as $eSale) {

			list($weight, $reason) = self::weightCashflowSale($eCashflow, $eSale);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

					$eSuggestion = new \preaccounting\Suggestion([
						'sale' => $eSale,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
					]);

					\preaccounting\Suggestion::model()->option('add-replace')->insert($eSuggestion);
			}

		}

		// FACTURES \\
		$cInvoice = \selling\Invoice::model()
			->select([
				'id', 'priceIncludingVat', 'name', 'customer' => ['id', 'name'], 'date',
				'thirdParty' => \account\ThirdParty::model()
					->select('id', 'name', 'normalizedName')
					->delegateCollection('customer')
			])
			->whereFarm($eFarm)
			->where('priceIncludingVat BETWEEN '.($eCashflow['amount'] - 10).' AND '.($eCashflow['amount'] + 10))
			//->whereReadyForAccounting(TRUE)
			//->whereClosed(TRUE)
			->wherePaymentStatus(\selling\Invoice::NOT_PAID)
			->whereAccountingHash(NULL)
			->whereDate('<=', $eCashflow['date'])
			->getCollection();

		foreach($cInvoice as $eInvoice) {

			list($weight, $reason) = self::weightCashflowInvoice($eCashflow, $eInvoice);

			if($weight > 50 and self::countReasons($reason) > self::REASON_MIN) {

					$eSuggestion = new \preaccounting\Suggestion([
						'invoice' => $eInvoice,
						'cashflow' => $eCashflow,
						'reason' => $reason,
						'weight' => $weight,
					]);

					\preaccounting\Suggestion::model()->option('add-replace')->insert($eSuggestion);
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

				$eSuggestion = new \preaccounting\Suggestion([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'reason' => $reason,
					'weight' => $weight,
				]);

				\preaccounting\Suggestion::model()->option('add-replace')->insert($eSuggestion);

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
				$reason->value(\preaccounting\Suggestion::THIRD_PARTY, TRUE);
			}

		}


		if(abs(abs($eOperation['amount']) - abs($eCashflow['amount'])) < 0.1) {

			$weight += 100;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		} else if(abs(abs($eOperation['amount']) - abs($eCashflow['amount'])) < 0.5) {

			$weight += 50;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		}

		if(mb_strpos(mb_strtolower($eCashflow['memo']), mb_strtolower($eOperation['description']))) {

			$weight += 100;
			$reason->value(\preaccounting\Suggestion::REFERENCE, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eOperation['date'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(\preaccounting\Suggestion::DATE, TRUE);

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
			$reason->value(\preaccounting\Suggestion::THIRD_PARTY, TRUE);
		}

		if(abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.1) {

			$weight += 100;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		} else if(abs(abs($eInvoice['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.5) {

			$weight += 50;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		}

		if(mb_strpos(mb_strtolower($eCashflow['memo']), mb_strtolower($eInvoice['name']))) {

			$weight += 100;
			$reason->value(\preaccounting\Suggestion::REFERENCE, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eInvoice['date'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(\preaccounting\Suggestion::DATE, TRUE);

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
			$reason->value(\preaccounting\Suggestion::THIRD_PARTY, TRUE);
		}

		if(abs(abs($eSale['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.1) {

			$weight += 100;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		} else if(abs(abs($eSale['priceIncludingVat']) - abs($eCashflow['amount'])) < 0.5) {

			$weight += 50;
			$reason->value(\preaccounting\Suggestion::AMOUNT, TRUE);

		}

		if($weight > 0) {

			$interval = abs((int)(\util\DateLib::interval($eSale['deliveredAt'], $eCashflow['date']) / 60 / 60 / 24));

			if($interval < 30) {

				$weight += 80;
				$reason->value(\preaccounting\Suggestion::DATE, TRUE);

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
