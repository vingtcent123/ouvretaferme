<?php
namespace journal;

class DeferredChargeLib extends DeferredChargeCrud {

	public static function getPropertiesCreate(): array {
		return ['operation', 'amount', 'startDate', 'endDate'];
	}

	public static function getPropertiesUpdate(): array {
		return ['amount', 'startDate', 'endDate'];
	}

	/**
	 * Crédite 486 et débite le compte initial.
	 */
	public static function deferChargesIntoFinancialYear(\account\FinancialYear $eFinancialYear): void {

		$cDeferredCharges = DeferredCharge::model()
			->select(DeferredCharge::getSelection() + ['operation' => Operation::getSelection()])
			->whereStatus(DeferredCharge::RECORDED)
			->getCollection();

		foreach($cDeferredCharges as $eDeferredCharge) {

			$eOperation486 = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => \account\AccountLib::getByClass(\Setting::get('account\deferredChargeClass')),
				'accountLabel' => \account\ClassLib::pad(\Setting::get('account\deferredChargeClass')),
				'thirdParty' => $eDeferredCharge['operation']['thirdParty'],
				'description' => DeferredChargeUi::getTranslation(DeferredCharge::DEFERRED).' - '.$eDeferredCharge['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferredCharge['amount'],
				'journalCode' => Operation::OD,
				'date' => new \Sql('NOW()'),
				'paymentDate' => new \Sql('NOW()'),
			]);

			Operation::model()->insert($eOperation486);

			$eOperationCredit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferredCharge['operation']['account'],
				'accountLabel' => $eDeferredCharge['operation']['accountLabel'],
				'thirdParty' => $eDeferredCharge['operation']['thirdParty'],
				'description' => DeferredChargeUi::getTranslation(DeferredCharge::DEFERRED).' - '.$eDeferredCharge['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferredCharge['amount'],
				'journalCode' => Operation::OD,
				'date' => new \Sql('NOW()'),
				'paymentDate' => new \Sql('NOW()'),
			]);

			Operation::model()->insert($eOperationCredit);

			$eDeferredCharge['status'] = DeferredCharge::DEFERRED;
			$eDeferredCharge['updatedAt'] = new \Sql('NOW()');

