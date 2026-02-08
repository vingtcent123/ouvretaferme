<?php
namespace selling;

class PaymentMarketLib {

	/**
	 * Change un moyen de paiement pour un autre s'il n'est pas présent par ailleurs
	 */
	public static function updateMethod(Payment $ePayment, \payment\Method $eMethod): void {

		if($eMethod->isOnline()) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$ePayment->expects(['id', 'sale', 'method']);

			$eSale = $ePayment['sale'];

			if(Payment::model()
				->whereSale($eSale)
				->whereMethod($eMethod)
				->exists()) {
				return;
			}

			$ePayment['method'] = $eMethod;

			PaymentLib::update($ePayment, ['method']);

		Payment::model()->commit();

	}

	/**
	 * Supprime un moyen de paiement
	 * S'il n'en reste qu'un, modifie le montant pour correspondre à la vente
	 */
	public static function deleteMethod(Sale $eSale, \payment\Method $eMethod): void {

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

		if(
			$eMethod->notEmpty() and
			$eMethod->isOnline()
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$cPayment = PaymentLib::getBySale($eSale);

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

			} else {

				$fillAmount = max(0.0, $eSale['priceIncludingVat'] - $currentAmount);

				PaymentLib::createByMethod($eSale, $eMethod, $fillAmount);

			}

		Payment::model()->commit();

	}

}
?>
