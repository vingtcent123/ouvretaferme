<?php
namespace preaccounting;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\Collection $cSuggestion): void {

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eSuggestion);
		}

	}
	public static function reconciliateSuggestion(\preaccounting\Suggestion $eSuggestion): void {

		\selling\Invoice::model()->beginTransaction();

		$eCashflow = $eSuggestion['cashflow'];
		$eInvoice = $eSuggestion['invoice'];

		\bank\Cashflow::model()->update($eCashflow, [
			'isReconciliated' => TRUE,
			'invoice' => $eInvoice,
			'updatedAt' => new \Sql('NOW()'),
		]);

		if($eInvoice->notEmpty()) {

			\selling\PaymentTransactionLib::replace($eInvoice, new \Collection([
				new \selling\Payment([
					'method' => $eSuggestion['paymentMethod'],
					'status' => \selling\Invoice::PAID,
					'amountIncludingVat' => $eCashflow['amount'],
					'paidAt' => $eCashflow['date'],
				])
			]));

			$eInvoice['cashflow'] = $eCashflow;
			\selling\InvoiceLib::update($eInvoice, ['cashflow']);

		}

		self::invalidateSuggestions($eSuggestion);

		\selling\Invoice::model()->commit();

	}

	public static function invalidateSuggestions(Suggestion $eSuggestionValidated): void {

		// Invalidation de toutes les suggestions de l'opération, de la facture, de la vente et du cashflow concernés (out)
		\preaccounting\Suggestion::model()
			->or(
				fn() => $this->whereCashflow($eSuggestionValidated['cashflow']),
				fn() => $this->whereInvoice($eSuggestionValidated['invoice']),
			)
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->update(['status' => \preaccounting\Suggestion::OUT]);

		// Enregistrement de la suggestion validée
		\preaccounting\Suggestion::model()->update($eSuggestionValidated, ['status' => \preaccounting\Suggestion::VALIDATED]);

	}

	public static function cancelReconciliation(\farm\Farm $eFarm, \bank\Cashflow $eCashflow): void {

		\selling\Invoice::model()->beginTransaction();

		$eInvoice = \selling\Invoice::model()
			->select(\selling\Invoice::getSelection())
			->whereCashflow($eCashflow)
			->get();

		if($eInvoice->notEmpty()) {

			\selling\Invoice::model()->update($eInvoice, ['cashflow' => NULL, 'readyForAccounting' => FALSE]);

			\bank\Cashflow::model()->update($eCashflow, ['invoice' => NULL, 'isReconciliated' => FALSE, 'isSuggestionCalculated' => FALSE]);

			Suggestion::model()
				->or(
					fn() => $this->whereCashflow($eCashflow),
					fn() => $this->whereInvoice($eInvoice),
				)
				->delete();

			SuggestionLib::calculateForCashflow($eFarm, $eCashflow);

		}

		\selling\Invoice::model()->commit();

	}

}
