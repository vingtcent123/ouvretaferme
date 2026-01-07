<?php
namespace preaccounting;

Class ReconciliateLib {

	public static function reconciliateSuggestionCollection(\farm\Farm $eFarm, \Collection $cSuggestion): void {

		$cPaymentMethod = \payment\MethodLib::getByFarm($eFarm, FALSE);

		foreach($cSuggestion as $eSuggestion) {
			self::reconciliateSuggestion($eFarm, $eSuggestion, $cPaymentMethod);
		}

	}
	public static function reconciliateSuggestion(\farm\Farm $eFarm, \preaccounting\Suggestion $eSuggestion): void {

		\selling\Invoice::model()->beginTransaction();

		$eCashflow = $eSuggestion['cashflow'];
		$eInvoice = $eSuggestion['invoice'];

		\bank\Cashflow::model()->update($eCashflow, [
			'isReconciliated' => TRUE,
			'invoice' => $eInvoice,
			'updatedAt' => new \Sql('NOW()'),
		]);

		$cSale = \selling\SaleLib::getByInvoice($eInvoice);

		if($eInvoice->notEmpty()) {

			$updateInvoice = [
				'paymentStatus' => \selling\Invoice::PAID,
				'paymentMethod' => $eSuggestion['paymentMethod'],
				'cashflow' => $eCashflow,
			];

			\selling\Invoice::model()->update($eInvoice, $updateInvoice);

		}

		if($cSale->notEmpty()) {

			$updateSale = ['paymentStatus' => \selling\Sale::PAID];

			\selling\Sale::model()
				->whereId('IN', $cSale->getIds())
				->wherePaymentStatus(\selling\Sale::NOT_PAID)
				->update($updateSale);

			$cPayment = new \Collection();

			foreach($cSale as $eSale) {

				if($eSale['cPayment']->notEmpty()) {
					\selling\PaymentLib::deleteBySale($eSale);
				}

				$cPayment->append(new \selling\Payment([
					'method' => $eSuggestion['paymentMethod'],
					'sale' => $eSale,
					'farm' => $eFarm,
					'amountIncludingVat' => $eInvoice['priceIncludingVat']
				]));

			}

			\selling\Payment::model()->insert($cPayment);

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

			\selling\Invoice::model()->update($eInvoice, ['cashflow' => NULL]);

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
