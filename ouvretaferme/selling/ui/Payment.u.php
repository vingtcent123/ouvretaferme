<?php
namespace selling;

class PaymentUi {

	public function __construct() {


	}

	public static function getList(\Collection $cPayment): array {

		$payments = [];

		foreach($cPayment as $ePayment) {

			if($ePayment['method']->exists() === FALSE) {
				continue;
			}

			$payments[] = encode($ePayment['method']['name']);

		}

		return $payments;

	}

	public static function statusIcon(Payment $ePayment): string {

		return match($ePayment['status']) {
			Payment::SUCCESS => \Asset::icon('check-lg', ['title' => s("Succès"), 'class' => 'color-success']),
			Payment::FAILURE => \Asset::icon('x-lg', ['title' => s("Échec"), 'class' => 'color-danger']),
			Payment::PENDING => \Asset::icon('pause', ['title' => s("En attente"), 'class' => 'color-info']),
		};

	}

	public static function getListDisplay(Sale $eSale, \Collection $cPayment): string {

		$totalPayments = $cPayment->reduce(fn($ePayment, $value) => $value + ($ePayment['amountIncludingVat'] ?? 0), 0);

		$paymentList = [];
		foreach($cPayment as $ePayment) {

			$displayAmount = ($totalPayments !== $eSale['priceIncludingVat'] and $ePayment['amountIncludingVat'] !== NULL);

			if($ePayment->isPaymentOnline()) {

				$payment = '<div>';
				$payment .= self::statusIcon($ePayment).' '.\payment\MethodUi::getOnlineCardText();
				if($displayAmount) {
					$payment .= ' ('.\util\TextUi::money($ePayment['amountIncludingVat']).')';
				}
				$payment .= '</div>';

				$paymentList[] = $payment;

			} else if($eSale['invoice']->notEmpty()) {

				if($eSale['invoice']->isCreditNote()) {
					$paymentList[] = '<div>'.s("Avoir").'</div>';
				} else {
					$paymentList[] = '<div>'.s("Facture").'</div>'
						.'<div>'.InvoiceUi::getPaymentStatus($eSale['invoice']).'</div>';
				}

			} else {

				$payment = '<div>';
					$payment .= self::statusIcon($ePayment).' '.encode($ePayment['method']['name'] ?? '?');

					if($displayAmount) {
						$payment .= ' ('.\util\TextUi::money($ePayment['amountIncludingVat']).')';
					}
				$payment .= '</div>';

				$paymentList[] = $payment;

			}

		}

		if(empty($paymentList)) {
			return '/';
		}

		return implode('<br />', $paymentList);
	}

}
