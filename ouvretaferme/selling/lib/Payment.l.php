<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	public static function getByCheckoutId(string $id): Payment {

		$ePayment = new Payment();

		Payment::model()
			->select(Payment::getSelection())
			->whereCheckoutId($id)
			->get($ePayment);

		return $ePayment;

	}

	public static function getByPaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): Payment {

		$ePayment = new Payment();

		Payment::model()
			->select(Payment::getSelection())
			->wherePaymentIntentId($id)
			->get($ePayment);

		if($ePayment->notEmpty()) {
			return $ePayment;
		}

		self::associatePaymentIntentId($eStripeFarm, $id);

		Payment::model()
			->select(Payment::getSelection())
			->wherePaymentIntentId($id)
			->get($ePayment);

		return $ePayment;

	}

	public static function associatePaymentIntentId(\payment\StripeFarm $eStripeFarm, string $id): void {

		try {
			$checkout = \payment\StripeLib::getStripeCheckoutSessionFromPaymentIntent($eStripeFarm, $id);
		}
		catch(\Exception $e) {
			trigger_error("Stripe: ", $e->getMessage());
			return;
		}

		if($checkout['data'] === []) {
			return;
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

	public static function createBySale(Sale $eSale, ?\payment\Method $eMethod, ?string $providerId = NULL): Payment {

		$eSale->expects(['customer', 'farm']);

		$e = new Payment([
			'sale' => $eSale,
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'checkoutId' => $providerId,
			'method' => $eMethod,
		]);

		Payment::model()->insert($e);

		return $e;

	}

	public static function getBySale(Sale $eSale): \Collection {

		return Payment::model()
			->select(Payment::getSelection() + ['method' => \payment\Method::getSelection()])
			->whereSale($eSale)
			->getCollection();

	}

	public static function getBySaleAndMethod(Sale $eSale, ?string $methodFqn): Payment {

		$ePayment = new Payment();

		if($methodFqn !== NULL) {
			$eMethod = \payment\MethodLib::getByFqn($methodFqn);
		} else {
			$eMethod = new \payment\Method();
		}

		Payment::model()
			->select(Payment::getSelection() + ['method' => \payment\Method::getSelection()])
			->whereSale($eSale)
			->whereMethod($eMethod)
			->get($ePayment);

		return $ePayment;
	}

	public static function updateOrCreateBySale(Sale $eSale, \payment\Method $eMethod, string $status = Payment::PENDING): void {

		$eSale->expects(['customer', 'farm', 'priceIncludingVat']);

		$ePayment = new Payment([
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'sale' => $eSale,
			'method' => $eMethod,
			'status' => $status,
			'amountIncludingVat' => $eSale['priceIncludingVat'],
		]);

		Payment::model()->beginTransaction();

		$affected = Payment::model()
			->select(['method', 'status', 'amountIncludingVat'])
			->whereSale($eSale)
			->whereCustomer($eSale['customer'])
			->update($ePayment);

		if($affected === 0) {
			self::createBySale($eSale, $eMethod);
		}
		Payment::model()->commit();

	}

	public static function updateBySaleStatus(Sale $eSale): void {

		$eSale->expects(['customer', 'farm', 'priceIncludingVat', 'preparationStatus']);

		$ePayment = new Payment([
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'sale' => $eSale,
			'status' => match($eSale['preparationStatus']) {
				Sale::DELIVERED => Payment::SUCCESS,
				Sale::DRAFT => Payment::PENDING,
			},
			'amountIncludingVat' => $eSale['priceIncludingVat'],
		]);

		Payment::model()
       ->select(['status', 'amountIncludingVat'])
       ->whereSale($eSale)
       ->whereFarm($eSale['farm'])
       ->whereCustomer($eSale['customer'])
       ->update($ePayment);
	}

}
?>
