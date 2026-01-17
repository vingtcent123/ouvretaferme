<?php
namespace account;

/**
 * Classe qui traitera tout ce qui concerne l'ouverture d'un exercice comptable
 * - à nouveau
 * - résultat
 * - extournes
 */
Class OpeningLib {

	/**
	 * À-nouveaux
	 */
	public static function getRetainedEarnings(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): \Collection {

		$cOperation = \journal\OperationLib::getForOpening($eFinancialYearPrevious);
		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD_BILAN);

		$cOperationNew = new \Collection();

		foreach($cOperation as $eOperation) {

			$eOperationNew = new \journal\Operation([
				'account' => $eOperation['account'],
				'accountLabel' => $eOperation['accountLabel'],
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
				'description' => $eOperation['account']['description'].' - '.new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious),
				'amount' => abs($eOperation['total']),
				'document' => new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious),
				'documentDate' => $eFinancialYear['startDate'],
				'type' => $eOperation['total'] < 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eJournalCode,
			]);
			$cOperationNew->append($eOperationNew);

		}

		return $cOperationNew;
		
	}

	/**
	 * Extournes
	 */
	public static function getReversableData(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): array {

		$cJournalCode = \journal\JournalCode::model()
			->select(\journal\JournalCode::getSelection())
			->whereIsReversable(TRUE)
			->getCollection();

		$ccOperationPrevious = \journal\Operation::model()
			->select(\journal\Operation::getSelection())
			->whereFinancialYear($eFinancialYearPrevious)
			->whereJournalCode('IN', $cJournalCode)
			->getCollection(NULL, NULL, ['journalCode', 'id']);

		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD_BILAN);
		$eUser = \user\ConnectionLib::getOnline();

		$ccOperation = new \Collection();
		foreach($ccOperationPrevious as $journalCode => $cOperation) {

			$ccOperation[$journalCode] = new \Collection();

			foreach($cOperation as $eOperation) {

				$eOperationNew = clone $eOperation;
				unset($eOperationNew['id']);

				if($eOperationNew['type'] === \journal\Operation::CREDIT) {

					$eOperationNew['type'] = \journal\Operation::DEBIT;

				} else {

					$eOperationNew['type'] = \journal\Operation::CREDIT;

				}

				$eOperationNew['hash'] = $hash;
				$eOperationNew['date'] = $eFinancialYear['startDate'];
				$eOperationNew['paymentDate'] = $eFinancialYear['startDate'];
				$eOperationNew['journalCode'] = $eJournalCode;
				$eOperationNew['createdAt'] = new \Sql('NOW()');
				$eOperationNew['updatedAt'] = new \Sql('NOW()');
				$eOperationNew['createdBy'] = $eUser;
				$eOperationNew['description'] .= ' - '.new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious);

				$ccOperation[$journalCode]->append($eOperationNew);

			}
		}

		return [$cJournalCode, $ccOperation];

	}

	/**
	 * Résultat
	 */
	public static function getResultOperation(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): \Collection {

		$cOperation = new \Collection();
		$result = \overview\IncomeStatementLib::computeResult($eFinancialYearPrevious);

		if($result === 0.0) {
			return $cOperation;
		}

		if($eFinancialYear['legalCategory'] === \company\CompanySetting::CATEGORIE_JURIDIQUE_ENTREPRENEUR_INDIVIDUEL) {

			$retainedClass = AccountSetting::FARMER_S_ACCOUNT_CLASS;

		} else {

			if($result >= 0) {
				$retainedClass = AccountSetting::PROFIT_RETAINED_CLASS;
			} else {
				$retainedClass = AccountSetting::LOSS_RETAINED_CLASS;
			}
		}

		if($result >= 0) {

			$class = \account\AccountSetting::PROFIT_CLASS;
			$type = \journal\Operation::CREDIT;

		} else {

			$class = \account\AccountSetting::LOSS_CLASS;
			$type = \journal\Operation::DEBIT;

		}

		// Résultat
		$eAccount = AccountLib::getByClass($class);
		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD_BILAN);

		$values = [
			'account' => $eAccount,
			'accountLabel' => \account\AccountLabelLib::pad($class),
			'date' => $eFinancialYear['startDate'],
			'paymentDate' => $eFinancialYear['startDate'],
			'description' => new FinancialYearUi()->getOpeningResult($eFinancialYearPrevious),
			'amount' => abs($result),
			'type' => $type,
			'financialYear' => $eFinancialYear,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		// Affectation du résultat : écriture équilibrée qui solde le résultat
		$values = [
			'account' => $eAccount,
			'accountLabel' => \account\AccountLabelLib::pad($class),
			'date' => $eFinancialYear['startDate'],
			'paymentDate' => $eFinancialYear['startDate'],
			'description' => new FinancialYearUi()->getOpeningAffectResult($eFinancialYearPrevious),
			'amount' => abs($result),
			'type' => $type === \journal\Operation::DEBIT ? \journal\Operation::CREDIT : \journal\Operation::DEBIT,
			'financialYear' => $eFinancialYear,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		$eAccount = AccountLib::getByClass($retainedClass);

		$values = [
			'account' => $eAccount,
			'accountLabel' => \account\AccountLabelLib::pad($retainedClass),
			'date' => $eFinancialYear['startDate'],
			'paymentDate' => $eFinancialYear['startDate'],
			'description' => new FinancialYearUi()->getOpeningAffectResult($eFinancialYearPrevious),
			'amount' => abs($result),
			'type' => $type,
			'financialYear' => $eFinancialYear,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];

		$cOperation->append(new \journal\Operation($values));

		return $cOperation;
	}

	public static function open(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, array $journalCodes): void {

		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_RETAINED;
		
		$cOperation = \account\OpeningLib::getRetainedEarnings($eFinancialYearPrevious, $eFinancialYear, $hash);

		$cOperationResult = \account\OpeningLib::getResultOperation($eFinancialYearPrevious, $eFinancialYear, $hash);

		if($cOperationResult->notEmpty()) {
			$cOperation->mergeCollection($cOperationResult);
		}

		if($eFinancialYear->isCashAccounting() === FALSE) {

			[$cJournalCode, $ccOperationReversed] = \account\OpeningLib::getReversableData($eFinancialYearPrevious, $eFinancialYear, $hash);
			foreach($cJournalCode as $eJournalCode) {
				if(in_array((string)$eJournalCode['id'], $journalCodes)) {
					$cOperation->mergeCollection($ccOperationReversed->offsetGet($eJournalCode['id']));
				}
			}

		}

		if($cOperation->empty()) {
			return;
		}

		\journal\Operation::model()->insert($cOperation);

		// Récupération des PCA et CCA (ACCRUAL UNIQUEMENT)
		//\journal\DeferralLib::deferIntoFinancialYear($eFinancialYearPrevious, $eFinancialYear);

	}
	

}
