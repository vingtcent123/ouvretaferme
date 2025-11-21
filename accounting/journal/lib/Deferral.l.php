<?php
namespace journal;

class DeferralLib extends DeferralCrud {

	public static function getPropertiesCreate(): array {
		return ['type', 'initialFinancialYear', 'operation', 'amount', 'startDate', 'endDate'];
	}

	public static function getDeferrals(\account\FinancialYear $eFinancialYearPrevious): \Collection {

		return Deferral::model()
			->select(Deferral::getSelection() + ['operation' => Operation::getSelection()])
			->whereInitialFinancialYear($eFinancialYearPrevious)
			->whereStatus(Deferral::RECORDED)
			->getCollection();

	}

	/**
	 * Charges : Crédite 486 et débite le compte initial.
	 * Produits : Débite 487 et crédite le compte initial.
	 *
	 * /!\ À utiliser à l'ouverture d'un exercice comptable
	 */
	public static function deferIntoFinancialYear(\account\FinancialYear $eFinancialYearPrevious, \account\FinancialYear $eFinancialYear): void {

		$cDeferral = self::getDeferrals($eFinancialYearPrevious);

		foreach($cDeferral as $eDeferral) {

			if($eDeferral['type'] === Deferral::CHARGE) {
				$class = \account\AccountSetting::PREPAID_EXPENSE_CLASS;
			} else {
				$class = \account\AccountSetting::ACCRUED_EXPENSE_CLASS;
			}

			$eOperationCredit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferral['type'] === Deferral::CHARGE ? \account\AccountLib::getByClass($class) : $eDeferral['operation']['account'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? \account\ClassLib::pad($class) : $eDeferral['operation']['accountLabel'],
				'thirdParty' => $eDeferral['operation']['thirdParty'],
				'description' => DeferralUi::getTranslation(Deferral::DEFERRED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
			]);

			Operation::model()->insert($eOperationCredit);

			$eOperationDebit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['account'] : \account\AccountLib::getByClass($class),
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['accountLabel'] : \account\ClassLib::pad($class),
				'thirdParty' => $eDeferral['operation']['thirdParty'],
				'description' => DeferralUi::getTranslation(Deferral::DEFERRED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
			]);

			Operation::model()->insert($eOperationDebit);

			Deferral::model()->update($eDeferral, ['status' => Deferral::DEFERRED, 'updatedAt' => new \Sql('NOW()')]);

		}

	}

	/**
	 * Charge : Débite 486 et crédite le compte initial pour déduire les charges constatées d'avance.
	 * Produit : Crédit 487 et débite le compte initial pour déduire les produits constatés d'avance.
	 *
	 * /!\ à utiliser à la clôture de l'exercice comptable
	 */
	public static function recordDeferralIntoFinancialYear(\account\FinancialYear $eFinancialYear): void {

		$cDeferral = Deferral::model()
			->select(Deferral::getSelection() + ['operation' => Operation::getSelection()])
			->whereInitialFinancialYear($eFinancialYear)
			->whereStatus(Deferral::PLANNED)
			->getCollection();

		foreach($cDeferral as $eDeferral) {

			if($eDeferral['type'] === Deferral::CHARGE) {
				$class = \account\AccountSetting::PREPAID_EXPENSE_CLASS;
			} else {
				$class = \account\AccountSetting::ACCRUED_EXPENSE_CLASS;
			}

			$eOperationDebit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferral['type'] === Deferral::CHARGE ? \account\AccountLib::getByClass($class) : $eDeferral['operation']['account'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? \account\ClassLib::pad($class) : $eDeferral['operation']['accountLabel'],
				'thirdParty' => $eDeferral['operation']['thirdParty'],
				'description' => DeferralUi::getTranslation(Deferral::RECORDED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
			]);

			Operation::model()->insert($eOperationDebit);

			$eOperationCredit = new Operation([
				'financialYear' => $eFinancialYear,
				'account' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['account'] : \account\AccountLib::getByClass($class),
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['accountLabel'] : \account\ClassLib::pad($class),
				'thirdParty' => $eDeferral['operation']['thirdParty'],
				'description' => DeferralUi::getTranslation(Deferral::RECORDED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => Operation::OD,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
			]);

			Operation::model()->insert($eOperationCredit);

			Deferral::model()->update($eDeferral, ['status' => Deferral::RECORDED, 'updatedAt' => new \Sql('NOW()')]);

		}

	}

	public static function getDeferralsForOperations(\Collection $cOperation): void {

		$cDeferral = Deferral::model()
			->select(Deferral::getSelection())
			->whereOperation('IN', $cOperation)
			->getCollection(NULL, NULL, 'operation');

		$cOperation->map(function($eOperation) use($cDeferral) {
			$eOperation['deferral'] = $cDeferral->find(function($e) use ($eOperation) {
				return $e['operation']['id'] === $eOperation['id'];
			})->first();
		});

		// On affichera les PCA et CCA déjà reportés en premier
		$cOperation->sort(function($e1, $e2) {

			if($e1['deferral'] !== null and $e2['deferral'] === NULL) {
				return -1;
			}

			if($e1['deferral'] === NULL and $e2['deferral'] !== NULL) {
				return 1;
			}

			if(substr($e1['accountLabel'], 0, 1) > substr($e2['accountLabel'], 0, 1)) {
				return 1;
			}

			if(substr($e1['accountLabel'], 0, 1) < substr($e2['accountLabel'], 0, 1)) {
				return -1;
			}

			return $e1['date'] > $e2['date'] ? 1 : -1;

		});

	}

	public static function createDeferral(Operation $eOperation, array $input): bool {

		$field = $input['field'] ?? NULL;
		if(in_array($field, ['dates', 'amount']) === FALSE) {
			return FALSE;
		}

		$isCharge = \account\ClassLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::CHARGE_ACCOUNT_CLASS);
		$isProduct = \account\ClassLib::isFromClass($eOperation['accountLabel'], \account\AccountSetting::PRODUCT_ACCOUNT_CLASS);

		if($isCharge === FALSE and $isProduct === FALSE) {
			throw new \NotExpectedAction('Unable to defer operation (not a charge nor a product)');
		}

		if($field === 'dates') {

			[$amount, $endDate] = self::computeAmount($eOperation, $input['endDate'] ?? NULL);

		} else {

			[$amount, $endDate] = self::computeDates($eOperation, $input['amount'] ?? 0);

		}

		$fw = new \FailWatch();

		$eDeferral = new Deferral();
		$values = [
			'operation' => $eOperation['id'],
			'startDate' => $eOperation['date'],
			'endDate' => $endDate,
			'amount' => $amount,
			'initialFinancialYear' => $eOperation['financialYear']['id'],
			'status' => Deferral::PLANNED,
			'type' => $isCharge ? Deferral::CHARGE : Deferral::PRODUCT
		];
		$eDeferral->build(['type', 'operation', 'initialFinancialYear', 'startDate', 'endDate', 'amount', 'status'], $values);

		$fw->validate();

		Deferral::model()->insert($eDeferral);

		\account\LogLib::save('create', 'Deferral', ['id' => $eDeferral['id'], 'type' => $isCharge ? 'charge' : 'product']);

		return $isCharge ? 'Deferral::charge.created' : 'Deferral::product.created';

	}

	private static function computeAmount(Operation $eOperation, ?string $endDate): array {

		if(
			$endDate === NULL or
			mb_strlen($endDate) !== 10 or
			checkdate(mb_substr($endDate, 5, 2), mb_substr($endDate, 8, 2), mb_substr($endDate, 0, 4)) === FALSE or
			$endDate < $eOperation['date'] or
			$endDate < $eOperation['financialYear']['endDate']
		) {
			throw new \NotExpectedAction('Unable to compute amount');
		}

		$financialYearEndTime = strtotime($eOperation['financialYear']['endDate']);
		$startTime = strtotime($eOperation['date']);
		$endTime = strtotime($endDate);

		$nbDaysYear1 = round(($financialYearEndTime - $startTime) / (60 * 60 * 24)) + 1;
		$totalDays = round(($endTime - $startTime) / (60 * 60 * 24)) + 1;

		$newAmount = round(($totalDays - $nbDaysYear1) * $eOperation['amount'] / $totalDays, 2);

		return [$newAmount, $endDate];

	}

	private static function computeDates(Operation $eOperation, float $deferredAmount): array {

		if($deferredAmount >= $eOperation['amount']) {
			throw new \NotExpectedAction('Unable to compute date');
		}

		$financialYearEndTime = strtotime($eOperation['financialYear']['endDate']);
		$startTime = strtotime($eOperation['date']);

		$nbDaysYear1 = round(($financialYearEndTime - $startTime) / (60 * 60 * 24)) + 1;

		$amountYear1 = $eOperation['amount'] - $deferredAmount;
		$totalDays = round($nbDaysYear1 * ($eOperation['amount'] / $amountYear1));

		$endTime = strtotime('+ '.$totalDays.' days - 1 day', $startTime);

		return [$deferredAmount, date('Y-m-d', $endTime)];

	}

}
