<?php
namespace selling;

class PaymentTransactionLib {

	private static bool $recalculate = TRUE;

	/**
	 * Vérification intégrale des données car le build() ne fait pas les vérifications métier
	 * Trop de comportements différents avec le paiement en ligne et le logiciel de caisse
	 */
	public static function prepare(Sale|Invoice $e, array $input): \Collection {

		$keys = ['id', 'method', 'status', 'amountIncludingVat', 'paidAt'];

		if(array_intersect($keys, array_keys($input)) !== $keys) {
			Payment::fail('unexpected');
			return new \Collection();
		}

		$ids = array_slice($input['payment'], 0, -1, preserve_keys: TRUE);
		$payments = count($ids);

		Payment::model()->beginTransaction();

			$cMethod = \payment\MethodLib::getByFarm($e['farm'], NULL);

			$cPaymentCurrent = PaymentTransactionLib::getAll($e, index: 'id');
			$cPaymentNew = new \Collection();

			foreach($ids as $position => $id) {

				$ePaymentNew = $cPaymentCurrent->offsetExists($id) ? $cPaymentCurrent[$id] : new Payment();

				$method = var_filter($input['method'][$position] ?? NULL, '?int');
				$amount = var_filter($input['amountIncludingVat'][$position] ?? NULL, '?float', default: 0.0);
				$paidAt = var_filter($input['paidAt'][$position] ?? NULL, '?string');

				if($cMethod->offsetExists($method)) {
					$ePaymentNew['method'] = $cMethod[$method];
				} else {

					if($payments === 1) {
						Payment::model()->commit();
						return new \Collection();
					}

					Payment::fail('method.empty', wrapper: 'method['.$position.']');
					$ePaymentNew['method'] = new \payment\Method();
				}

				if($payments > 1) {
					$ePaymentNew['status'] = Payment::PAID;
				} else {
					$ePaymentNew['status'] = var_filter($input['status'][$position] ?? NULL, [Payment::PAID, Payment::NOT_PAID], Payment::NOT_PAID);
				}


				// Il doit y avoir un montant pour un règlement payé
				if(
					$amount === 0.0 and
					$ePaymentNew['status'] === Payment::PAID
				) {
					continue;
				}

				switch($ePaymentNew['status']) {

					case Payment::NOT_PAID :
						$ePaymentNew['amountIncludingVat'] = NULL;
						$ePaymentNew['paidAt'] = NULL;
						break;

					case Payment::PAID :
						$ePaymentNew->build(['amountIncludingVat', 'paidAt'], [
							'amountIncludingVat' => $amount,
							'paidAt'=> $paidAt
						], new \Properties()->setWrapper(fn(string $property) => $property.'['.$position.']'));
						break;

				}

				$cPaymentNew[] = $ePaymentNew;

			}

		Payment::model()->commit();

		return $cPaymentNew;

	}

	public static function getAll(Sale|Invoice $e, ?array $selection = NULL, mixed $index = NULL): \Collection {

		$selection ??= Payment::getSelection();

		return Payment::model()
			->select($selection)
			->whereSale($e, if: $e instanceof Sale)
			->whereInvoice($e, if: $e instanceof Invoice)
			->sort(['id' => SORT_ASC])
			->getCollection(index: $index);

	}

	public static function getByMethod(Sale|Invoice $e, \payment\Method $eMethod): Payment {

		return Payment::model()
			->select(Payment::getSelection())
			->whereSale($e, if: $e instanceof Sale)
			->whereInvoice($e, if: $e instanceof Invoice)
			->whereMethod($eMethod)
			->sort(['createdAt' => SORT_DESC])
			->get();
	}

	public static function delegateBySale(): PaymentModel {

		return Payment::model()
      	->select(Payment::getSelection())
			->sort(['id' => SORT_ASC])
			->delegateCollection('sale', 'id');

	}

	public static function delegateByInvoice(): PaymentModel {

		return Payment::model()
      	->select(Payment::getSelection())
			->sort(['id' => SORT_ASC])
			->delegateCollection('invoice', 'id');

	}

	/**
	 * Ajoute un moyen de paiement
	 * Si les moyens de paiement sont fournis, ils doivent être garantis par une transaction
	 */
	public static function createForTransaction(Sale|Invoice $e, Payment $ePayment): void {

		$e->expects(['farm', 'customer']);

		$ePayment->merge([
			'customer' => $e['customer'],
			'farm' => $e['farm'],
		]);

		if($e instanceof Sale) {
			$ePayment['sale'] = $e;
			$ePayment['source'] = Payment::SALE;
		} else if($e instanceof Invoice) {
			$ePayment['invoice'] = $e;
			$ePayment['source'] = Payment::INVOICE;
		}

		PaymentLib::create($ePayment);

	}

