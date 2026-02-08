<?php
namespace selling;

class PaymentTransactionLib {

	public static function getAll(Sale $eSale): \Collection {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

	public static function getByMethod(Sale $eSale, \payment\Method $eMethod): Payment {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($eSale)
			->whereMethod($eMethod)
			->sort(['createdAt' => SORT_DESC])
			->get();
	}

	public static function delegateBySale(): PaymentModel {

		return Payment::model()
      	->select(Payment::getSelection())
			->sort(['id' => SORT_DESC])
			->delegateCollection('sale', 'id');

	}

	/**
	 * Ajoute un moyen de paiement
	 * Si les moyens de paiement sont fournis, ils doivent être garantis par une transaction
	 */
	public static function createForSale(Payment $e): void {

		$e->expects([
			'sale' => ['farm', 'customer'],
		]);

		$e->merge([
			'source' => Payment::SALE,
			'customer' => $e['sale']['customer'],
			'farm' => $e['sale']['farm'],
		]);

		PaymentLib::create($e);

	}

	public static function replace(Sale $eSale, \Collection $cPayment): void {

		$cPayment->expects([
			'method', 'amountIncludingVat', 'status'
		]);

		Payment::model()->beginTransaction();

			self::delete($eSale, recalculate: FALSE);

			if($cPayment->notEmpty()) {

				foreach($cPayment as $ePayment) {

					$ePayment['sale'] = $eSale;

					self::createForSale($ePayment);

				}

			} else {
				PaymentTransactionLib::recalculate($eSale, new \Collection());
			}

		Payment::model()->commit();

	}

	public static function updateNotPaidMethod(Sale $eSale, \payment\Method $eMethod): void {

		if($eMethod->acceptManualUpdate() === FALSE) {
			throw new \UnsupportedException();
		}

		Payment::model()
			->whereSale($eSale)
			->whereStatus(Payment::NOT_PAID)
			->update([
				'method' => $eMethod
			]);

	}

	public static function updatePaid(Sale $eSale): void {

		Payment::model()->beginTransaction();

			$ePayment = Payment::model()
				->select(Payment::getSelection())
				->whereSale($eSale)
				->whereStatus(Payment::NOT_PAID)
				->get();

			if($ePayment->notEmpty()) {

				Sale::model()
					->select('paymentAmount', 'priceIncludingVat')
					->get($eSale);

				$ePayment['amountIncludingVat'] = ($eSale['priceIncludingVat'] - $eSale['paymentAmount']);
				$ePayment['status'] = Payment::PAID;
				$ePayment['paidAt'] = currentDate();
				$ePayment['sale'] = $eSale;

				PaymentLib::update($ePayment, ['amountIncludingVat', 'status', 'paidAt']);

			}

		Payment::model()->commit();

	}

	public static function updateNeverPaid(Sale $eSale): void {

		Payment::model()->beginTransaction();

			self::delete($eSale, recalculate: FALSE);
			self::reset($eSale, Sale::NEVER_PAID);

		Payment::model()->commit();

	}

	public static function updateCustomer(Sale $eSale, Customer $eCustomer): void {

		Payment::model()
			->whereSale($eSale)
			->update([
				'customer' => $eCustomer,
			]);

	}

	public static function delete(Sale $eSale, bool $recalculate = TRUE): void {

		$eSale->expects(['id']);

		Payment::model()->beginTransaction();

			// On expire toutes les sessions paiement en ligne
			$cPayment = self::getAll($eSale);

			if($cPayment->empty()) {
				Payment::model()->commit();
				return;
			}

			foreach($cPayment as $ePayment) {

				if(
					$ePayment['status'] === Payment::NOT_PAID and
					$ePayment['onlineCheckoutId'] !== NULL
				) {

					static $eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);

					if($eStripeFarm->notEmpty()) {

						try {
							\payment\StripeLib::expiresCheckoutSession($eStripeFarm, $ePayment['onlineCheckoutId']);
						} catch(\payment\StripeException) {
						}

					}

				}

			}

			Payment::model()->delete($cPayment);

			if($recalculate) {
				PaymentTransactionLib::recalculate($eSale, new \Collection());
			}

		Payment::model()->commit();

	}

