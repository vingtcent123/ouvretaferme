<?php
namespace account;
class FinancialYearLib extends FinancialYearCrud {

	private static ?\Collection $cOpenFinancialYear = NULL;

	public static function getPropertiesCreate(): array {
		return ['taxSystem', 'accountingType', 'startDate', 'endDate', 'hasVat', 'vatFrequency', 'legalCategory', 'associates', 'vatChargeability', 'accountingMode'];
	}
	public static function getPropertiesUpdate(): array {
		return ['taxSystem', 'accountingType', 'hasVat', 'vatFrequency', 'legalCategory', 'associates', 'vatChargeability', 'accountingMode'];
	}

	public static function getPreviousFinancialYear(FinancialYear $eFinancialYear): FinancialYear {

		$eFinancialYearPrevious = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereEndDate('<=', $eFinancialYear['startDate'])
			->sort(['endDate' => SORT_DESC])
			->get($eFinancialYearPrevious);

		return $eFinancialYearPrevious;

	}

	public static function getNextFinancialYearDates(?FinancialYear $eFinancialYear = NULL): array {

		$eFinancialYear ??= FinancialYearLib::getLastFinancialYear();

		return [
			'startDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 day')),
			'endDate' => date('Y-m-d', strtotime($eFinancialYear['endDate'].' +1 year'))
		];

	}

	/**
	 * @param FinancialYear $eFinancialYear Exercice sur lequel écrire le bilan d'ouverture
	 */
	public static function openFinancialYear(FinancialYear $eFinancialYear, array $journalCodes): void {

		$eFinancialYearPrevious = self::getPreviousFinancialYear($eFinancialYear);

		FinancialYear::model()->beginTransaction();

		OpeningLib::open($eFinancialYearPrevious, $eFinancialYear, $journalCodes);

		LogLib::save('open', 'FinancialYear', ['id' => $eFinancialYear['id']]);

		FinancialYear::model()->update($eFinancialYear, [
			'status' => FinancialYear::OPEN,
			'openDate' => new \Sql('NOW()'),
		]);

		FinancialYear::model()->commit();

	}

	/**
	 * Certains comptes doivent être soldés avant la clôture.
	 */
	public static function checkCanClose(FinancialYear $eFinancialYear): bool {

		$waitingAccounts = \journal\OperationLib::getWaitingAccountValues($eFinancialYear);
		if(array_reduce($waitingAccounts, fn($sum, $waitingAccount) => $waitingAccount['total'] + $sum, 0) !== 0.0) {
			return FALSE;
		}

		if(empty(\journal\OperationLib::getInternalTransferAccountValues($eFinancialYear)) === FALSE) {
			return FALSE;
		}

		if(empty(\journal\OperationLib::getFarmersAccountValue($eFinancialYear)) === FALSE) {
			return FALSE;
		}

		return TRUE;

	}

	public static function closeFinancialYear(\farm\Farm $eFarm, FinancialYear $eFinancialYear): bool {

		// Check waiting accounts, and balance
		if(
			count(\journal\OperationLib::getInternalTransferAccountValues($eFinancialYear)) > 0 or
			count(\journal\OperationLib::getWaitingAccountValues($eFinancialYear)) > 0 or
			\journal\TrialBalanceLib::isBalanced($eFinancialYear) === FALSE
		) {
			return FALSE;
		}

		FinancialYear::model()->beginTransaction();

		// 0- Annuler tous les imports FEC en attente
		ImportLib::cancelAll($eFinancialYear);

		// 1- Calcul des amortissements classe 2. + des étalements de subvention
		\asset\AssetLib::amortizeAll($eFinancialYear);

		// 2- Charges et Produits constatés d'avance
		if($eFinancialYear->isCashAccounting() === FALSE) {
			\journal\DeferralLib::recordDeferralIntoFinancialYear($eFinancialYear);
		}

		// 3- Solder le compte de l'exploitant si nécessaire
		$balanceFarmerAccount = \journal\OperationLib::getFarmersAccountValue($eFinancialYear);
		if($balanceFarmerAccount !== 0.0) {
			$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_INVENTORY;
			$cOperation = ClosingLib::getFarmersAccountCloseOperation($eFinancialYear, $hash, $balanceFarmerAccount);
			\journal\Operation::model()->insert($cOperation);
		}

		// Mettre les numéros d'écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

		FinancialYear::model()->update($eFinancialYear, [
			'status' => FinancialYear::CLOSE,
			'closeDate' => new \Sql('NOW()'),
		]);

		LogLib::save('close', 'FinancialYear', ['id' => $eFinancialYear['id']]);

		FinancialYear::model()->commit();

		// Met à jour tous les fichiers de l'exercice
		FinancialYearDocumentLib::regenerateAll($eFarm, $eFinancialYear);

		return TRUE;

	}

	public static function getFinancialYearSurroundingDate(string $date, ?int $excludedId): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStartDate('<=', $date)
			->whereEndDate('>=', $date)
			->whereId('!=', $excludedId, if:$excludedId !== NULL)
			->get($eFinancialYear);


		return $eFinancialYear;

	}

	public static function getAll(): \Collection {

		return self::getCache('list', fn() => FinancialYear::model()
			->select(FinancialYear::getSelection())
			->sort(['startDate' => SORT_DESC])
			->getCollection(NULL, NULL, 'id'));

	}

	public static function isDateLinkedToFinancialYear(string $date, \Collection $cFinancialYear): bool {

		foreach($cFinancialYear as $eFinancialYear) {

			if(
				$date >= date('Y-m-d', strtotime($eFinancialYear['startDate']))
				and $date <= date('Y-m-d', strtotime($eFinancialYear['endDate']))
			) {
				return TRUE;
			}
		}

		return FALSE;

	}

	public static function getLastFinancialYear(): FinancialYear {

		$eFinancialYear = new FinancialYear();

		FinancialYear::model()
			->select(FinancialYear::getSelection())
			->sort(['endDate' => SORT_DESC])
			->get($eFinancialYear);

		return $eFinancialYear;

	}

	public static function getByDate(string $date): FinancialYear {

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->where(FinancialYear::model()->format($date).' BETWEEN startDate AND endDate')
			->get();

	}

	public static function getOpenFinancialYearByDate(string $date): FinancialYear {

		return FinancialYear::model()
			->select(FinancialYear::getSelection())
			->whereStatus(FinancialYearElement::OPEN)
			->whereStartDate('<=', $date)
			->whereEndDate('>=', $date)
			->get();

	}

	public static function getOpenFinancialYears(): \Collection {

		if(self::$cOpenFinancialYear === NULL) {
			self::$cOpenFinancialYear = FinancialYear::model()
				->select(FinancialYear::getSelection())
				->whereStatus('=', FinancialYear::OPEN)
				->sort(['endDate' => SORT_DESC])
				->getCollection();
		}

		return self::$cOpenFinancialYear;

	}

	public static function getFinancialYearForDate(string $date, \Collection $cFinancialYear): FinancialYear {

		if($cFinancialYear->empty()) {
			$cFinancialYear = self::getAll();
		}

		foreach($cFinancialYear as $eFinancialYear) {
			if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
				return $eFinancialYear;
			}
		}

		return new FinancialYear();
	}

	public static function isDateInOpenFinancialYear(string $date, \Collection $cFinancialYear = new \Collection()): FinancialYear {

		if($cFinancialYear->empty()) {
			$cFinancialYear = self::getOpenFinancialYears();
		}

		foreach($cFinancialYear as $eFinancialYear) {
			if($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']) {
				return $eFinancialYear;
			}
		}

		return new FinancialYear();

	}

	public static function isDateInFinancialYear(string $date, FinancialYear $eFinancialYear): bool {

		return($date >= $eFinancialYear['startDate'] and $date <= $eFinancialYear['endDate']);

	}

	public static function create(FinancialYear $e): void {

		$eFarm = \farm\FarmLib::getById(POST('farm'))->validate('canManage');

		if($e['endDate'] < $e['startDate']) {
			$startDate = $e['endDate'];
			$e['endDate'] = $e['startDate'];
			$e['startDate'] = $startDate;
		}

		FinancialYear::model()->beginTransaction();

			FinancialYear::model()->insert($e);

			\farm\Farm::model()->update($eFarm, [
				'hasFinancialYears' => TRUE
			]);

		FinancialYear::model()->commit();


	}

	public static function update(FinancialYear $e, array $properties): void {

		if($e['nOperation'] > 0) {
			array_delete($properties, 'startDate');
			array_delete($properties, 'endDate');
		}

		parent::update($e, $properties);

		$changes = [];
		foreach($e['eOld'] as $property => $value) {
			if($e[$property] !== $value) {
				$changes[$property] = ['old' => $value, 'new' => $e[$property]];
			}
		}

		LogLib::save('update', 'FinancialYear', ['id' => $e['id'], 'changes' => $changes]);

	}

	public static function reopen(FinancialYear $eFinancialYear): void {

		// On ne réinitialise pas closeDate car le bilan de clôture a déjà été réalisé
		FinancialYear::model()
			->update($eFinancialYear, ['status' => FinancialYear::OPEN]);

		LogLib::save('reopen', 'FinancialYear', ['id' => $eFinancialYear['id']]);

	}

	public static function reclose(FinancialYear $eFinancialYear): void {

		FinancialYear::model()
			->update($eFinancialYear, ['status' => FinancialYear::CLOSE]);

		LogLib::save('reclose', 'FinancialYear', ['id' => $eFinancialYear['id']]);

	}

}

?>
