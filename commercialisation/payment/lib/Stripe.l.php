<?php
namespace payment;


/**
 * Stripe management
 */
class StripeLib {

	const API_URL = 'https://api.stripe.com/v1/';

	public static function getByFarm(\farm\Farm $eFarm): StripeFarm {

		return StripeFarm::model()
			->select(StripeFarm::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	public static function getWebhookUrl(\farm\Farm $eFarm): string {
		return \Lime::getUrl().'/payment/stripe:webhook?farm='.$eFarm['id'];
	}

	public static function createWebhook(StripeFarm $eStripeFarm): void {

		// On supprime d'anciens webhooks liés à OTF
		$webhooks = self::getWebhooks($eStripeFarm);

		foreach($webhooks['data'] as $webhook) {

			if(
				$webhook['object'] === 'webhook_endpoint' and
				str_starts_with($webhook['url'], self::getWebhookUrl($eStripeFarm['farm']))
			) {
				self::deleteWebhook($eStripeFarm, $webhook['id']);
			}

		}

		// On crée un nouveau webhook
		$arguments = [
			'url' => self::getWebhookUrl($eStripeFarm['farm']),
			'description' => 'Created by '.\Lime::getName(),
			'enabled_events' => [
				'payment_intent.amount_capturable_updated',
				'payment_intent.canceled',
				'payment_intent.created',
				'payment_intent.partially_funded',
				'payment_intent.payment_failed',
				'payment_intent.processing',
				'payment_intent.requires_action',
				'payment_intent.succeeded',
				'checkout.session.async_payment_failed',
				'checkout.session.async_payment_succeeded',
				'checkout.session.completed',
				'checkout.session.expired'
			],
		];

		$data = self::sendStripeRequest($eStripeFarm, 'webhook_endpoints', $arguments);

		$eStripeFarm['webhookSecretKey'] = $data['secret'];

		StripeFarm::model()
			->select('webhookSecretKey')
			->update($eStripeFarm);

	}

	public static function deleteWebhook(StripeFarm $eStripeFarm, string $id): void {
		self::sendStripeRequest($eStripeFarm, 'webhook_endpoints/'.$id, mode: 'DELETE');
	}

	public static function getWebhooks(StripeFarm $eStripeFarm): array {

		return self::sendStripeRequest($eStripeFarm, 'webhook_endpoints', mode: 'GET');

	}

	public static function createCheckoutSession(StripeFarm $eStripeFarm, array $arguments): array {

		$arguments += [
			'mode' => 'payment',
			'payment_method_types' => ['card'],
		];

		return self::sendStripeRequest($eStripeFarm, 'checkout/sessions', $arguments);

	}

	public static function expiresCheckoutSession(StripeFarm $eStripeFarm, string $sessionId): array {

		return self::sendStripeRequest($eStripeFarm, 'checkout/sessions/'.$sessionId.'/expire');

	}

	public static function getStripeCheckoutSessionFromPaymentIntent(StripeFarm $eStripeFarm, string $paymentIntentId) {

		$arguments = [
			'payment_intent' => $paymentIntentId,
		];
		return self::sendStripeRequest($eStripeFarm, 'checkout/sessions', $arguments, mode: 'GET');

	}

	public static function webhook(StripeFarm $eStripeFarm, array $event): void {

		if(str_starts_with($event['type'], 'payment_intent')) {
			self::webhookPaymentIntent($eStripeFarm, $event);
		}

	}

	public static function webhookPaymentIntent(StripeFarm $eStripeFarm, array $event): void {

		// Les utilisateurs qui partagent leur compte stripe avec d'autres services
		if(($event['data']['object']['metadata']['source'] ?? NULL) !== 'otf') {
			return;
		}

		$eSale = self::getSaleFromPaymentIntent($eStripeFarm, $event);
		$eSale['shop']['farm'] = $eSale['farm']; // Uniquement sur les boutiques personnelles

		if($eSale->empty()) {
			return;
		}

		$object = $event['data']['object'];

		$error = FALSE;
		$stripePaymentMethods = $object['payment_method_types'];

		$ePayment = \selling\PaymentLib::getBySaleAndMethod($eSale, MethodLib::ONLINE_CARD);

		if($ePayment->notEmpty()) {

			if(in_array('card', $object['payment_method_types']) === FALSE) {
				$error = TRUE;
			}

		} else {
			$error = TRUE;
		}

		if($error) {
			trigger_error('Sale #'.$eSale['id'].' found, Stripe '.implode(', ', $stripePaymentMethods).' expected in sale #'.$eSale['id'].' (event #'.$event['id'].')', E_USER_WARNING);
			return;
		}

		switch($event['type']) {

			case 'payment_intent.partially_funded' :
			case 'payment_intent.payment_failed' :
				\shop\SaleLib::paymentFailed($eSale, $event);
				break;

			case 'payment_intent.succeeded' :
				\shop\SaleLib::paymentSucceeded($eSale, $event);
				break;

			case 'payment_intent.canceled':
				\shop\SaleLib::paymentFailed($eSale, $event);
				break;

		}

	}

	private static function getSaleFromPaymentIntent(StripeFarm $eStripeFarm, array $event): \selling\Sale {

		$object = $event['data']['object'];

		$ePayment = \selling\PaymentLib::getByPaymentIntentId($eStripeFarm, $object['id']);

		if($ePayment->empty()) {
			throw new \Exception('Unknown payment for intentId '.$object['id']);
		}

		$eSale = \selling\SaleLib::getById($ePayment['sale']['id']);

		if($eSale->empty()) {
			throw new \Exception('Unknown sale #'.$ePayment['sale']['id']);
		}

		return $eSale;

	}

	public static function getEvent(StripeFarm $eStripeFarm): array {

		$payload = @file_get_contents('php://input');

		self::checkSignature($eStripeFarm, $payload);

		return json_decode($payload, TRUE);

	}

	/**
	 * https://stripe.com/docs/webhooks/signatures
	 */
	private static function checkSignature(StripeFarm $eStripeFarm, string $payload) {

		$webhookSecretKey = match(LIME_ENV) {
			'dev' => $eStripeFarm['webhookSecretKeyTest'],
			'prod' => $eStripeFarm['webhookSecretKey'],
		};

		if($webhookSecretKey === NULL) {
			throw new \Exception('No valid key for '.LIME_ENV);
		}

		$signature = SERVER('HTTP_STRIPE_SIGNATURE');

		$elementsString = explode(',', $signature);
		$elements = [];
		foreach($elementsString as $elementString) {
			if(str_contains($elementString, '=') === FALSE) {
				throw new \Exception('Stripe webhook: Error in checkSignature.');
			}
			list($key, $value) = explode('=', $elementString);
			$elements[$key] = $value;
		}

		$signedPayload = ($elements['t'] ?? '').'.'.$payload;
		$expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecretKey);

		if($expectedSignature !== $elements['v1']) {
			throw new \Exception('Stripe webhook: Error in checkSignature (t = '.$elements['t'].', v1 = '.$elements['v1'].', payload = '.$payload.').');
		}

	}

	public static function getPaymentIntentDetails(StripeFarm $eStripeFarm, string $paymentIntent): array {

		return self::sendStripeRequest($eStripeFarm, 'payment_intents/'.encode($paymentIntent));

	}


	private static function sendStripeRequest(StripeFarm $eStripeFarm, string $endpoint, array $arguments = [], string $mode = 'POST'): ?array {

		$key = match(LIME_ENV) {
			'dev' => $eStripeFarm['apiSecretKeyTest'],
			'prod' => $eStripeFarm['apiSecretKey'],
		};

		if($key === NULL) {
			throw new \Exception('No valid key for '.LIME_ENV);
		}

		$header = ['Authorization: Bearer '.$key];

		if($mode === 'POST' or $mode === 'DELETE') {
			$header[] = 'Content-Type: application/x-www-form-urlencoded';
		}

		$options = [
			CURLOPT_HTTPHEADER => $header,
		];

		$curl = new \util\CurlLib();

		$data = $curl->exec(self::API_URL.$endpoint, $arguments, $mode, $options);
		$httpCode = $curl->getLastInfos()['httpCode'];

		if($httpCode === 200) {
			return json_decode($data, TRUE);
		} else {
			throw new \Exception('Stripe error (HTTP code is '.$httpCode.')', $httpCode);
		}

	}


}
