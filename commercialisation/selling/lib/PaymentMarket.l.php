<?php
namespace selling;

class PaymentMarketLib {

	public static function pay(Sale $eSale): void {

		if($eSale->isMarketSale() === FALSE) {
			throw new \UnsupportedException();
		}

		Sale::model()->beginTransaction();

			$paidAt = currentDate();

			$cPayment = PaymentTransactionLib::getAll($eSale);

			$ids = [];

			foreach($cPayment as $ePayment) {

				if($ePayment['status'] === Payment::NOT_PAID) {

					$ePayment['status'] = Payment::PAID;
					$ePayment['paidAt'] = $paidAt;

					$ids[] = $ePayment['id'];

				}

			}

			Payment::model()
				->whereId('IN', $ids)
				->update([
					'status' => Payment::PAID,
					'paidAt' => $paidAt
				]);

			PaymentTransactionLib::recalculate($eSale, $cPayment);

		Sale::model()->commit();

	}

	public static function clean(Sale $eSale): void {

		if($eSale->isMarketSale() === FALSE) {
			throw new \UnsupportedException();
		}

		Payment::model()
			->whereSale($eSale)
			->where('amountIncludingVat IS NULL OR amountIncludingVat = 0')
			->delete();

	}

	/**
	 * Change un moyen de paiement pour un autre s'il n'est pas présent par ailleurs
	 */
	public static function updateMethod(Sale $eSale, Payment $ePayment, \payment\Method $eMethod): void {

		if($eSale->isMarketSale() === FALSE) {
			throw new \UnsupportedException();
		}

		if($eMethod->isOnline()) {
			throw new \UnsupportedException();
		}

		$ePayment->expects(['id', 'sale', 'method']);
		$ePayment->validateProperty('sale', $eSale);

		Payment::model()->beginTransaction();

			if(Payment::model()
				->whereSale($eSale)
				->whereMethod($eMethod)
				->exists()) {
				return;
			}

			$ePayment['method'] = $eMethod;
			$ePayment['sale'] = $eSale;

			PaymentLib::update($ePayment, ['method']);

		Payment::model()->commit();

	}

	/**
	 * Supprime un moyen de paiement
	 * S'il n'en reste qu'un, modifie le montant pour correspondre à la vente
	 */
	public static function deleteMethod(Sale $eSale, \payment\Method $eMethod): void {

		if($eSale->isMarketSale() === FALSE) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			Payment::model()
				->whereSale($eSale)
				->whereMethod($eMethod)
				->delete();

			\selling\PaymentMarketLib::fillMethod($eSale);

		Payment::model()->commit();

	}

	/**
	 * Remplit la vente avec le moyen de paiement renseigné pour que le total des paiements corresponde au total de la vente
	 * Si aucun moyen de paiement n'est renseigné, on utilise le moyen de paiement actuel de la vente si il est renseigné et qu'il n'y en a qu'un
	 * Sinon, on utilise le moyen de paiement par défaut
	 */
	public static function fillMethod(Sale $eSale, \payment\Method $eMethod = new \payment\Method()): void {

		if($eSale->isMarketSale() === FALSE) {
			throw new \UnsupportedException();
		}

		if(
			$eMethod->notEmpty() and
			$eMethod->isOnline()
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$cPayment = PaymentTransactionLib::getAll($eSale);

			if($eMethod->empty()) {

				$eMethodDefault = $eSale['farm']->getConf('marketSalePaymentMethod');

				if($cPayment->count() === 1) {

					$eMethod = $cPayment->first()['method'];

				} else if($eMethodDefault->notEmpty()) {

					$eMethod = \payment\MethodLib::getById($eMethodDefault);

				} else {

					Payment::model()->commit();
					return;

				}

			}

			$currentAmount = 0;
			$ePaymentWithMethod = new \payment\Method();

			foreach($cPayment as $ePayment) {

				$currentAmount += $ePayment['amountIncludingVat'];

				if($ePayment['method']->is($eMethod)) {
					$ePaymentWithMethod = $ePayment;
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

				PaymentTransactionLib::recalculate($eSale, $cPayment);

			} else {

				$fillAmount = max(0.0, $eSale['priceIncludingVat'] - $currentAmount);

				$ePaymentCreate = new Payment([
					'method' => $eMethod,
					'amountIncludingVat' => $fillAmount,
					'status' => Sale::NOT_PAID
				]);

				PaymentTransactionLib::createForTransaction($eSale, $ePaymentCreate);

			}

		Payment::model()->commit();

	}

	/**
	 * Mets à jour les données agrégées des ventes dans la caisse
	 */
	public static function calculateAggregation(Sale $eSale): void {

		if($eSale->isMarket() === FALSE) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$cPayment = new \Collection();

			$aggregate = [
				'priceIncludingVat' => 0.0,
				'priceExcludingVat' => 0.0,
				'vatByRate' => []
			];

			$ccSale = \selling\SaleLib::getByParent($eSale);

			if($ccSale->notEmpty()) {

				$ccSale[Sale::DELIVERED]
					->map(function($eSaleMarket) use($eSale, $cPayment, &$aggregate) {

						$aggregate['priceIncludingVat'] += $eSaleMarket['priceIncludingVat'];
						$aggregate['priceExcludingVat'] += $eSaleMarket['priceExcludingVat'];

						foreach($eSaleMarket['cPayment'] as $ePayment) {

							$eMethod = $ePayment['method'];

							$cPayment[$eMethod['id']] ??= new Payment([
								'method' => $eMethod,
								'status' => Payment::PAID,
								'paidAt' => $eSale['deliveredAt'],
								'amountIncludingVat' => 0.0
							]);

							$cPayment[$eMethod['id']]['amountIncludingVat'] += $ePayment['amountIncludingVat'];
							$cPayment[$eMethod['id']]['amountIncludingVat'] = round($cPayment[$eMethod['id']]['amountIncludingVat'], 2);

						}

						foreach($eSaleMarket['vatByRate'] as $rate) {

							$key = (string)$rate['vatRate'];

							$aggregate['vatByRate'][$key] ??= [
								'vatRate' => $rate['vatRate'],
								'vat' => 0.0,
								'amount' => 0.0
							];

							$aggregate['vatByRate'][$key]['vat'] += $rate['vat'];
							$aggregate['vatByRate'][$key]['vat'] = round($aggregate['vatByRate'][$key]['vat'], 2);

							$aggregate['vatByRate'][$key]['amount'] += $rate['amount'];
							$aggregate['vatByRate'][$key]['amount'] = round($aggregate['vatByRate'][$key]['amount'], 2);


						}

					});

			}

			$cPayment->filter(fn($ePayment) => $ePayment['amountIncludingVat'] !== 0.0);

			$aggregate['vatByRate'] = array_values($aggregate['vatByRate']);

			if(
				$eSale['priceIncludingVat'] !== NULL and
				round($eSale['priceIncludingVat'], 2) !== round($aggregate['priceIncludingVat'], 2)
			) {
				trigger_error('Price inconsistency ('.$eSale['priceIncludingVat'].' expected, '.$aggregate['priceIncludingVat'].' calculated)');
			}

			PaymentTransactionLib::replace($eSale, $cPayment);

			$aggregate['vat'] = round($aggregate['priceIncludingVat'] - $aggregate['priceExcludingVat'], 2);

			Sale::model()->update($eSale, $aggregate);

		Payment::model()->commit();

	}

}
?>
