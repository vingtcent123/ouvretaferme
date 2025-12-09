<?php
namespace invoicing;

Class SuggestionLib extends SuggestionCrud {

	public static function countWaitingOperations(): int {

		return Suggestion::model()
			->select([new \Sql('DISTINCT(operation)')])
			->whereStatus(Suggestion::WAITING)
			->getCollection()
			->count();

	}

	public static function getAllWaiting(): \Collection {

		return Suggestion::model()
			->select([
				'id',
				'cashflow' => ['id', 'name', 'memo', 'type', 'date', 'amount'],
				'operation' => ['id', 'date', 'description', 'accountLabel', 'account' => ['id', 'class', 'description'], 'amount', 'type', 'thirdParty' => ['id', 'name', 'clientAccountLabel']],
				'weight', 'reason',
			])
			->whereStatus(Suggestion::WAITING)
			->group(['operation', 'cashflow'])
			->getCollection(NULL, NULL, ['operation', 'cashflow'])
			->sort(fn(\Collection $c1, \Collection $c2) => $c1->first()['operation']['date'] <=> $c2->first()['operation']['date']);

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

	public static function calculateForCashflow(\account\FinancialYear $eFinancialYear, \bank\Cashflow $eCashflow): void {

		$cThirdParty = \account\ThirdParty::model()
			->select('id', 'name', 'memos', 'normalizedName')
			->getCollection(NULL, NULL, 'id');

		$cThirdParty = \account\ThirdPartyLib::filterByCashflow($cThirdParty, $eCashflow)->filter(fn($e) => $e['weight'] > 50);

		// Par le tiers
		$cOperationThirdParty = \journal\Operation::model()
			->select(['id', 'amount', 'thirdParty', 'date'])
			->whereThirdParty('IN', $cThirdParty)
			->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
			->where(new \Sql('letteringStatus IS NULL OR letteringStatus = '.\journal\Operation::model()->format(\journal\Operation::PARTIAL)))
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
			->getCollection();

		foreach($cOperationThirdParty as $eOperation) {

			$eOperation['thirdParty'] = $cThirdParty->offsetGet($eOperation['thirdParty']['id']);
			list($weight, $reason) = self::weightCashflowOperation($eCashflow, $eOperation);

			if($weight > 50) {

				$eSuggestion = new Suggestion([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'reason' => $reason,
					'weight' => $weight,
				]);

				Suggestion::model()->option('add-replace')->insert($eSuggestion);

			}

		}

		// Par le montant exact
		$cOperation = \journal\Operation::model()
			->select(['id', 'amount', 'thirdParty', 'date'])
			->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
			->where(new \Sql('letteringStatus IS NULL OR letteringStatus = '.\journal\Operation::model()->format(\journal\Operation::PARTIAL)))
			->where(new \Sql('ROUND(amount, 2) = ROUND('.abs($eCashflow['amount']).', 2) '))
			->whereType($eCashflow['amount'] > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT)
			->whereDate('>=', $eFinancialYear['startDate'])
			->whereDate('<=', $eFinancialYear['endDate'])
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

			$eSuggestion = new Suggestion([
				'operation' => $eOperation,
				'cashflow' => $eCashflow,
				'reason' => $reason,
				'weight' => $weight,
			]);

			try {

				Suggestion::model()->insert($eSuggestion);

			} catch(\DuplicateException $e) {

				Suggestion::model()
					->whereOperation($eOperation)
					->whereCashflow($eCashflow)
					->whereWeight('<', $eSuggestion['weight'])
          ->update(['weight' => $eSuggestion['weight']]);

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

			if($weight > 50) {

				$eSuggestion = new Suggestion([
					'operation' => $eOperation,
					'cashflow' => $eCashflow,
					'reason' => $reason,
					'weight' => $weight,
				]);

				Suggestion::model()->option('add-replace')->insert($eSuggestion);

			}

		}

	}

	public static function weightCashflowOperation(\bank\Cashflow $eCashflow, \journal\Operation $eOperation): array {

		$weight = \account\ThirdPartyLib::extractWeightByCashflow($eOperation['thirdParty'], $eCashflow);

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

		if($weight > 0) {
			$reason->value(Suggestion::THIRD_PARTY, TRUE);
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

		return [$weight, $reason];
	}

}
