<?php
namespace selling;

class PaymentUi {

	public function __construct() {


	}

	public static function getListDisplay(Sale $eSale, \Collection $cPayment): string {

		$paymentList = [];
		foreach($cPayment as $ePayment) {

			$paymentMethodFqn = $ePayment['method']['fqn'] ?? NULL;

			if($ePayment->isPaymentOnline()) {

				$payment = '<div>';
				$payment .= \payment\MethodUi::getOnlineCardText();
				if($ePayment['amountIncludingVat']) {
					$payment .= ' ('.\util\TextUi::money($ePayment['amountIncludingVat']).')';
				}
				$payment .= '</div>';
				$payment .= '<div>'.SaleUi::getPaymentStatus($eSale, $ePayment).'</div>';

				$paymentList[] = $payment;

			} else if($eSale['invoice']->notEmpty()) {
				if($eSale['invoice']->isCreditNote()) {
					$paymentList[] = '<div>'.s("Avoir").'</div>';
				} else {
					$paymentList[] = '<div>'.s("Facture").'</div>'
						.'<div>'.InvoiceUi::getPaymentStatus($eSale['invoice']).'</div>';
				}

			} else if($paymentMethodFqn === \payment\MethodLib::TRANSFER) {
				$paymentList[] = '<div>'.s("Virement bancaire").'</div>'
					.'<div>'.SaleUi::getPaymentStatus($eSale, $ePayment).'</span></div>';
			} else if(in_array($paymentMethodFqn, [\payment\MethodLib::CASH, \payment\MethodLib::CHECK, \payment\MethodLib::CARD])) {
				$paymentList[] = encode($ePayment['method']['name']);
			}

		}

		if(empty($paymentList)) {
			return '/';
		}

		return implode('<br />', $paymentList);
	}

}
