<?php
namespace journal;

class DeferralLib extends DeferralCrud {

	public static function getPropertiesCreate(): array {
		return ['type', 'financialYear', 'operation', 'amount', 'startDate', 'endDate'];
	}

	public static function getDeferrals(\account\FinancialYear $eFinancialYearPrevious): \Collection {

		return Deferral::model()
			->select(Deferral::getSelection() + ['operation' => Operation::getSelection()])
			->whereFinancialYear($eFinancialYearPrevious)
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
		$eJournalCode = JournalCodeLib::askByCode(JournalSetting::JOURNAL_CODE_INV);

		Deferral::model()->beginTransaction();

		foreach($cDeferral as $eDeferral) {

			$hash = OperationLib::generateHash().JournalSetting::HASH_LETTER_DEFERRAL;

			if($eDeferral['type'] === Deferral::CHARGE) {
				$class = \account\AccountSetting::PREPAID_EXPENSE_CLASS;
			} else {
				$class = \account\AccountSetting::ACCRUED_EXPENSE_CLASS;
			}

			$values = [
				'financialYear' => $eFinancialYear['id'],
				'account' => ($eDeferral['type'] === Deferral::CHARGE ? \account\AccountLib::getByClass($class) : $eDeferral['operation']['account'])['id'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? \account\AccountLabelLib::pad($class) : $eDeferral['operation']['accountLabel'],
				'thirdParty' => $eDeferral['operation']['thirdParty']['id'] ?? NULL,
				'description' => DeferralUi::getTranslation(Deferral::DEFERRED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => $eJournalCode['id'] ?? NULL,
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
				'hash' => $hash,
			];

			$eOperationDebit = OperationLib::createFromValues($values);

			$values = [
				'financialYear' => $eFinancialYear['id'],
				'account' => ($eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['account'] : \account\AccountLib::getByClass($class))['id'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['accountLabel'] : \account\AccountLabelLib::pad($class),
				'thirdParty' => $eDeferral['operation']['thirdParty']['id'] ?? NULL,
				'description' => DeferralUi::getTranslation(Deferral::DEFERRED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => $eJournalCode['id'] ?? NULL,
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
				'hash' => $hash,
			];

			$eOperationCredit = OperationLib::createFromValues($values);

			Deferral::model()->update($eDeferral, ['status' => Deferral::DEFERRED, 'updatedAt' => new \Sql('NOW()')]);

			// Si la date excède l'actuelle année comptable => Recréer un Deferral avec la quote part correspondante
			if($eDeferral['endDate'] > $eFinancialYear['endDate']) {
				self::createDeferral($eDeferral['type'] === Deferral::CHARGE ? $eOperationCredit : $eOperationDebit, ['endDate' => $eDeferral['endDate'], 'field' => 'dates']);
			}

		}

		Deferral::model()->commit();
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
			->whereFinancialYear($eFinancialYear)
			->whereStatus(Deferral::PLANNED)
			->getCollection();

		$eJournalCode = JournalCodeLib::askByCode(JournalSetting::JOURNAL_CODE_INV);

		Deferral::model()->beginTransaction();

		foreach($cDeferral as $eDeferral) {

			$hash = OperationLib::generateHash().JournalSetting::HASH_LETTER_DEFERRAL;

			if($eDeferral['type'] === Deferral::CHARGE) {
				$class = \account\AccountSetting::PREPAID_EXPENSE_CLASS;
			} else {
				$class = \account\AccountSetting::ACCRUED_EXPENSE_CLASS;
			}

			$values = [
				'financialYear' => $eFinancialYear['id'],
				'account' => ($eDeferral['type'] === Deferral::CHARGE ? \account\AccountLib::getByClass($class) : $eDeferral['operation']['account'])['id'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? \account\AccountLabelLib::pad($class) : $eDeferral['operation']['accountLabel'],
				'thirdParty' => $eDeferral['operation']['thirdParty']['id'] ?? NULL,
				'description' => DeferralUi::getTranslation(Deferral::RECORDED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::DEBIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => $eJournalCode['id'] ?? NULL,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
				'hash' => $hash,
			];

			OperationLib::createFromValues($values);

			$values = [
				'financialYear' => $eFinancialYear['id'],
				'account' => ($eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['account'] : \account\AccountLib::getByClass($class))['id'],
				'accountLabel' => $eDeferral['type'] === Deferral::CHARGE ? $eDeferral['operation']['accountLabel'] : \account\AccountLabelLib::pad($class),
				'thirdParty' => $eDeferral['operation']['thirdParty']['id'] ?? NULL,
				'description' => DeferralUi::getTranslation(Deferral::RECORDED, $eDeferral['type']).' - '.$eDeferral['operation']['description'],
				'type' => Operation::CREDIT,
				'amount' => $eDeferral['amount'],
				'journalCode' => $eJournalCode['id'] ?? NULL,
				'date' => $eFinancialYear['endDate'],
				'paymentDate' => $eFinancialYear['endDate'],
				'hash' => $hash,
			];

			OperationLib::createFromValues($values);

			Deferral::model()->update($eDeferral, [
				'status' => Deferral::RECORDED,
				'updatedAt' => new \Sql('NOW()'),
			]);

		}

		Deferral::model()->commit();

	}

	public static function getDeferralsForOperations(): \Collection {

		return Deferral::model()
			->select(Deferral::getSelection() + ['operation' => Operation::getSelection()])
			->whereStatus(Deferral::PLANNED)
			->getCollection(NULL, NULL, 'operation');

	}

	public static function createDeferral(Operation $eOperation, array $input): string {

		$field = $input['field'] ?? NULL;
		if(in_array($field, ['dates', 'amount']) === FALSE) {
			return FALSE;
		}

		if($field === 'dates') {

			[$amount, $endDate] = self::computeAmount($eOperation, $input['endDate'] ?? NULL);

		} else {

			[$amount, $endDate] = self::computeDates($eOperation, $input['amount'] ?? 0);

		}

		$fw = new \FailWatch();

		$isCharge = \account\AccountLabelLib::isChargeClass($eOperation['accountLabel']);

		$eDeferral = new Deferral();
		$values = [
			'operation' => $eOperation['id'],
			'startDate' => $eOperation['date'],
			'endDate' => $endDate,
			'amount' => $amount,
			'financialYear' => $eOperation['financialYear']['id'],
			'status' => Deferral::PLANNED,
			'type' => $isCharge ? Deferral::CHARGE : Deferral::PRODUCT
		];
		$eDeferral->build(['type', 'financialYear', 'operation', 'startDate', 'endDate', 'amount', 'status'], $values);

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
