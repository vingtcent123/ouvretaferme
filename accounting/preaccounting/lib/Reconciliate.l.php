<?php
namespace preaccounting;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\Collection $cSuggestion): void {

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eSuggestion);
		}

	}

	public static function reconciliateSuggestion(\preaccounting\Suggestion $eSuggestion): void {

		\selling\Payment::model()->beginTransaction();

			$eCashflow = $eSuggestion['cashflow'];

			$ePayment = $eSuggestion['payment'];

			if($ePayment->notEmpty()) {

				$ePayment['cashflow'] = $eCashflow;
				$ePayment['method'] = $eSuggestion['paymentMethod'];
				$ePayment['status'] = \selling\Payment::PAID;
				$ePayment['amountIncludingVat'] = $eCashflow['amount'];
				$ePayment['paidAt'] = $eCashflow['date'];

				\selling\PaymentLib::updateForReconciliation($ePayment, ['cashflow']);
				\selling\PaymentLib::update($ePayment, ['method', 'status', 'amountIncludingVat', 'paidAt']);

			} else {

				$eInvoice = \selling\InvoiceLib::getById($eSuggestion['invoice']['id']);

				$ePayment = new \selling\Payment([
					'cashflow' => $eCashflow,
					'status' => \selling\Payment::PAID,
					'amountIncludingVat' => $eCashflow['amount'],
					'paidAt' => $eCashflow['date'],
					'method' => $eSuggestion['paymentMethod'],
				]);

				\selling\PaymentTransactionLib::createForTransaction($eInvoice, $ePayment);
			}


			\bank\Cashflow::model()->update($eCashflow, [
				'isReconciliated' => TRUE,
				'payment' => $ePayment,
				'updatedAt' => new \Sql('NOW()'),
			]);

			self::invalidateSuggestions($eSuggestion);

		\selling\Payment::model()->commit();

	}

	public static function invalidateSuggestions(Suggestion $eSuggestionValidated): void {

		// Invalidation de toutes les suggestions de l'opération, de la facture, de la vente et du cashflow concernés (out)
		\preaccounting\Suggestion::model()
			->or(
				fn() => $this->whereCashflow($eSuggestionValidated['cashflow']),
				fn() => $this->wherePayment($eSuggestionValidated['payment']),
			)
			->whereStatus(\preaccounting\Suggestion::WAITING)
			->update(['status' => \preaccounting\Suggestion::OUT]);

		// Enregistrement de la suggestion validée
		\preaccounting\Suggestion::model()->update($eSuggestionValidated, ['status' => \preaccounting\Suggestion::VALIDATED]);

	}

	public static function cancelReconciliation(\farm\Farm $eFarm, \bank\Cashflow $eCashflow): void {

		\selling\Payment::model()->beginTransaction();

			\selling\PaymentLib::cancelReconciliation($eCashflow);

			\bank\Cashflow::model()->update($eCashflow, ['payment' => NULL, 'isReconciliated' => FALSE, 'isSuggestionCalculated' => FALSE]);

			Suggestion::model()
				->whereCashflow($eCashflow)
				->delete();

			SuggestionLib::calculateForCashflow($eFarm, $eCashflow);

		\selling\Payment::model()->commit();

	}

}
