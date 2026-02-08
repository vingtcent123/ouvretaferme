<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	/**
	 * Ajoute un moyen de paiement
	 * Si les moyens de paiement sont fournis, ils doivent être garantis par une transaction
	 */
	public static function create(Payment $e): void {

		$e->expects([
			'source',
			'customer', 'farm',
			'method' => ['fqn', 'name'],
			'sale' => ['profile'],
			'status'
		]);

		if(
			$e['sale']->isMarketSale() === FALSE and
			$e['sale']->isSale() === FALSE
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			$e->merge([
				'methodName' => $e['method']['name'],
			]);

			if($e['status'] === Payment::NOT_PAID) {

				$e['paidAt'] = NULL;
				$e['amountIncludingVat'] ??= NULL;

			} else {
				$e->expects(['amountIncludingVat', 'paidAt']);
			}

			parent::create($e);

			PaymentTransactionLib::recalculate($e['sale']);

		Payment::model()->commit();

	}

	public static function update(Payment $ePayment, array $properties): void {

		if(array_diff($properties, ['method', 'amountIncludingVat', 'status', 'paidAt']) !== []) {
			throw new \UnsupportedException();
		}

		if(in_array('method', $properties)) {

			$ePayment['method']->expects(['name']);

			$ePayment['methodName'] = $ePayment['method']['name'];
			$properties[] = 'methodName';

		}

		if(
			array_intersect($properties, ['status', 'paidAt']) !== [] and (
				($ePayment['status'] === Payment::PAID and $ePayment['paidAt'] === NULL) or
				($ePayment['status'] !== Payment::PAID and $ePayment['paidAt'] !== NULL)
			)
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			parent::update($ePayment, $properties);

			if(array_intersect($properties, ['amountIncludingVat', 'status', 'paidAt']) !== []) {
				PaymentTransactionLib::recalculate($ePayment['sale']);
			}

		Payment::model()->commit();

	}

	public static function delete(Payment $ePayment): void {

		$ePayment->expects(['sale']);

		Payment::model()->beginTransaction();

			parent::delete($ePayment);

			PaymentTransactionLib::recalculate($ePayment['sale']);

		Payment::model()->commit();

	}

}
?>
