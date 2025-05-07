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
			Payment::FAILURE => \Asset::icon('x-lg', ['title' => s("Échec"), 'class' => 'color-info']),
			Payment::PENDING => \Asset::icon('pause', ['title' => s("En attente"), 'class' => 'color-danger']),
		};

	}

	public static function getListDisplay(Sale $eSale, \Collection $cPayment): string {

		$paymentList = [];
		foreach($cPayment as $ePayment) {

			$paymentMethodFqn = $ePayment['method']['fqn'] ?? NULL;

			if($ePayment->isPaymentOnline()) {

				$payment = '<div>';
				$payment .= self::statusIcon($ePayment).' '.\payment\MethodUi::getOnlineCardText();
				if($ePayment['amountIncludingVat'] and $ePayment['status'] === Payment::SUCCESS) {
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

					if($ePayment['amountIncludingVat'] and $ePayment['status'] === Payment::SUCCESS) {
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
