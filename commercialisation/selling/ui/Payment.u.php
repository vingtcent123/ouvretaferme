<?php
namespace selling;

class PaymentUi {

	public static function getList(\Collection $cPayment): array {

		$payments = [];

		foreach($cPayment as $ePayment) {

			if($ePayment['method']->empty()) {
				continue;
			}

			$payments[] = encode($ePayment['method']['name']);

		}

		return $payments;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Customer::model()->describer(
			$property, [
				'amountIncludingVat' => s("Montant (TTC)"),
			]
		);

		switch($property) {

			case 'amountIncludingVat' :
				$d->type = 'float';
				break;

		}

		return $d;
	}
}
