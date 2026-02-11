<?php
namespace preaccounting;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\Collection $cSuggestion): void {

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eSuggestion);
		}

	}

	public static function reconciliateSuggestion(\preaccounting\Suggestion $eSuggestion): void {

		Suggestion::model()->beginTransaction();

			$eCashflow = $eSuggestion['cashflow'];

			$ePayment = $eSuggestion['payment'];

			$eInvoice = \selling\InvoiceLib::getById($eSuggestion['invoice']['id']);

			$ePayment['cashflow'] = $eCashflow;
			$ePayment['method'] = $eSuggestion['paymentMethod'];
			$ePayment['status'] = \selling\Payment::PAID;
			$ePayment['amountIncludingVat'] = $eCashflow['amount'];
			$ePayment['paidAt'] = $eCashflow['date'];
			$ePayment['invoice'] = $eInvoice;

			if(
				$ePayment->exists() or
				$ePayment['amountIncludingVat'] === $eInvoice['priceIncludingVat']
			) {

				\selling\PaymentTransactionLib::replace($eInvoice, new \Collection([$ePayment]), ['cashflow']);

			} else if($ePayment->exists() === FALSE and $ePayment['amountIncludingVat'] <= ($eInvoice['priceIncludingVat'] - $eInvoice['paymentAmount'])) {

				\selling\PaymentTransactionLib::add($eInvoice, new \Collection([$ePayment]));

			}

			$ePayment = \selling\Payment::model()
				->select('id')
				->whereInvoice($eInvoice)
				->get();

			\bank\Cashflow::model()->update($eCashflow, [
				'isReconciliated' => TRUE,
				'payment' => $ePayment,
				'updatedAt' => new \Sql('NOW()'),
			]);

			self::invalidateSuggestions($eSuggestion);

		Suggestion::model()->commit();

	}

	public static function invalidateSuggestions(Suggestion $eSuggestionValidated): void {

		Suggestion::model()->beginTransaction();

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

		Suggestion::model()->commit();
	}

	public static function cancelReconciliation(\farm\Farm $eFarm, \bank\Cashflow $eCashflow): void {

		Suggestion::model()->beginTransaction();

			\selling\PaymentAccountingLib::cancelReconciliation($eCashflow);

			\bank\Cashflow::model()->update($eCashflow, ['payment' => NULL, 'isReconciliated' => FALSE, 'isSuggestionCalculated' => FALSE]);

			Suggestion::model()
				->whereCashflow($eCashflow)
				->delete();

			SuggestionLib::calculateForCashflow($eFarm, $eCashflow);

		Suggestion::model()->commit();

	}

}