	public static function createCollectionForTransaction(Sale|Invoice $e, \Collection $cPayment): void {

		foreach($cPayment as $ePayment) {
			self::createForTransaction($e, $ePayment);
		}

	}

	public static function replace(Sale|Invoice $e, \Collection $cPayment): void {

		$cPayment->expects([
			'method', 'amountIncludingVat', 'status'
		]);

		Payment::model()->beginTransaction();

			if($cPayment->notEmpty()) {

				PaymentTransactionLib::withRecalculate(FALSE);

					$cPaymentDelete = PaymentTransactionLib::getAll($e, index: 'id');

					foreach($cPayment as $ePayment) {

						if($ePayment->exists()) {

							// Propriétés nécessaires pour la mise à jour
							if($e instanceof Sale) {
								$ePayment['sale'] = $e;
							} else if($e instanceof Invoice) {
								$ePayment['invoice'] = $e;
							}

							if($cPaymentDelete->offsetExists($ePayment['id'])) {
								$cPaymentDelete->offsetUnset($ePayment['id']);
							}

							PaymentLib::update($ePayment, ['method', 'amountIncludingVat', 'status', 'paidAt']);

						} else {
							self::createForTransaction($e, $ePayment);
						}

					}

					if($cPaymentDelete->notEmpty()) {
						PaymentLib::deleteCollection($cPaymentDelete);
					}

				PaymentTransactionLib::withRecalculate(TRUE);

				PaymentTransactionLib::recalculate($e, $cPayment);

			} else {
				self::delete($e);
			}

		Payment::model()->commit();

	}

	public static function updateNotPaidMethod(Sale|Invoice $e, \payment\Method $eMethod): void {

		if($eMethod->acceptManualUpdate() === FALSE) {
			throw new \UnsupportedException();
		}

		$cPayment = new \Collection([
			new Payment([
				'method' => $eMethod,
				'status' => Payment::NOT_PAID,
				'paidAt' => NULL,
				'amountIncludingVat' => NULL,
			])
		]);

		self::replace($e, $cPayment);

	}

	public static function updatePaid(Sale|Invoice $e): void {

		Payment::model()->beginTransaction();

			$ePayment = Payment::model()
				->select(Payment::getSelection())
				->whereSale($e, if: $e instanceof Sale)
				->whereInvoice($e, if: $e instanceof Invoice)
				->whereStatus(Payment::NOT_PAID)
				->get();

			if($ePayment->notEmpty()) {

				$e->model()
					->select('paymentAmount', 'priceIncludingVat')
					->get($e);

				$ePayment['amountIncludingVat'] = ($e['priceIncludingVat'] - $e['paymentAmount']);
				$ePayment['status'] = Payment::PAID;
				$ePayment['paidAt'] = currentDate();

				if($e instanceof Sale) {
					$ePayment['sale'] = $e;
				} else if($e instanceof Invoice) {
					$ePayment['invoice'] = $e;
				}

				PaymentLib::update($ePayment, ['amountIncludingVat', 'status', 'paidAt']);

			}

		Payment::model()->commit();

	}

	public static function updateNeverPaid(Sale|Invoice $e): void {

		Payment::model()->beginTransaction();

			self::delete($e, recalculate: FALSE);
			self::reset($e, Sale::NEVER_PAID);

		Payment::model()->commit();

	}

	public static function updateCustomer(Sale|Invoice $e, Customer $eCustomer): void {

		Payment::model()
			->whereSale($e, if: $e instanceof Sale)
			->whereInvoice($e, if: $e instanceof Invoice)
			->update([
				'customer' => $eCustomer,
			]);

	}

	public static function delete(Sale|Invoice $e, bool $recalculate = TRUE): void {

		$e->expects(['id']);

		Payment::model()->beginTransaction();

			// On expire toutes les sessions paiement en ligne
			$cPayment = self::getAll($e);

			if($cPayment->empty()) {
				Payment::model()->commit();
				return;
			}

			foreach($cPayment as $ePayment) {

				// Besoin de reporter les propriétés pour la suppression
				if($e instanceof Sale) {
					$ePayment['sale'] = $e;
				} else if($e instanceof Invoice) {
					$ePayment['invoice'] = $e;
				}


				if(
					$ePayment['status'] === Payment::NOT_PAID and
					$ePayment['onlineCheckoutId'] !== NULL
				) {

					static $eStripeFarm = \payment\StripeLib::getByFarm($e['farm']);

					if($eStripeFarm->notEmpty()) {

						try {
							\payment\StripeLib::expiresCheckoutSession($eStripeFarm, $ePayment['onlineCheckoutId']);
						} catch(\payment\StripeException) {
						}

					}

				}

			}

			PaymentLib::deleteCollection($cPayment);


			if($recalculate) {
				PaymentTransactionLib::recalculate($e, new \Collection());
			}

		Payment::model()->commit();

	}

