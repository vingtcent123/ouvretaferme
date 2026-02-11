<?php
namespace selling;

class PaymentAccountingLib extends PaymentCrud {

	public static function setImported(Payment $ePayment, string $hash): void {

		if($ePayment->exists() === FALSE) {
			throw new \NotExpectedAction();
		}

		$ePayment['hash'] = $hash;
		Payment::model()->update($ePayment, ['hash']);

		PaymentLib::close($ePayment);

	}

	/**
	 * Supprime le lien entre le paiement et la comptabilité
	 */
	public static function cancelAccounting(string $hash): void {

		\selling\Payment::model()
			->whereAccountingHash($hash)
			->update(['accountingHash' => NULL, 'accountingDifference' => NULL]);

	}

	public static function cancelReconciliation(\bank\Cashflow $eCashflow): void {

		if($eCashflow->exists() === FALSE) {
			throw new \NotExpectedAction();
		}

		\selling\Payment::model()
			->whereCashflow($eCashflow)
			->update(['cashflow' => NULL, 'accountingReady' => FALSE]);

	}

	public static function sumTotalPaid(Payment $ePayment): float {

		return Payment::model()
			->select('amountIncludingVat')
			->whereStatus(\selling\Payment::PAID)
			->whereInvoice($ePayment['invoice'], if: $ePayment['source'] === Payment::INVOICE)
			->whereSale($ePayment['sale'], if: $ePayment['source'] === Payment::SALE)
			->getCollection()
			->sum('amountIncludingVat');

	}


}
?>