			DeferredCharge::model()
				->select(['status', 'updatedAt'])
				->update($eDeferredCharge);

		}

	}

	/**
	 * Débite 486 et crédite le compte initial pour déduire les charges constatées d'avance.
	 */
	public static function recordChargesIntoFinancialYear(\account\FinancialYear $eFinancialYear): void {

		$cDeferredCharges = DeferredCharge::model()
			->select(DeferredCharge::getSelection() + ['operation' => Operation::getSelection()])
			->whereStatus(DeferredCharge::PLANNED)
			->getCollection();

		foreach($cDeferredCharges as $eDeferredCharge) {

			$eOperation486 = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => \account\AccountLib::getByClass(\Setting::get('account\deferredChargeClass')),
				'accountLabel' => \account\ClassLib::pad(\Setting::get('account\deferredChargeClass')),
				'thirdParty' => $eDeferredCharge['operation']['thirdParty'],
				'description' => DeferredChargeUi::getTranslation(DeferredCharge::RECORDED).' - '.$eDeferredCharge['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferredCharge['amount'],
				'journalCode' => Operation::OD,
				'date' => new \Sql('NOW()'),
				'paymentDate' => new \Sql('NOW()'),
			]);

			Operation::model()->insert($eOperation486);

			$eOperationCredit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferredCharge['operation']['account'],
				'accountLabel' => $eDeferredCharge['operation']['accountLabel'],
				'thirdParty' => $eDeferredCharge['operation']['thirdParty'],
				'description' => DeferredChargeUi::getTranslation(DeferredCharge::RECORDED).' - '.$eDeferredCharge['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferredCharge['amount'],
				'journalCode' => Operation::OD,
				'date' => new \Sql('NOW()'),
				'paymentDate' => new \Sql('NOW()'),
			]);

			Operation::model()->insert($eOperationCredit);

			$eDeferredCharge['status'] = DeferredCharge::RECORDED;
			$eDeferredCharge['updatedAt'] = new \Sql('NOW()');

			DeferredCharge::model()
				->select(['status', 'updatedAt'])
				->update($eDeferredCharge);

		}

	}

	public static function getDeferredChargesForOperations(\Collection $cOperation): void {

		$cDeferredCharge = DeferredCharge::model()
			->select(DeferredCharge::getSelection())
			->whereOperation('IN', $cOperation)
			->getCollection(NULL, NULL, 'operation');

		$cOperation->map(function($eOperation) use($cDeferredCharge) {
			$eOperation['deferredCharge'] = $cDeferredCharge->find(function($e) use ($eOperation) {
				return $e['operation']['id'] === $eOperation['id'];
			})->first();
		});

		// On affichera les charges déjà reportées en premier
		$cOperation->sort(function($e1, $e2) {

			if($e1['deferredCharge'] !== null and $e2['deferredCharge'] === NULL) {
				return -1;
			}

			if($e1['deferredCharge'] === NULL and $e2['deferredCharge'] !== NULL) {
				return 1;
			}

			return $e1['date'] > $e2['date'] ? 1 : -1;

		});

	}

	public static function createDeferredCharge(array $input): bool {

		$field = $input['field'] ?? NULL;
		if(in_array($field, ['dates', 'amount']) === FALSE) {
			return FALSE;
		}

		$eFinancialYear = \account\FinancialYearLib::getById($input['financialYear'] ?? NULL)->validate('canUpdate');

		$eOperation = OperationLib::getById($input['id'] ?? NULL);
		if($eOperation->isDeferrableCharge($eFinancialYear) === FALSE) {
			throw new \NotExpectedAction('Unable to defer charge');
		}

		if($field === 'dates') {

			[$amount, $endDate] = self::computeAmount($eOperation['date'], $input['endDate'] ?? NULL, $eOperation['amount'], $eFinancialYear['endDate']);

		} else {

			[$amount, $endDate] = self::computeDates($eOperation['date'], $eOperation['amount'], $input['amount'] ?? 0, $eFinancialYear['endDate']);

		}

		$fw = new \FailWatch();

		$eDeferredCharge = new DeferredCharge();
		$values = [
			'operation' => $eOperation['id'],
			'startDate' => $eOperation['date'],
			'endDate' => $endDate,
			'amount' => $amount,
			'initialFinancialYear' => $eFinancialYear['id'],
			'status' => DeferredCharge::PLANNED,
		];
		$eDeferredCharge->build(['operation', 'startDate', 'endDate', 'amount', 'initialFinancialYear', 'status'], $values);

		$fw->validate();

		DeferredCharge::model()->insert($eDeferredCharge);

		\account\LogLib::save('create', 'deferredCharge', ['id' => $eDeferredCharge['id']]);
		return TRUE;

	}

	private static function computeAmount(string $startDate, string $endDate, float $initialAmount, string $financialYearEndDate): array {

		if(
			mb_strlen($endDate) !== 10
			or checkdate(mb_substr($endDate, 5, 2), mb_substr($endDate, 8, 2), mb_substr($endDate, 0, 4)) === FALSE
			or $endDate < $startDate
			or $endDate < $financialYearEndDate
		) {
			throw new \NotExpectedAction('Unable to compute amount');
		}

		$financialYearEndTime = strtotime($financialYearEndDate);
		$startTime = strtotime($startDate);
		$endTime = strtotime($endDate);

		$nbDaysYear1 = round(($financialYearEndTime - $startTime) / (60 * 60 * 24)) + 1;
		$totalDays = round(($endTime - $startTime) / (60 * 60 * 24)) + 1;

		$newAmount = round(($totalDays - $nbDaysYear1) * $initialAmount / $totalDays, 2);

		return [$newAmount, $endDate];

	}

	private static function computeDates(string $startDate, float $initialAmount, float $deferredAmount, string $financialYearEndDate): array {

		if($deferredAmount >= $initialAmount) {
			throw new \NotExpectedAction('Unable to compute date');
		}

		$financialYearEndTime = strtotime($financialYearEndDate);
		$startTime = strtotime($startDate);

		$nbDaysYear1 = round(($financialYearEndTime - $startTime) / (60 * 60 * 24)) + 1;

		$amountYear1 = $initialAmount - $deferredAmount;
		$totalDays = round($nbDaysYear1 * ($initialAmount / $amountYear1));

		$endTime = strtotime('+ '.$totalDays.' days - 1 day', $startTime);

		return [$deferredAmount, date('Y-m-d', $endTime)];

	}

}
