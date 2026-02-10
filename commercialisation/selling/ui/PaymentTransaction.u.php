<?php
namespace selling;

class PaymentTransactionUi {

	public function __construct() {

		\Asset::css('selling', 'payment.css');
		\Asset::js('selling', 'payment.js');

	}

	public static function getPaymentMethodName(Sale|Invoice $e): ?string {

		$e->expects(['cPayment']);

		$cPayment = $e['cPayment'];

		$paymentList = [];

		foreach($cPayment as $ePayment) {

			$payment = '';
			if($ePayment['accountingHash'] !== NULL) {
				if($e['paymentStatus'] === Invoice::PAID) {
					$payment .= '<a class="util-badge bg-accounting" title="'.s("Paiement intégré en comptabilité").'" href="'.\farm\FarmUi::urlConnected($e['farm']).'/journal/livre-journal?hash='.$ePayment['accountingHash'].'&financialYearReset">'.\Asset::icon('journal-text').'</a> ';
				} else {
					$payment .= '<span class="util-badge bg-accounting" title="'.s("Paiement intégré en comptabilité").'">'.\Asset::icon('journal-text').'</span> ';
				}
			} else if($ePayment['cashflow']->notEmpty()) {
				if($e['paymentStatus'] === Invoice::PAID) {
					$payment .= '<a class="util-badge bg-accounting" title="'.s("Paiement rapproché").'" href="'.\farm\FarmUi::urlConnected($e['farm']).'/banque/operations?id='.$ePayment['cashflow']['id'].'">'.\Asset::icon('bank').'</a> ';
				} else {
					$payment .= '<span class="util-badge bg-accounting" title="'.s("Paiement rapproché").'">'.\Asset::icon('bank').'</span> ';
				}
			}

			$payment .= \payment\MethodUi::getName($ePayment['method']);

			if(
				$ePayment['amountIncludingVat'] !== NULL and
				($cPayment->count() > 1 or
					($ePayment['status'] === Payment::PAID and $e['paymentStatus'] === Sale::PARTIAL_PAID) or
					$ePayment['amountIncludingVat'] !== $e['priceIncludingVat']
				)
			) {
				$payment .= ' <span class="color-muted font-sm">'.\util\TextUi::money($ePayment['amountIncludingVat']).'</span>';
			}

			$paymentList[] = $payment;
		}

		return implode('<br />', $paymentList);

	}

	public static function getPaymentStatusBadge(Sale|Invoice $e): string {

		\Asset::css('selling', 'payment.css');

		if($e['paymentStatus'] === NULL) {
			return '';
		}

		$status = $e['paymentStatus'];
		$label = SaleUi::p('paymentStatus')->values[$status];

		$h = '<span class="util-badge payment-status payment-status-'.$status.'">';

			if($e['paidAt'] !== NULL) {
				$h .= s("{status} le {date}", ['status' => $label, 'date' => \util\DateUi::numeric($e['paidAt'])]);
			} else {
				$h .= $label;
			}

		$h .= '</span>';

		return $h;

	}

	public static function getPaymentBox(Sale|Invoice $e, bool $optimize = FALSE, string $late = ''): string {

		$e->expects([
			'paymentStatus',
			'cPayment'
		]);

		\Asset::css('selling', 'payment.css');

		$h = '';

		if($e['paymentStatus'] === Sale::NEVER_PAID) {

			$h .= PaymentTransactionUi::getPaymentStatusBadge($e);

		} else if($e['cPayment']->empty()) {

			if($e->acceptUpdatePayment()) {
				$h .= '<a href="'.self::getPrefix($e).':updatePayment?id='.$e['id'].'" class="btn btn-sm btn-outline-primary">'.s("Choisir").'</a>';
				$h .= $late;
			}

		} else {

			if($e->acceptUpdatePayment() and $e['paymentStatus'] !== Sale::PAID) {
				$h .= '<a href="'.self::getPrefix($e).':updatePayment?id='.$e['id'].'" class="btn btn-sm btn-outline-primary sale-button">';
			}

				$h .= PaymentTransactionUi::getPaymentMethodName($e);

				$paymentStatus = PaymentTransactionUi::getPaymentStatusBadge($e);
				$paymentStatus .= $late;

				if($paymentStatus) {

					if(
						$optimize and
						$e['cPayment']->count() === 1 and
						$e['paymentStatus'] !== Sale::PARTIAL_PAID
					) {
						$h .= '  '.$paymentStatus;
					} else {
						$h .= '<div>'.$paymentStatus.'</div>';
					}

				}

			if($e->acceptUpdatePayment() and $e['paymentStatus'] !== Sale::PAID) {
				$h .= '</a>';
			}

		}

		return $h;

	}