	public static function broadcastInvoice(Invoice $eInvoice): void {

		$eInvoice->expects(['sales']);

		Payment::model()->beginTransaction();

			Invoice::model()
				->select([
					'paymentMethod' => \payment\MethodElement::getSelection(),
					'paymentStatus', 'paidAt'
				])
				->get($eInvoice);

			$cSale = SaleLib::getByIds($eInvoice['sales']);

			foreach($cSale as $eSale) {

				if($eInvoice['paymentMethod']->notEmpty()) {

					$cPayment = new \Collection([
						new Payment([
							'method' => $eInvoice['paymentMethod'],
							'status' => $eInvoice['paymentStatus'],
							'paidAt' => $eInvoice['paidAt'],
							'amountIncludingVat' => match($eInvoice['paymentStatus']) {
								Invoice::PAID => $eInvoice['priceIncludingVat'],
								default => NULL
							},
						])
					]);

					self::replace($eSale, $cPayment);

				} else {
					self::delete($eSale);
				}

			}

		Payment::model()->commit();

	}

	public static function recalculate(Sale $eSale, ?\Collection $cPayment = NULL): void {

		$eSale->expects([
			'profile',
			'secured', 'closed'
		]);

		$cPayment ??= self::getAll($eSale);

		Sale::model()
			->select('priceIncludingVat', 'paymentStatus')
			->get($eSale);

		if($cPayment->empty()) {

			// Pas de moyen de paiement, on replace la vente à l'état NULL
			if($eSale['paymentStatus'] !== NULL) {

				self::reset($eSale, NULL);

			}

			return;

		}

		$paidAmount = NULL;
		$paidAt = NULL;

		$paid = 0;
		$notPaid = 0;
		$failed = 0;

		foreach($cPayment as $ePayment) {

			switch($ePayment['status']) {

				case Payment::PAID :

					$paidAmount ??= 0.0;
					$paidAmount += $ePayment['amountIncludingVat'];

					$paidAt ??= $ePayment['paidAt'];
					$paidAt = max($ePayment['paidAt'], $paidAt);

					$paid++;
					break;

				case Payment::NOT_PAID :
					$notPaid++;
					break;

				case Payment::FAILED :
					$failed++;
					break;

			}

		}

		// Cas impossible sauf en cas de bug technique
		if(
			$eSale['profile'] !== Sale::SALE_MARKET and
			$notPaid >= 2
		) {
			throw new \Exception('Too much not paid payment methods for sale '.$eSale['id']);
		}

		if($paidAmount !== NULL) {
			$paidAmount = round($paidAmount, 2);
		}

		if($paid === 0) {
			$eSale['paymentStatus'] = ($failed > 0) ? Sale::FAILED : Sale::NOT_PAID;
		} else {

			if($paidAmount < $eSale['priceIncludingVat']) {
				$eSale['paymentStatus'] = Sale::PARTIAL_PAID;
			} else {
				$eSale['paymentStatus'] = Sale::PAID;
			}

		}

		if(
			$failed > 0 and
			($paid > 0 or $notPaid > 0)
		) {

			Payment::model()
				->whereSale($eSale)
				->whereStatus(Payment::FAILED)
				->delete();

		}

		$eSale['paymentAmount'] = $paidAmount;
		$eSale['paidAt'] = $paidAt;

		SaleLib::update($eSale, ['paymentStatus', 'paymentAmount', 'paidAt']);

	}

	protected static function reset(Sale $eSale, ?string $paymentStatus): void {

		if($paymentStatus !== NULL and $paymentStatus !== Sale::NEVER_PAID) {
			throw new \UnsupportedException();
		}

		$eSale['paymentStatus'] = $paymentStatus;
		$eSale['paymentAmount'] = NULL;
		$eSale['paidAt'] = NULL;

		SaleLib::update($eSale, ['paymentStatus', 'paymentAmount', 'paidAt']);

	}

}
?>
