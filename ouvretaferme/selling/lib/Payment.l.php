<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	public static function getByCheckoutId(string $id): Payment {

		return Payment::model()
			->select(Payment::getSelection())
			->whereCheckoutId($id)
			->get();

	}

	public static function getByPaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): Payment {

		$ePayment = Payment::model()
			->select(Payment::getSelection())
			->wherePaymentIntentId($id)
			->get();

		if($ePayment->notEmpty()) {
			return $ePayment;
		}

		self::associatePaymentIntentId($eStripeFarm, $id);

		return Payment::model()
			->select(Payment::getSelection())
			->wherePaymentIntentId($id)
			->get();

	}

	public static function associatePaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): void {

		$checkout = \payment\StripeLib::getStripeCheckoutSessionFromPaymentIntent($eStripeFarm, $id);

		if(isset($checkout['error'])) {
			throw new \Exception(var_export($checkout['error'], TRUE));
		}

		Payment::model()
			->whereCheckoutId($checkout['data'][0]['id'])
			->update([
				'paymentIntentId' => $id
			]);

	}

	public static function hasSuccess(Sale $eSale): bool {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereStatus(Payment::SUCCESS)
			->exists();

	}

	public static function updateStatus(string $id, string $newStatus): int {

		return Payment::model()
			->wherePaymentIntentId($id)
			->update([
				'status' => $newStatus
			]);

	}

	public static function createBySale(Sale $eSale, string $providerId = NULL): Payment {

		$eSale->expects(['customer', 'farm']);

		$e = new Payment([
			'sale' => $eSale,
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'checkoutId' => $providerId
		]);

		Payment::model()->insert($e);

		return $e;

	}

}
?>
