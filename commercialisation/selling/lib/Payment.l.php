<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	// Les accès CRUD sont contrôlés par des méthodes spécialisées

	public static function create(Payment $e): void {
		throw new \UnsupportedException();
	}

	public static function update(Payment $e, array $properties): void {

		if(array_diff($properties, ['method', 'amountIncludingVat']) !== []) {
			throw new \UnsupportedException();
		}

		if(in_array('method', $properties)) {

			$e['method']->expects(['name']);

			$e['methodName'] = $e['method']['name'];
			$properties[] = 'methodName';

		}

		parent::update($e, $properties);

	}

	public static function delete(Payment $ePayment): void {

		Payment::model()->beginTransaction();

			$cPayment = Payment::model()
				->select(Payment::getSelection())
				->whereSource($ePayment['source'])
				->whereSale($ePayment['sale'])
				->getCollection(index: 'id');

			if($cPayment->offsetExists($ePayment['id']) === FALSE) {
				return;
			}

			$ePayment = $cPayment[$ePayment['id']];

			dd($ePayment);

		Payment::model()->commit();

	}

	public static function delegateBySale(): PaymentModel {

		return Payment::model()
      	->select(Payment::getSelection())
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

	public static function getBySale(Sale $eSale, bool $onlyPaid = FALSE): \Collection {

		$cPayment = Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

		if($onlyPaid) {
			$cPayment->filter(fn($ePayment) => $ePayment->isPaid());
		}

		return $cPayment;

	}

	public static function getByMethod(Sale $eSale, \payment\Method $eMethod): Payment {

		$ePayment = new Payment();

		Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereMethod($eMethod)
			->sort(['createdAt' => SORT_DESC])
			->get($ePayment);

		return $ePayment;
	}

	/**
	 * Remplit la vente avec le moyen de paiement renseigné pour que le total des paiements corresponde au total de la vente
	 * Si aucun moyen de paiement n'est renseigné, on utilise le moyen de paiement actuel de la vente si il est renseigné et qu'il n'y en a qu'un
	 * Sinon, on utilise le moyen de paiement par défaut
	 */
	public static function fillByMethod(Sale $eSale, \payment\Method $eMethod = new \payment\Method()): void {

		if(
			$eMethod->notEmpty() and
			$eMethod->isOnline()
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$cPayment = self::getBySale($eSale, onlyPaid: TRUE);

			if($eMethod->empty()) {

				$eMethodDefault = $eSale['farm']->getConf('marketSalePaymentMethod');

				if($cPayment->count() === 1) {

					$eMethod = $cPayment->first()['method'];

				} else if($eMethodDefault->notEmpty()) {

					$eMethod = \payment\MethodLib::getById($eMethodDefault['id']);

				} else {

					Payment::model()->commit();
					return;

				}

			}

			$currentAmount = 0;
			$ePaymentWithMethod = new \payment\Method();

			foreach($cPayment as $ePayment) {

				if($ePayment->isPaid()) {

					$currentAmount += $ePayment['amountIncludingVat'];

					if($ePayment['method']->is($eMethod)) {
						$ePaymentWithMethod = $ePayment;
					}

				}

			}

			if($ePaymentWithMethod->notEmpty()) {

				$fillAmount =  $eSale['priceIncludingVat'] - $currentAmount + $ePaymentWithMethod['amountIncludingVat'];

				Payment::model()
					->whereSale($eSale)
					->whereMethod($eMethod)
					->update([
						'amountIncludingVat' => $fillAmount
					]);

			} else {

				$fillAmount = max(0.0, $eSale['priceIncludingVat'] - $currentAmount);

				self::createByMethod($eSale, $eMethod, $fillAmount);

			}

		Payment::model()->commit();

	}

	public static function createByMethod(Sale $eSale, \payment\Method $eMethod, ?float $amount = NULL, ?string $checkoutId = NULL): Payment {

		if($eMethod->empty()) {
			return new Payment();
		}

		$eMethod->expects([
			'fqn',
			'name'
		]);

		$eSale->expects([
			'farm', 'customer'
		]);

		$ePayment = new Payment([
			'source' => Payment::SALE,
			'sale' => $eSale,
			'customer' => $eSale['customer'],
			'farm' => $eSale['farm'],
			'onlineCheckoutId' => $checkoutId,
			'method' => $eMethod,
			'methodName' => $eMethod['name'],
			'amountIncludingVat' => $amount ?? $eSale['priceIncludingVat'],
			'status' => ($eMethod['fqn'] === \payment\MethodLib::ONLINE_CARD) ? Payment::NOT_PAID : $eSale['paymentStatus'],
		]);

		Payment::model()->insert($ePayment);

		return $ePayment;

	}

	public static function deleteByMethod(Sale $eSale, \payment\Method $eMethod): void {

		Payment::model()
			->whereSale($eSale)
			->whereMethod($eMethod)
			->delete();

	}

	public static function updateMethod(Payment $ePayment, \payment\Method $eMethod): void {

		$ePayment->expects(['id', 'sale', 'method']);

		$eSale = $ePayment['sale'];

		$isExisting = Payment::model()
			->whereSale($eSale)
			->whereMethod($eMethod)
			->exists();

		if($isExisting) {
			return;
		}

		$ePayment['method'] = $eMethod;

		self::update($ePayment, ['method']);

	}

	public static function replaceBySale(Sale $eSale, \payment\Method $eMethod, ?float $amount = NULL, ?string $checkoutId = NULL): void {

		self::deleteBySale($eSale);
		self::createByMethod($eSale, $eMethod, $amount, $checkoutId);

	}

	public static function replaceSeveralBySale(Sale $eSale, array $values): void {

		self::deleteBySale($eSale);

		foreach($values as $value) {
			PaymentLib::createByMethod($eSale, $value['method'], $value['amount']);
		}

	}

	public static function deleteBySale(Sale $eSale): void {

		$eSale->expects(['id']);

		Payment::model()
			->whereSale($eSale)
			->delete();

	}

	public static function cleanBySale(Sale $eSale): void {

		$eSale->expects(['id', 'farm', 'cPayment']);

		Payment::model()
			->whereSale($eSale)
			->whereFarm($eSale['farm'])
			->where('amountIncludingVat IS NULL OR amountIncludingVat = 0')
			->delete();

	}

}
?>
