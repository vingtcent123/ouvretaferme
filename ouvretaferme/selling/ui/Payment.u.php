<?php
namespace selling;

class PaymentUi {

	public function __construct() {


	}

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

	public static function statusIcon(Sale $eSale, Payment $ePayment): string {

		return match($ePayment['status']) {
			Payment::SUCCESS => SaleUi::getPaymentStatus(new Sale(['paymentStatus' => Sale::PAID, 'invoice' => $eSale['invoice']])),
			Payment::FAILURE => SaleUi::getPaymentStatus(new Sale(['paymentStatus' => Sale::NOT_PAID, 'invoice' => $eSale['invoice']])),
			Payment::INITIALIZED => SaleUi::getPaymentStatus(new Sale(['paymentStatus' => Sale::NOT_PAID, 'invoice' => $eSale['invoice']])),
		};

	}

	public static function getPaymentMethodName($ePayment): string {

		$ePayment->expects(['method']);

		if($ePayment->isPaymentOnline()) {
			return \payment\MethodUi::getOnlineCardText();
		}

		return encode($ePayment['method']['name'] ?? '?');

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
