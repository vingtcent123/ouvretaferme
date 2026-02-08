<?php
namespace selling;

class PaymentOnlineLib {

	public static function getByPaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): Payment {

		$ePayment = new Payment();

		Payment::model()
			->select(Payment::getSelection())
			->whereOnlinePaymentIntentId($id)
			->get($ePayment);

		if($ePayment->notEmpty()) {
			return $ePayment;
		}

		self::associatePaymentIntentId($eStripeFarm, $id);

		Payment::model()
			->select(Payment::getSelection())
			->whereOnlinePaymentIntentId($id)
			->get($ePayment);

		return $ePayment;

	}

	public static function associatePaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): void {

		try {
			$checkout = \payment\StripeLib::getStripeCheckoutSessionFromPaymentIntent($eStripeFarm, $id);
		}
		catch(\Exception $e) {
			trigger_error("Stripe: ".$e->getMessage());
			return;
		}

		if($checkout['data'] === []) {
			return;
		}

		Payment::model()
			->whereOnlineCheckoutId($checkout['data'][0]['id'])
			->update([
				'onlinePaymentIntentId' => $id
			]);

	}

	public static function failByPaymentIntentId(Sale $eSale, string $paymentIntentId): bool {

		\selling\Payment::model()->beginTransaction();

			$affected = Payment::model()
				->whereSale($eSale)
				->whereOnlinePaymentIntentId($paymentIntentId)
				->update([
					'status' => \selling\Payment::FAILED
				]);

			if($affected > 0) {
				PaymentTransactionLib::recalculate($eSale);
			}

		\selling\Payment::model()->commit();

		return ($affected > 0);

	}

	public static function payByPaymentIntentId(Sale $eSale, string $paymentIntentId, float $amount): int {

		\selling\Payment::model()->beginTransaction();

			$affected = Payment::model()
				->whereSale($eSale)
				->whereOnlinePaymentIntentId($paymentIntentId)
				->update([
					'status' => Payment::PAID,
					'amountIncludingVat' => $amount,
					'paidAt' => currentDate()
				]);

			if($affected > 0) {
				PaymentTransactionLib::recalculate($eSale);
			}

		\selling\Payment::model()->commit();

		return ($affected > 0);

	}

	/**
	 * Expire les paiements en ligne
	 * OU supprime tous les autres moyens de paiement
	 * pour une vente donnée.
	 *
	 */
	public static function expiresPaymentSessions(Sale $eSale): void {

		// On expire toutes les sessions paiement en ligne
		$cPayment = Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereStatus(Payment::NOT_PAID)
			->whereOnlineCheckoutId('!=', NULL)
			->getCollection();

		if($cPayment->notEmpty()) {

			$eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);

			if($eStripeFarm->notEmpty()) {

				foreach($cPayment as $ePayment) {

					try {
						\payment\StripeLib::expiresCheckoutSession($eStripeFarm, $ePayment['onlineCheckoutId']);
					} catch(\payment\StripeException) {
					}

				}

			}

		}

		PaymentTransactionLib::delete($eSale);

	}

}
?>
