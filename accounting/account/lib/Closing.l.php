<?php
namespace account;

Class ClosingLib {

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

	public static function checkReclose(FinancialYear $eFinancialYear): ?array {

		$farmersAccountValue = \journal\OperationLib::getFarmersAccountValue($eFinancialYear);
		$waitingAccounts = \journal\OperationLib::getWaitingAccountValues($eFinancialYear);
		$internalAccount = \journal\OperationLib::getInternalTransferAccountValues($eFinancialYear);
		$isBalanced = \journal\TrialBalanceLib::isBalanced($eFinancialYear);

		if(
			$farmersAccountValue === 0.0 and
			count($waitingAccounts) === 0 and
			count($internalAccount) === 0 and
			$isBalanced === TRUE
		) {
			return NULL;
		}

		return ['farmersAccountValue' => $farmersAccountValue, 'waitingAccounts' => $waitingAccounts, 'internalAccount' => $internalAccount, 'isBalanced' => $isBalanced];

	}

	public static function reclose(FinancialYear $eFinancialYear): void {

		// On revérifie les équilibres
		if(self::checkReclose($eFinancialYear) !== NULL) {
			throw new \FailAction('FinancialYear::reclose.check');
		}

		// On attribue des numéros aux nouvelles écritures
		\journal\OperationLib::setNumbers($eFinancialYear);

		FinancialYear::model()->update($eFinancialYear, ['status' => FinancialYear::CLOSE]);

		LogLib::save('reclose', 'FinancialYear', ['id' => $eFinancialYear['id']]);

	}
	public static function getFarmersAccountCloseOperation(FinancialYear $eFinancialYearPrevious, string $hash, float $amount): \Collection {

		$cOperation = new \Collection();
		if($amount === 0.0) {
			return new \Collection();
		}

		$cAccount = AccountLib::getByClasses([AccountSetting::FARMER_S_ACCOUNT_CLASS, AccountSetting::CAPITAL_CLASS], 'class');
		$eJournalCode = \journal\JournalCodeLib::askByCode(\journal\JournalSetting::JOURNAL_CODE_OD);

		$eAccountFarmer = $cAccount[AccountSetting::FARMER_S_ACCOUNT_CLASS];
		$values = [
			'account' => $eAccountFarmer,
			'accountLabel' => \account\AccountLabelLib::pad($eAccountFarmer['class']),
			'date' => $eFinancialYearPrevious['endDate'],
			'paymentDate' => $eFinancialYearPrevious['endDate'],
			'description' => new FinancialYearUi()->getFarmersAccountClose($eFinancialYearPrevious),
			'amount' => abs($amount),
			'type' => $amount < 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
			'financialYear' => $eFinancialYearPrevious,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		$eAccountCapital = $cAccount[AccountSetting::CAPITAL_CLASS];
		$values = [
			'account' => $eAccountCapital,
			'accountLabel' => \account\AccountLabelLib::pad($eAccountCapital['class']),
			'date' => $eFinancialYearPrevious['endDate'],
			'paymentDate' => $eFinancialYearPrevious['endDate'],
			'description' => new FinancialYearUi()->getFarmersAccountClose($eFinancialYearPrevious),
			'amount' => abs($amount),
			'type' => $amount > 0 ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
			'financialYear' => $eFinancialYearPrevious,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		return $cOperation;

	}

}
