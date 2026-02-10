<?php
namespace selling;

class PaymentLib extends PaymentCrud {

	public static function isOnline(\Collection $cPayment, ?string $status = NULL): bool {

		if($cPayment->empty()) {
			return FALSE;
		}

		foreach($cPayment as $ePayment) {
			if(
				$ePayment['method']->isOnline() and
				($status === NULL or $ePayment['status'] === $status)
			) {
				return TRUE;
			}
		}

		return FALSE;

	}

	/**
	 * Ajoute un moyen de paiement
	 * Si les moyens de paiement sont fournis, ils doivent être garantis par une transaction
	 */
	public static function create(Payment $e): void {

		$e->expects([
			'source',
			'customer', 'farm',
			'method' => ['fqn', 'name'],
			'status'
		]);

		switch($e['source']) {

			case Payment::SALE :

				$e->expects([
					'sale' => ['profile']
				]);

				if(
					$e['sale']->isMarketSale() === FALSE and
					$e['sale']->isSale() === FALSE
				) {
					throw new \UnsupportedException();
				}

				break;

			case Payment::INVOICE :
				$e->expects(['invoice']);
				break;

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

			PaymentTransactionLib::recalculate($e->getElement());

		Payment::model()->commit();

	}

	public static function cancelAccounting(string $hash): void {

		\selling\Payment::model()
      ->whereAccountingHash($hash)
      ->update(['accountingHash' => NULL, 'accountingDifference' => NULL]);
	}

	public static function cancelReconciliation(\bank\Cashflow $eCashflow): void {

		\selling\Payment::model()
			->whereCashflow($eCashflow)
			->update(['cashflow' => NULL, 'readyForAccounting' => FALSE]);

	}

	public static function updateForReconciliation(Payment $ePayment, array $properties): void {

		$propertiesForbidden = array_intersect($properties, ['method', 'amountIncludingVat', 'status', 'paidAt']);

		if(count($propertiesForbidden) > 0) {
			throw new \UnsupportedException();
		}

		parent::update($ePayment, $properties);

		self::close($ePayment);

	}

	public static function sumTotalPaid(Payment $ePayment): float {

		return Payment::model()
			->select('amountIncludingVat')
			->whereStatus(\selling\Payment::PAID)
			->whereInvoice($ePayment['invoice'], if: $ePayment['source'] === Payment::INVOICE)
			->whereSale($ePayment['sale'], if: $ePayment['source'] === Payment::SALE)
			->getCollection()
			->sum('amountIncludingVat');

	}

	public static function update(Payment $e, array $properties): void {

		$e->expects(['closed']);

		if($e['closed']) {
			return;
		}

		if(array_diff($properties, ['method', 'amountIncludingVat', 'status', 'paidAt']) !== []) {
			throw new \UnsupportedException();
		}

		if(in_array('method', $properties)) {

			$e['method']->expects(['name']);

			$e['methodName'] = $e['method']['name'];
			$properties[] = 'methodName';

		}

		if(
			array_intersect($properties, ['status', 'paidAt']) !== [] and (
				($e['status'] === Payment::PAID and $e['paidAt'] === NULL) or
				($e['status'] !== Payment::PAID and $e['paidAt'] !== NULL)
			)
		) {
			throw new \UnsupportedException();
		}

		Payment::model()->beginTransaction();

			parent::update($e, $properties);

			if(array_intersect($properties, ['amountIncludingVat', 'status', 'paidAt']) !== []) {
				PaymentTransactionLib::recalculate($e->getElement());
			}

		Payment::model()->commit();

	}

	public static function close(Payment $ePayment): void {

		$ePayment['closed'] = TRUE;
		$ePayment['closedAt'] = Payment::model()->now();

		Payment::model()
			->select(['closed', 'closedAt'])
			->whereClosed(FALSE)
			->update($ePayment);

	}

	public static function delete(Payment $e): void {

		$e->expects(['id', 'closed']);

		if($e['closed']) {
			return;
		}

		Payment::model()->beginTransaction();

			parent::delete($e);

			PaymentTransactionLib::recalculate($e->getElement());

		Payment::model()->commit();

	}

	public static function deleteCollection(\Collection $cPayment): void {

		Payment::model()->beginTransaction();

			foreach($cPayment as $ePayment) {
				self::delete($ePayment);
			}

		Payment::model()->commit();


	}

	public static function deleteFailed(Sale|Invoice $e): void {

		Payment::model()->beginTransaction();

			Payment::model()
				->whereSale($e, if: $e instanceof Sale)
				->whereInvoice($e, if: $e instanceof Invoice)
				->whereStatus(Payment::FAILED)
				->delete();

		Payment::model()->commit();


	}

}
?>