	public function getOnlinePayment(Sale|Invoice $e): string {

		if($e instanceof Sale) {
			$confirm = s("Voulez-vous vraiment supprimer ce mode de règlement pour la vente ?");
		} else if($e instanceof Invoice) {
			$confirm = s("Voulez-vous vraiment supprimer ce mode de règlement pour la facture ?");
		}

		$content = '<div class="flex-justify-space-between flex-align-center">';
			$content .= '<div>'.self::getPaymentMethodName($e).' '.self::getPaymentStatusBadge($e).'</div>';
			$content .= '<a data-ajax="'.self::getPrefix($e).':doDeletePayment" post-id="'.$e['id'].'" class="btn btn-xs btn-danger" data-confirm="'.$confirm.'">'.s("Supprimer").'</a>';
		$content .= '</div>';

		$h = '<div class="util-block bg-background-light">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

	public function getPaymentForm(Sale|Invoice $e): string {

		$form = new \util\FormUi();

		if($e instanceof Sale) {
			$neverPaid = s("Cette vente est actuellement enregistrée comme une vente qui ne sera pas payée, mais vous pouvez revenir sur votre choix.");
			$neverPaidConfirm = s("Vous allez indiquer que cette vente ne sera jamais payée. Voulez-vous continuer ?");
		} else if($e instanceof Invoice) {
			$neverPaid = s("Cette facture est actuellement enregistrée comme une facture qui ne sera pas payée, mais vous pouvez revenir sur votre choix.");
			$neverPaidConfirm = s("Vous allez indiquer que cette facture ne sera jamais payée. Voulez-vous continuer ?");
		}

		$h = $form->openAjax(self::getPrefix($e).':doUpdatePayment');

			$h .= $form->hidden('id', $e['id']);

			if($e['paymentStatus'] === Sale::NEVER_PAID) {
				$h .= $form->group(
					content: '<div class="util-block-info">'.$neverPaid.'</div>'
				);
			}

			$never = $e->acceptNeverPaid() ? '<a data-ajax="'.self::getPrefix($e).':doUpdateNeverPaid" post-id="'.$e['id'].'" class="btn btn-outline-primary" data-confirm="'.$neverPaidConfirm.'">'.s("Ne sera pas payée").'</a>' : '';

			if($e['priceIncludingVat'] !== NULL) {
				$title = s("Vente de {value}", \util\TextUi::money($e['priceIncludingVat']));
			} else {
				$title = s("Vente");
			}

			$h .= $form->group(content: '<div class="util-title">'.
				'<h4>'.$title.'</h4>'.
				$never.
			'</div>');
			$h .= $this->update($e, $e['cPayment'], $e['cPaymentMethod']);

			$h .= $form->group(
				content: '<div class="flex-justify-space-between">'.
					$form->submit(s("Enregistrer")).
					'<a class="btn btn-outline-primary" onclick="Payment.add()">'.\Asset::icon('plus-circle').' '.s("Ajouter un autre paiement").'</a>'.
				'</div>'
		);

		$h .= $form->close();

		return $h;

	}

	public function update(Sale|Invoice $e, \Collection $cPayment, \Collection $cMethod): string {

		$eFarm = $e['farm'];

		$form = new \util\FormUi();
		$position = 0;

		$saleAmount = $e['priceIncludingVat'];

		if(
			$cPayment->empty() or
			$cPayment->first()['status'] === Payment::NOT_PAID
		) {
			$paymentAmount = $saleAmount;
		} else {
			$paymentAmount = $e['paymentAmount'];
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

					$h .= $this->getUpdatePayment($form, $ePayment, $position++, $cMethod);

				}

				if($cPayment->empty()) {

					$h .= $this->getUpdatePayment($form, new Payment([
						'status' => Payment::NOT_PAID,
						'amountIncludingVat' => $saleAmount,
						'farm' => $eFarm,
						'paidAt' => currentDate(),
						'closed' => FALSE
					]), $position++, $cMethod);

				}

			$h .= '</div>';

			$h .= '<div id="payment-update-total" class="util-block-gradient '.($isBalanced ? '' : 'payment-update-total-error').'" data-position="'.$position.'" data-value="'.$paymentAmount.'" data-target="'.$saleAmount.'">';
				$h .= $form->group(
					s("Montant total renseigné"),
					'<span class="font-xl" style="font-weight: bold">'.\Asset::icon('exclamation-circle').'<span id="payment-update-amount">'.\util\TextUi::number($paymentAmount).'</span><span id="payment-update-target"> / '.\util\TextUi::number($saleAmount).'</span> €</span>'
				);
			$h .= '</div>';

			$h .= '<div id="payment-update-new" data-position="'.$position.'">';
				$h .= $this->getUpdatePayment($form, new Payment([
					'farm' => $eFarm,
					'status' => Payment::PAID,
					'paidAt' => currentDate(),
					'closed' => FALSE
				]), '', $cMethod);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	protected function getUpdatePayment(\util\FormUi $form, Payment $ePayment, int|string $position, \Collection $cMethod): string {

		$ePayment['cMethod'] = $cMethod;

		if($ePayment['closed']) {
			return $this->getClosedPayment($form, $ePayment, $position);
		}

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

	protected function getClosedPayment(\util\FormUi $form, Payment $ePayment, int|string $position): string {

		$h = '<div class="payment-update util-block bg-background-light">';

			$h .= $form->group(
				content: '<div class="util-action">'.
					'<h4>'.s("Paiement n°{value}", '<span class="payment-update-counter"></span>').'  '.\Asset::icon('lock-fill').'</h4>'.
				'</div>',
				attributes: ['class' => 'payment-update-title']
			);

			// Pour les calculs JS
			$h .= $form->hidden('amountIncludingVat', $ePayment['amountIncludingVat']);

			$accountingStatus = [];

			if($ePayment['accountingHash'] !== NULL) {
				$accountingStatus[] = '<a class="util-badge bg-accounting" href="'.\farm\FarmUi::urlConnected($ePayment['farm']).'/journal/livre-journal?hash='.$ePayment['accountingHash'].'&financialYearReset">'.\Asset::icon('journal-text').'</a> '.s("Paiement intégré en comptabilité");
			} else if($ePayment['cashflow']->notEmpty()) {
				$accountingStatus[] = '<a class="util-badge bg-accounting" href="'.\farm\FarmUi::urlConnected($ePayment['farm']).'/banque/operations?id='.$ePayment['cashflow']['id'].'">'.\Asset::icon('bank').'</a> '.s("Paiement rapproché");
			}

			if($accountingStatus) {

				$h .= $form->group(
					s("Comptabilité"),
					implode('<br/>', $accountingStatus)
				);

			}

			$h .= $form->group(
				PaymentUi::p('method')->label,
				encode($ePayment['method']['name'])
			);

			$h .= $form->group(
				PaymentUi::p('amountIncludingVat')->label,
				\util\TextUi::money($ePayment['amountIncludingVat'])
			);

			$h .= $form->group(
				PaymentUi::p('paidAt')->label,
				\util\DateUi::numeric($ePayment['paidAt'])
			);

		$h .= '</div>';

		return $h;

	}

	private static function getPrefix(Sale|Invoice $e): string {

		if($e instanceof Sale) {
			return '/selling/sale';
		} else if($e instanceof Invoice) {
			return '/selling/invoice';
		}

	}

}
