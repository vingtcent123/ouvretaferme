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

	public static function updateByPaymentIntentId(string $id, array $properties): int {

		return Payment::model()
			->whereOnlinePaymentIntentId($id)
			->update($properties);

	}

	/**
	 * Expire les paiements par CB en ligne
	 * OU supprime tous les autres moyens de paiement
	 * pour une vente donnée.
	 *
	 */
	public static function expiresPaymentSessions(Sale $eSale): void {

		// On expire toutes les sessions paiement en ligne
		$cPayment = Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereStatusOnline(Payment::INITIALIZED)
			->getCollection();

		if($cPayment->notEmpty()) {
			$eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);
		}

		foreach($cPayment as $ePayment) {

			if($eStripeFarm->notEmpty() and $ePayment['method']->isOnline()) {

				try {
					\payment\StripeLib::expiresCheckoutSession($eStripeFarm, $ePayment['onlineCheckoutId']);
				} catch(\payment\StripeException) {
				}

				Payment::model()->update($ePayment, ['statusOnline' => Payment::EXPIRED]);

			}

		}

		// On supprime tous les paiements autres que en ligne
		Payment::model()
			->join(\payment\Method::model(), 'm1.method = m2.id')
			->whereSale($eSale)
			->where('m2.online = 0')
			->delete();

		// On réinitialise le statut de la vente
		$properties = ['paymentStatus' => NULL, 'onlinePaymentStatus' => NULL];
		Sale::model()->update($eSale, $properties);

	}

	public static function hasSuccess(Sale $eSale): bool {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereStatusOnline(Payment::SUCCESS)
			->exists();

	}

}
?>
