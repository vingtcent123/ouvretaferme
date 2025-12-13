<?php
namespace preaccounting;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\farm\Farm $eFarm, \Collection $cSuggestion): void {

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eFarm, $eSuggestion);
		}

	}

	/**
	 * Rapprochement bancaire
	 * - si opération => opération en 512 et en 411 + lettrage
	 * - si facture ou vente
	 *    => marquer la facture ou la vente comme payée et la date de paiement à la date du cashflow
	 *    => si la facture ou la vente est rattachée à une opération (hash) => faire les opérations au dessus
	 * - dans tous les cas :
	 *    => marquer le cashflow comme traité
	 *    => supprimer les suggestions pour le cashflow / l'opération / la facture / la vente
	 */
	public static function reconciliateSuggestion(\farm\Farm $eFarm, \preaccounting\Suggestion $eSuggestion): void {

		\journal\Operation::model()->beginTransaction();

		$eOperation = $eSuggestion['operation'];
		$eCashflow = $eSuggestion['cashflow'];
		$eInvoice = $eSuggestion['invoice'];
		$eSale = $eSuggestion['sale'];
		$hashLinked = NULL;
		$createOperations = FALSE;

		// On commence par importer la facture ou la vente si on utilise la comptabilité
		if($eFarm->usesAccounting()) {

			if($eInvoice->notEmpty()) {

				$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($eInvoice['date']);

				if(($eFinancialYear->isCashAccrualAccounting() or $eFinancialYear->isAccrualAccounting()) and $eInvoice['accountingHash'] === NULL) {

					ImportLib::importInvoice($eFarm, $eInvoice, $eFinancialYear);
					$eInvoice = \selling\InvoiceLib::getById($eInvoice['id']);

					$hashLinked = $eInvoice['accountingHash'];
					$eOperation = \journal\Operation::model()
						->select(\journal\Operation::getSelection())
						->whereHash($eInvoice['accountingHash'])
						->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
						->get();
					$eSuggestion['operation'] = $eOperation; // permettra le nettoyage des suggestions calculées lors de l'import

					$createOperations = TRUE;
				}

			} else if($eSale->notEmpty()) {

				$eFinancialYear = \account\FinancialYearLib::getOpenFinancialYearByDate($eSale['deliveredAt']);

				if(($eFinancialYear->isCashAccrualAccounting() or $eFinancialYear->isAccrualAccounting()) and $eSale['accountingHash'] === NULL) {

					ImportLib::importSale($eFarm, $eSale, $eFinancialYear);
					$eSale = \selling\SaleLib::getById($eSale['id']);

					$hashLinked = $eSale['accountingHash'];
					$eOperation = \journal\Operation::model()
						->select(\journal\Operation::getSelection())
						->whereHash($eSale['accountingHash'])
						->whereAccountLabel('LIKE', \account\AccountSetting::THIRD_ACCOUNT_RECEIVABLE_DEBT_CLASS.'%')
						->get();
					$eSuggestion['operation'] = $eOperation; // permettra le nettoyage des suggestions calculées lors de l'import

					$createOperations = TRUE;

				}

			} else if($eOperation->notEmpty()) {

				$hashLinked = $eOperation['hash'];
				$createOperations = TRUE;

			}

		}

		if($createOperations) {

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

		}

		// Cashflow passe en alloué
		\bank\Cashflow::model()->update($eCashflow, ['status' => \bank\Cashflow::ALLOCATED, 'updatedAt' => new \Sql('NOW()')]);

		// Invalidation de toutes les suggestions de l'opération, de la facture, de la vente et du cashflow concernés (out)
		$conditions = ['cashflow = '.$eCashflow['id']];
		if($eSuggestion['operation']->notEmpty()) {
			$conditions[] = 'operation = '.$eSuggestion['operation']['id'];
		}
		if($eSuggestion['sale']->notEmpty()) {
			$conditions[] = 'sale = '.$eSuggestion['sale']['id'];
		}
		if($eSuggestion['invoice']->notEmpty()) {
			$conditions[] = 'sale = '.$eSuggestion['invoice']['id'];
		}
		\preaccounting\Suggestion::model()
			->where(new \Sql(join(' OR ', $conditions)))
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->update(['status' => \preaccounting\Suggestion::OUT]);

		// Invalidation de la suggestion validée
		\preaccounting\Suggestion::model()->update($eSuggestion, ['status' => \preaccounting\Suggestion::VALIDATED]);

		// Marquer vente ou facture comme payée
		if($hashLinked !== NULL) {

			switch(mb_substr($hashLinked, -1)) {

				case \journal\JournalSetting::HASH_LETTER_IMPORT_INVOICE:
					\selling\Invoice::model()
						->whereAccountingHash($eOperation['hash'])
						->wherePaymentStatus(\selling\Invoice::NOT_PAID)
						->update(['paymentStatus' => \selling\Invoice::PAID]);
					break;

				case \journal\JournalSetting::HASH_LETTER_IMPORT_SALE:
					\selling\Sale::model()
						->whereAccountingHash($eOperation['hash'])
						->wherePaymentStatus(\selling\Sale::NOT_PAID)
						->update(['paymentStatus' => \selling\Sale::PAID]);
			}
		}

		\journal\Operation::model()->commit();

	}

}