	public static function broadcastInvoice(Invoice $eInvoice): void {

		$eInvoice->expects(['sales']);

		if(count($eInvoice['sales']) !== 1) {
			return;
		}

		Payment::model()->beginTransaction();

			$cSale = SaleLib::getByIds($eInvoice['sales']);

			if($cSale->count() === 1) { // En théorie inutile

				$cPayment = PaymentTransactionLib::getAll($eInvoice, selection: [
					'method' => \payment\Method::getSelection(),
					'status', 'amountIncludingVat', 'paidAt'
				]);

				$eSale = $cSale->first();

				if($cPayment->notEmpty()) {
					self::replace($eSale, $cPayment);
				} else {
					self::delete($eSale);
				}

			}

		Payment::model()->commit();

	}

	public static function importInvoice(Invoice $eInvoice, \Collection $cPayment): void {

		$eInvoice->expects(['sales']);

		Payment::model()->beginTransaction();

			$cPaymentCopy = new \Collection();

			foreach($cPayment as $ePayment) {
				$cPaymentCopy[] = $ePayment->getCopy(['method', 'status', 'amountIncludingVat', 'paidAt']);
			}

			self::createCollectionForTransaction($eInvoice, $cPaymentCopy);

		Payment::model()->commit();

	}

	public static function withRecalculate(bool $recalculate): void {
		self::$recalculate = $recalculate;
	}

	public static function recalculate(Sale|Invoice $e, ?\Collection $cPayment = NULL): void {

		if(self::$recalculate === FALSE) {
			return;
		}

		if($e instanceof Sale) {

			$e->expects([
				'profile',
				'secured', 'closed'
			]);

		}

		$cPayment ??= self::getAll($e);

		$e->model()
			->select('priceIncludingVat', 'paymentStatus')
			->get($e);

		if($cPayment->empty()) {

			// Pas de moyen de paiement, on replace la vente à l'état NULL
			if($e['paymentStatus'] !== NULL) {

				self::reset($e, NULL);

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
			(
				$e instanceof Invoice or
				($e instanceof Sale and $e['profile'] !== Sale::SALE_MARKET)
			) and
			$notPaid >= 2
		) {
			throw new \Exception('Too much not paid payment methods for '.$e->getModule().' '.$e['id']);
		}

		if($paidAmount !== NULL) {
			$paidAmount = round($paidAmount, 2);
		}

		if($paid === 0) {
			$e['paymentStatus'] = ($failed > 0) ? Sale::FAILED : Sale::NOT_PAID;
		} else {

			if($paidAmount < $e['priceIncludingVat']) {
				$e['paymentStatus'] = Sale::PARTIAL_PAID;
			} else {
				$e['paymentStatus'] = Sale::PAID;
			}

		}

		if(
			$failed > 0 and
			($paid > 0 or $notPaid > 0)
		) {

			PaymentLib::deleteFailed($e);

		}

		$e['paymentAmount'] = $paidAmount;
		$e['paidAt'] = $paidAt;

		$properties = ['paymentStatus', 'paymentAmount', 'paidAt'];

		if($e instanceof Sale) {
			SaleLib::update($e, $properties);
		} else if($e instanceof Invoice) {
			InvoiceLib::update($e, $properties);
		}

	}

	protected static function reset(Sale|Invoice $e, ?string $paymentStatus): void {

		if($paymentStatus !== NULL and $paymentStatus !== Sale::NEVER_PAID) {
			throw new \UnsupportedException();
		}

		$e['paymentStatus'] = $paymentStatus;
		$e['paymentAmount'] = NULL;
		$e['paidAt'] = NULL;

		$properties = ['paymentStatus', 'paymentAmount', 'paidAt'];

		if($e instanceof Sale) {
			SaleLib::update($e, $properties);
		} else if($e instanceof Invoice) {
			InvoiceLib::update($e, $properties);
		}

	}

}
?>
