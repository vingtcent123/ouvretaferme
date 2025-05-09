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

	public static function getPaymentMethodName($ePayment): string {

		$ePayment->expects(['method']);

		if($ePayment->isPaymentOnline()) {
			return \payment\MethodUi::getOnlineCardText();
		}

		return encode($ePayment['method']['name'] ?? '?');

	}

	public static function getPaymentForm(Sale $eSale, \Collection $cPaymentMethod): string {

		$payment = '<div class="sale-payment-labels">';
		foreach($cPaymentMethod as $ePaymentMethod) {
			$payment .= '<a data-ajax="/selling/sale:doUpdatePaymentMethod" post-id="'.$eSale['id'].'" post-status="'.Payment::SUCCESS.'" post-payment-method="'.$ePaymentMethod['id'].'" class="sale-payment-action sale-payment-label sale-payment-'.($ePaymentMethod['online'] ? '' : 'not-').'online" title="'.encode($ePaymentMethod['name']).'">'.\payment\MethodUi::getShortValues($ePaymentMethod).'</a>';
		}
		$payment .= '</div>';

		return $payment;

	}

	public static function getListDisplay(Sale $eSale, \Collection $cPayment, \Collection $cPaymentMethod): string {

		if($eSale['market']) {
			return '';
		}

		$totalPayments = $cPayment->reduce(fn($ePayment, $value) => $value + ($ePayment['amountIncludingVat'] ?? 0), 0);

		$paymentList = [];

		if($eSale['invoice']->notEmpty()) {

			if($eSale['invoice']->isCreditNote()) {
				$paymentList[] = '<div>'.s("Avoir").'</div>';
			} else {
				$paymentList[] = '<div>'.s("Facture").'</div>'
					.'<div>'.InvoiceUi::getPaymentStatus($eSale['invoice']).'</div>';
			}
		}

		foreach($cPayment as $ePayment) {

			$payment = '<div>';

				if($ePayment['method']->exists() === FALSE and $eSale->acceptWritePaymentMethod()) {

					$payment .= self::getPaymentForm($eSale, $cPaymentMethod);

				} else {

					$payment .= self::statusIcon($ePayment).' ';
					$payment .= self::getPaymentMethodName($ePayment);

				}

				if($totalPayments !== $eSale['priceIncludingVat'] and $ePayment['amountIncludingVat'] !== NULL) {
					$payment .= ' ('.\util\TextUi::money($ePayment['amountIncludingVat']).')';
				}
			$payment .= '</div>';

			$paymentList[] = $payment;

		}

		if(empty($paymentList)) {
			if($eSale->acceptWritePaymentMethod()) {
				return self::getPaymentForm($eSale, $cPaymentMethod);
			}
			return '/';
		}

		return implode('<br />', $paymentList);
	}

}
