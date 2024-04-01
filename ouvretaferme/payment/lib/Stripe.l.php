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

	public static function createCheckoutSession(StripeFarm $eStripeFarm, array $arguments): array {

		$endpoint = 'checkout/sessions';

		$arguments += [
			'mode' => 'payment',
			'payment_method_types' => ['card'],
		];

		return self::sendStripeRequest($eStripeFarm, $endpoint, $arguments);

	}

	public static function getStripeCheckoutSessionFromPaymentIntent(StripeFarm $eStripeFarm, string $paymentIntentId) {

		$endpoint = 'checkout/sessions';
		$arguments = [
			'payment_intent' => $paymentIntentId,
		];
		return self::sendStripeRequest($eStripeFarm, $endpoint, $arguments, mode: 'GET');

	}

	public static function webhook(StripeFarm $eStripeFarm, array $event): void {

		if(str_starts_with($event['type'], 'payment_intent')) {
			self::webhookPaymentIntent($eStripeFarm, $event);
		}

	}

	public static function webhookPaymentIntent(StripeFarm $eStripeFarm, array $event): void {

		$eSale = self::getSaleFromPaymentIntent($eStripeFarm, $event);
		$object = $event['data']['object'];

		$error = FALSE;
		$stripePaymentMethods = $object['payment_method_types'];

		if($eSale['paymentMethod'] === \selling\Sale::ONLINE_CARD) {

			if(in_array('card', $object['payment_method_types']) === FALSE) {
				$error = TRUE;
			}

		} else {
			$error = TRUE;
		}

		if($error) {
			trigger_error('Sale::'.$eSale['paymentMethod'].' found, Stripe '.implode(', ', $stripePaymentMethods).' expected in sale #'.$eSale['id'].' (event #'.$event['id'].')', E_USER_WARNING);
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

	private static function getSaleFromPaymentIntent(StripeFarm $eStripeFarm, array $event) {

		$object = $event['data']['object'];

		$ePayment = \selling\PaymentLib::getByPaymentIntentId($eStripeFarm, $object['id']);

		if($ePayment->empty()) {
			throw new \Exception('Unknown payment intent '.$object['id']);
		}

		$eSale = \selling\SaleLib::getById($ePayment['sale']);

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
			'preprod' => $eStripeFarm['webhookSecretKeyTest'],
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
			throw new \Exception('Stripe webhook: Error in checkSignature.');
		}

	}

	private static function sendStripeRequest(StripeFarm $eStripeFarm, string $endpoint, array $arguments = [], string $mode = 'POST'): array {

		$key = match(LIME_ENV) {
			'dev' => $eStripeFarm['apiSecretKeyTest'],
			'prod' => $eStripeFarm['apiSecretKey'],
			'preprod' => $eStripeFarm['apiSecretKeyTest'],
		};

		if($key === NULL) {
			throw new \Exception('No valid key for '.LIME_ENV);
		}

		$header = ['Authorization: Bearer '.$key];

		if($mode === 'POST') {
			$header[] = 'Content-Type: application/x-www-form-urlencoded';
		}

		$options = [
			CURLOPT_HTTPHEADER => $header,
			CURLOPT_VERBOSE => TRUE,
		];

		return json_decode((new \util\CurlLib())->exec(self::API_URL.$endpoint, $arguments, $mode, $options), TRUE);

	}


}
