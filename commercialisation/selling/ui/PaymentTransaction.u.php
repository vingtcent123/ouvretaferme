<?php
namespace selling;

class PaymentTransactionUi {

	public function __construct() {

		\Asset::css('selling', 'payment.css');
		\Asset::js('selling', 'payment.js');

	}

	public function update(Sale $eSale, \Collection $cPayment, \Collection $cMethod): string {

		$eFarm = $eSale['farm'];

		$form = new \util\FormUi();
		$position = 0;

		$saleAmount = $eSale['priceIncludingVat'];

		if(
			$cPayment->empty() or
			$cPayment->first()['status'] === Payment::NOT_PAID
		) {
			$paymentAmount = $saleAmount;
		} else {
			$paymentAmount = $eSale['paymentAmount'];
		}

		$isBalanced = ($saleAmount === $paymentAmount);

		$h = '<div class="payment-update-wrapper">';

			$h .= $form->group(
				content: '<div class="util-info">'.s("Lorsque vous saisissez plusieurs paiements, vous devez fournir obligatoirement une date et un montant pour chaque paiement.").'</div>',
				attributes: ['class' => 'payment-update-info']
			);

			$h .= '<div id="payment-update-list" data-balanced="'.($isBalanced ? '1' : '0').'">';

				foreach($cPayment as $ePayment) {

					if(
						$ePayment['status'] === Payment::NOT_PAID and
						$ePayment['amountIncludingVat'] === NULL
					) {
						$ePayment['amountIncludingVat'] = $saleAmount;
						$ePayment['paidAt'] = currentDate();
					}

					$h .= $this->getUpdatePayment($form, $position++, $ePayment, $cMethod);

				}

				if($cPayment->empty()) {

					$h .= $this->getUpdatePayment($form, $position++, new Payment([
						'status' => Payment::NOT_PAID,
						'amountIncludingVat' => $saleAmount,
						'farm' => $eFarm,
						'paidAt' => currentDate()
					]), $cMethod);

				}

			$h .= '</div>';

			$h .= '<div id="payment-update-total" class="util-block-gradient '.($isBalanced ? '' : 'payment-update-total-error').'" data-position="'.$position.'" data-value="'.$paymentAmount.'" data-target="'.$saleAmount.'">';
				$h .= $form->group(
					s("Montant total renseigné"),
					'<span class="font-xl" style="font-weight: bold">'.\Asset::icon('exclamation-circle').'<span id="payment-update-amount">'.\util\TextUi::number($paymentAmount).'</span><span id="payment-update-target"> / '.\util\TextUi::number($saleAmount).'</span> €</span>'
				);
			$h .= '</div>';

			$h .= '<div id="payment-update-new" data-position="'.$position.'">';
				$h .= $this->getUpdatePayment($form, '', new Payment([
					'farm' => $eFarm,
					'status' => Payment::PAID,
					'paidAt' => currentDate()
				]), $cMethod);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getUpdatePayment(\util\FormUi $form, int|string $position, Payment $ePayment, \Collection $cMethod): string {

		$ePayment['cMethod'] = $cMethod;

		$h = '<div class="payment-update util-block bg-background-light">';
			$h .= $form->group(
				content: '<div class="util-action">'.
					'<h4>'.s("Paiement n°{value}", '<span class="payment-update-counter"></span>').'</h4>'.
					'<a onclick="Payment.delete(this)" class="btn btn-outline-primary">'.s("Supprimer").'</a>'.
				'</div>',
				attributes: ['class' => 'payment-update-title']
			);
			$h .= $form->hidden('payment['.$position.']', $ePayment->exists() ? $ePayment['id'] : '');
			$h .= $form->dynamicGroup($ePayment, 'method['.$position.']');
			$h .= '<div class="payment-update-status">';
				$h .= $form->dynamicGroup($ePayment, 'status['.$position.']');
			$h .= '</div>';
			$h .= $form->group(
				PaymentUi::p('amountIncludingVat')->label,
				'<div class="payment-update-amount">'.
					$form->dynamicField($ePayment, 'amountIncludingVat['.$position.']').
					'<a onclick="Payment.magic(this)" class="btn btn-outline-primary payment-update-magic">'.\Asset::icon('magic').'</a>'.
				'</div>'
			);
			$h .= $form->dynamicGroup($ePayment, 'paidAt['.$position.']');
		$h .= '</div>';

		return $h;

	}

}
