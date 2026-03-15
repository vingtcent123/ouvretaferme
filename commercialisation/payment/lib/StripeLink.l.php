<?php
namespace payment;

class StripeLinkLib extends StripeLib {

	use \Notifiable;

	public static function create(StripeFarm $eStripeFarm, \selling\PaymentLink $ePaymentLink): array {

		$eElement = $ePaymentLink->getElement();

		$metadata = [
			'source' => 'otf',
			'type' => 'link',
			'elementId' => $eElement['id'],
			'paymentLinkId' => $ePaymentLink['id'],
			'element' => $ePaymentLink['source'],
		];

		$urlSuccess = \Lime::getUrl().'/paiement?id='.$ePaymentLink['id'].'&element='.$eElement['id'];

		$arguments = [
			'after_completion' => [
				'type' => 'redirect',
				'redirect' => ['url' => $urlSuccess],
			],
			'payment_intent_data' => [
				'metadata' => $metadata,
			],
			'restrictions' => [
				'completed_sessions' => [
					'limit' => 1,
				],
			],
			'line_items' => [
				[
					'quantity' => 1,
					'price_data' => [
						'currency' => 'EUR',
						'product_data' => [
							'name' => $ePaymentLink->getElementName(),
						],
					'unit_amount' => ($ePaymentLink['amountIncludingVat'] * 100), // in cents, how much to charge
					],
				],
			],
		];

		return self::sendStripeRequest($eStripeFarm, 'payment_links', $arguments);

	}


	public static function toggleActivation(StripeFarm $eStripeFarm, \selling\PaymentLink $ePaymentLink, bool $value): array {

		return self::sendStripeRequest($eStripeFarm, 'payment_links/'.$ePaymentLink['paymentLinkId'].'?active='.($value ? 'true': 'false'));

	}

	public static function webhookCheckoutSessionCompleted(StripeFarm $eStripeFarm, array $event): void {

		$object = $event['data']['object'];

		if(empty(($object['payment_link'] ?? NULL))) {
			return;
		}

		$paymentLinkId = $object['payment_link'];

		$ePaymentLink = \selling\PaymentLink::model()
			->select(\selling\PaymentLink::getSelection())
			->wherePaymentLinkId($paymentLinkId)
			->whereFarm($eStripeFarm['farm'])
			->get();

		if($ePaymentLink->empty()) {
			return;
		}

		$amountReceived = (int)$object['amount_total'];
		$amountExpected = (int)round($ePaymentLink['amountIncludingVat'] * 100);

		$eElement = $ePaymentLink->getElement();

		if($amountReceived !== $amountExpected) {
			trigger_error('Amount received '.($amountReceived / 100).' different from amount expected '.($ePaymentLink['amountIncludingVat']).' in '.$ePaymentLink['source'].' #'.$eElement['id'].' (event #'.$object['id'].')', E_USER_WARNING);
			return;
		}

		\selling\PaymentLinkLib::paymentSucceed($ePaymentLink, $event);

	}

}
