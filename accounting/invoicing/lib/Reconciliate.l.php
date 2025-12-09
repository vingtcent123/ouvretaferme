<?php
namespace invoicing;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\Collection $cSuggestion): void {

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eSuggestion);
		}

	}

	public static function reconciliateSuggestion(Suggestion $eSuggestion): void {

		\journal\Operation::model()->beginTransaction();

		$eOperation = \journal\OperationLib::getById($eSuggestion['operation']['id']);
		$eCashflow = \bank\CashflowLib::getById($eSuggestion['cashflow']['id']);

		$hash = \journal\OperationLib::generateHash().\journal\JournalSetting::HASH_LETTER_RECONCILIATE;
		$eThirdParty = $eOperation['thirdParty'];

		$eOperationBase = new \journal\Operation([
			'thirdParty' => $eOperation['thirdParty'],
			'document' => $eOperation['document'],
			'documentDate' => $eOperation['date'] ?? $eOperation['documentDate'],
			'financialYear' => $eOperation['financialYear'],
			'paymentMethod' => $eOperation['paymentMethod'],
			'paymentDate' => $eCashflow['date'],
			'journalCode' => $eOperation['journalCode'],
			'hash' => $hash
		]);

		// Écriture en 512
		$eOperationBank = \journal\OperationLib::createBankOperationFromCashflow($eCashflow, $eOperationBase);

		// Contrepartie en 411
		$eOperationThirdParty = clone $eOperationBank;
		unset($eOperationThirdParty['id']);
		$eOperationThirdParty['account'] = \account\AccountLib::getByClass(\account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS);
		$eOperationThirdParty['accountLabel'] = $eThirdParty['clientAccountLabel'];
		$eOperationThirdParty['description'] = $eOperation['description'];
		$eOperationThirdParty['type'] = $eOperationBank['type'] === \journal\Operation::DEBIT ? \journal\Operation::CREDIT : \journal\Operation::DEBIT;
		$eOperationThirdParty['thirdParty'] = $eThirdParty;
		\journal\Operation::model()->insert($eOperationThirdParty);

		// On relie l'opération en 411 au cashflow
		$eOperationCashflow = new \journal\OperationCashflow([
			'operation' => $eOperationThirdParty,
			'cashflow' => $eCashflow,
			'amount' => $eOperationThirdParty['amount'],
		]);
		\journal\OperationCashflow::model()->insert($eOperationCashflow);

		// Cashflow passe en alloué
		\bank\Cashflow::model()->update($eCashflow, ['status' => \bank\Cashflow::ALLOCATED, 'updatedAt' => new \Sql('NOW()')]);

		// Faire le lettrage entre l'operationThirdParty nouvellement créée directement avec l'opération de base
		\journal\LetteringLib::letterOperations(
			$eOperationThirdParty['type'] === \journal\Operation::CREDIT ? $eOperationThirdParty : $eOperation,
			$eOperationThirdParty['type'] === \journal\Operation::DEBIT ? $eOperationThirdParty : $eOperation
		);

		// Mise à jour les memos du tiers
		$eThirdParty = \account\ThirdPartyLib::recalculateMemos($eCashflow, $eThirdParty);
		\account\ThirdParty::model()
			->select('memos')
			->update($eThirdParty);

		// Invalidation de toutes les suggestions de l'opération et du cashflow concernés (out)
		Suggestion::model()
			->where(new \Sql('operation = '.$eOperation['id'].' OR cashflow = '.$eCashflow['id']))
			->whereStatus(Suggestion::WAITING)
			->update(['status' => Suggestion::OUT]);

		// Invalidation de la suggestion validée
		Suggestion::model()->update($eSuggestion, ['status' => Suggestion::VALIDATED]);

		// Marquer vente ou facture comme payée
		if($eOperation->importType() === \journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE) {

			\selling\Invoice::model()
				->whereAccountingHash($eOperation['hash'])
				->wherePaymentStatus(\selling\Invoice::NOT_PAID)
				->update(['paymentStatus' => \selling\Invoice::PAID]);

		} else if($eOperation->importType() === \journal\JournalSetting::HASH_LETTER_IMPORT_SALE) {

			\selling\Sale::model()
				->whereAccountingHash($eOperation['hash'])
				->wherePaymentStatus(\selling\Sale::NOT_PAID)
				->update(['paymentStatus' => \selling\Sale::PAID]);

		}


		\journal\Operation::model()->commit();

	}

}
