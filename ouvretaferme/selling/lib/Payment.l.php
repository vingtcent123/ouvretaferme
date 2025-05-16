<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	public static function delegateBySale(): PaymentModel {

		return Payment::model()
      ->select(Payment::getSelection() + ['method' => ['id', 'fqn', 'name', 'status']])
			->sort(['id' => SORT_DESC])
      ->delegateCollection('sale', 'id', function(\Collection $cPayment) {

				$cPaymentFiltered = new \Collection();

				foreach($cPayment as $ePayment) {

					if(
						$ePayment['method']->empty()
						or $cPaymentFiltered->contains(fn($e) => $e['method']['id'] === $ePayment['method']['id'])
					) {
						continue;
					}

					$cPaymentFiltered->append($ePayment);

				}

				return $cPaymentFiltered;
      });
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
			->whereOnlineStatus(Payment::SUCCESS)
			->exists();

	}

	public static function updateByPaymentIntentId(string $id, array $properties): int {

		return Payment::model()
			->wherePaymentIntentId($id)
			->update($properties);

	}

	public static function updateStatus(string $id, string $newStatus): int {

		return Payment::model()
			->wherePaymentIntentId($id)
			->update([
				'status' => $newStatus
			]);

	}

	public static function createBySale(Sale $eSale, ?\payment\Method $eMethod, ?string $providerId = NULL): Payment {

		if($eMethod->empty()) {
			return new Payment();
		}

		$eSale->expects(['customer', 'farm']);

		$amount = $eSale['priceIncludingVat'] - self::sumTotalBySale($eSale);
		$onlineStatus = ($eMethod['fqn'] ?? NULL) === \payment\MethodLib::ONLINE_CARD ? Payment::INITIALIZED : NULL;

		$e = new Payment([
			'sale' => $eSale,
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'checkoutId' => $providerId,
			'method' => $eMethod,
			'amountIncludingVat' => $amount,
			'onlineStatus' => $onlineStatus,
		]);

		Payment::model()->insert($e);

		return $e;

	}

	public static function sumTotalBySale(Sale $eSale): float {

		$ePayment = new Payment();
		Payment::model()
			->select(['sum' => new \Sql('SUM(amountIncludingVat)')])
			->whereSale($eSale)
			->get($ePayment);

		return $ePayment['sum'] ?? 0;

	}

	public static function fill(Sale $eSale, \payment\Method $eMethod): void {

		$cPayment = Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->getCollection();

		// Paiement non trouvé
		$cPaymentFound = $cPayment->find(fn($ePayment) => (($ePayment['method']['id']) ?? NULL) === $eMethod['id']);
		if($cPaymentFound->count() === 0) {
			return;
		}

		$ePayment = $cPaymentFound->first();

		$total = $cPayment->reduce(fn($e, $value) => ($e['id'] !== $ePayment['id']) ? ($e['amountIncludingVat'] + $value) : $value, 0);

		$ePayment['amountIncludingVat'] = $eSale['priceIncludingVat'] - $total;

		Payment::model()
			->select('amountIncludingVat')
			->update($ePayment);

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

	public static function deleteBySaleAndMethod(Sale $eSale, \payment\Method $eMethod): void {

		Payment::model()
			->whereSale($eSale)
			->whereMethod($eMethod)
			->delete();

	}

	public static function deleteBySale(Sale $eSale): void {

		$eSale->expects(['id', 'farm', 'customer']);

		Payment::model()
			->whereSale($eSale)
			->whereFarm($eSale['farm'])
			->whereCustomer($eSale['customer'])
			->delete();
	}

}
?>
