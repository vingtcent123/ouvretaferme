<?php
namespace account;

/**
 * Classe qui traitera tout ce qui concerne l'ouverture d'un exercice comptable
 * - à nouveau
 * - résultat
 * - extournes
 */
Class OpeningLib {
	
	public static function getRetainedEarnings(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): \Collection {

		$cOperation = \journal\OperationLib::getForOpening($eFinancialYearPrevious);
		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD);

		$cOperationNew = new \Collection();

		foreach($cOperation as $eOperation) {

			$eOperationNew = new \journal\Operation([
				'account' => $eOperation['account'],
				'accountLabel' => $eOperation['accountLabel'],
				'date' => $eFinancialYear['startDate'],
				'paymentDate' => $eFinancialYear['startDate'],
				'description' => $eOperation['account']['description'].' - '.new FinancialYearUi()->getOpeningDescription($eFinancialYearPrevious),
				'amount' => abs($eOperation['total']),
				'type' => $eOperation['total'] < 0 ? \journal\Operation::DEBIT : \journal\Operation::CREDIT,
				'financialYear' => $eFinancialYear['id'],
				'hash' => $hash,
				'journalCode' => $eJournalCode,

			]);
			$cOperationNew->append($eOperationNew);

		}

		return $cOperationNew;
		
	}
	
	public static function getReversableData(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): array {

		$cJournalCode = \journal\JournalCode::model()
			->select(\journal\JournalCode::getSelection())
			->whereIsReversable(TRUE)
			->getCollection();

		$ccOperationN = \journal\Operation::model()
			->select(\journal\Operation::getSelection())
			->whereFinancialYear($eFinancialYearPrevious)
			->whereJournalCode('IN', $cJournalCode)
			->getCollection(NULL, NULL, ['journalCode', 'id']);

		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD);
		$eUser = \user\ConnectionLib::getOnline();

		$ccOperation = new \Collection();
		foreach($ccOperationN as $journalCode => $cOperation) {

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

	public static function getResultOperation(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, string $hash): \journal\Operation {

		$result = \overview\IncomeStatementLib::computeResult($eFinancialYearPrevious);
		
		if($result >= 0) {
			
			$class = \account\AccountSetting::PROFIT_CLASS;
			$type = \journal\Operation::DEBIT;
			
		} else {
			
			$class = \account\AccountSetting::LOSS_CLASS;
			$type = \journal\Operation::CREDIT;
			
		}

		$eAccount = AccountLib::getByClass($class);
		$eJournalCode = \journal\JournalCodeLib::getByCode(\journal\JournalSetting::JOURNAL_CODE_OD);

		$values = [
			'account' => $eAccount,
			'accountLabel' => \account\ClassLib::pad($class),
			'date' => $eFinancialYear['startDate'],
			'paymentDate' => $eFinancialYear['startDate'],
			'description' => new FinancialYearUi()->getOpeningResult($eFinancialYearPrevious),
			'amount' => abs($result),
			'type' => $type,
			'financialYear' => $eFinancialYear,
			'hash' => $hash,
			'journalCode' => $eJournalCode,
		];
		
		return new \journal\Operation($values);
		
	}

	public static function open(FinancialYear $eFinancialYearPrevious, FinancialYear $eFinancialYear, array $journalCodes): void {

		$hash = \journal\OperationLib::generateHash().'n';
		
		$cOperation = \account\OpeningLib::getRetainedEarnings($eFinancialYearPrevious, $eFinancialYear, $hash);

		$eOperationResult = \account\OpeningLib::getResultOperation($eFinancialYearPrevious, $eFinancialYear, $hash);
		$cOperation->append($eOperationResult);

		list($cJournalCode, $ccOperationReversed) = \account\OpeningLib::getReversableData($eFinancialYearPrevious, $eFinancialYear, $hash);
		foreach($cJournalCode as $eJournalCode) {
			if(in_array((string)$eJournalCode['id'], $journalCodes)) {
				$cOperation->mergeCollection($ccOperationReversed->offsetGet($eJournalCode['id']));
			}
		}

		\journal\Operation::model()->insert($cOperation);

	}
	

}
