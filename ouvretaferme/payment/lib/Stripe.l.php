<?php
namespace payment;


/**
 * Stripe management
 */
class StripeLib {

	const API_URL = 'https://api.stripe.com/v1/';

	public static function loadSepa(\selling\Customer &$eCustomer) {

		if($eCustomer->empty() or isset($eCustomer['id']) === FALSE) {

			$eStripeCustomerSepa = new StripeCustomerSepa();

		} else {

			$eStripeCustomerSepa = StripeCustomerSepa::model()
				->select(StripeCustomerSepa::getSelection())
				->whereCustomer($eCustomer)
				->get();

		}

		$eCustomer['stripeSepa'] = $eStripeCustomerSepa ?? new StripeCustomerSepa();

	}

	public static function getByFarm(\farm\Farm $eFarm): StripeFarm {

		return StripeFarm::model()
			->select(StripeFarm::getSelection())
			->whereFarm($eFarm)
			->get();

	}

	/**
	 * Possibilité d'ajouter l'argument payment_intent_data[application_fee_amount] dans le cas où OTF prélève une commission.
	 *
	 * https://stripe.com/docs/connect/enable-payment-acceptance-guide?platform=web#create-checkout-session
	 *
	 */
	public static function createCheckoutSession(StripeFarm $eStripeFarm, array $arguments, string $salePayment): array {

		$endpoint = 'checkout/sessions';

		$paymentMethodTypes =  match($salePayment) {
			\selling\Sale::ONLINE_CARD => ['card'],
			\selling\Sale::ONLINE_SEPA => ['sepa_debit'],
			default => [],
		};

		$arguments += [
			'mode' => 'payment',
			'payment_method_types' => $paymentMethodTypes,
		];

		return self::sendStripeRequest($eStripeFarm, $endpoint, $arguments);

	}

	/**
	 * Ouvre une checkout session pour configurer les données bancaires en vue de paiements par prélèvement SEPA.
	 *
	 */
	public static function createCheckoutSessionSepa(StripeFarm $eStripeFarm, array $arguments): array {

		$eUser = \user\ConnectionLib::getOnline();
		$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eStripeFarm['farm']);
		self::loadSepa($eCustomer);

		if($eCustomer['stripeSepa']->empty() or $eCustomer['stripeSepa']['status'] !== StripeCustomerSepa::VALID) {
			self::createCustomer($eStripeFarm, $eCustomer, $arguments);
		}

		$endpoint = 'checkout/sessions';

		$arguments += [
			'customer' => $eCustomer['stripeSepa']['stripeCustomerId'],
			'mode' => 'setup',
			'payment_method_types' => ['sepa_debit'],
		];

		return self::sendStripeRequest($eStripeFarm, $endpoint, $arguments);

	}

	/**
	 * Crée un payment_intent chez Stripe
	 * https://stripe.com/docs/api/payment_intents/create
	 */
	public static function createPayment(StripeCustomerSepa $customerSepa, StripeFarm $eStripeFarm, array $arguments): array {

		$endpoint = 'payment_intents';

		$arguments += [
			'currency' => 'eur',
			'payment_method_types' => ['sepa_debit'],
			'customer' => $customerSepa['stripeCustomerId'],
			'payment_method' => $customerSepa['stripePaymentMethodId'],
			'confirm' => 'true',
		];

		return self::sendStripeRequest($eStripeFarm, $endpoint, $arguments);

	}

	private static function createCustomer(StripeFarm $eStripeFarm, \selling\Customer &$eCustomer, array $arguments): void {

		$eCustomer->expects(['id', 'stripeSepa']);

		$endpoint = 'customers';

		$customer = self::sendStripeRequest($eStripeFarm, $endpoint);

		if($eCustomer['stripeSepa']->empty()) {

			$eCustomer['stripeSepa'] = new StripeCustomerSepa([
				'customer' => $eCustomer,
				'stripeCustomerId' => $customer['id']
			]);
			StripeCustomerSepa::model()->insert($eCustomer['stripeSepa']);

		} else {

			$eCustomer['stripeSepa']['stripeCustomerId'] = $customer['id'];
			StripeCustomerSepa::model()->update($eCustomer['stripeSepa'], ['stripeCustomerId' => $customer['id']]);

		}

	}

	public static function getStripeCheckoutSession(StripeFarm $eStripeFarm, string $sessionId) {

		$endpoint = 'checkout/sessions/'.$sessionId;
		return self::sendStripeRequest($eStripeFarm, $endpoint, mode: 'GET');

	}

	public static function attachPaymentMethodToCustomerViaSession(StripeFarm $eStripeFarm, string $sessionId, \selling\Customer &$eCustomer) {

		StripeCustomerSepa::model()->beginTransaction();

		$eCustomer->expects(['stripeSepa']);

		// Première étape : récupérer la session (terminée en succès) pour avoir le setup_intent.
		$session = self::getStripeCheckoutSession($eStripeFarm, $sessionId);

		// La session n'étant pas terminée on ne peut rien faire.
		if($session['status'] !== 'complete') {
			return;
		}

		$setup_intent_id = $session['setup_intent'];
		$eCustomer['stripeSepa']['stripeSessionIntentId'] = $setup_intent_id;
		StripeCustomerSepa::model()
			->select('stripeSessionIntentId')
			->whereCustomer($eCustomer)
			->update($eCustomer['stripeSepa']);

		// Deuxième étape : Avec le setup_intent, récupérer l'ID de payment_method
		$endpoint = 'setup_intents/'.$setup_intent_id;
		$setup_intent = self::sendStripeRequest($eStripeFarm, $endpoint, mode: 'GET');

		if(in_array('sepa_debit', $setup_intent['payment_method_types']) === FALSE) {
			throw new \NotExpectedAction('Stripe: No sepa_debit to configure for Customer #'.$eCustomer['id'].' (session #'.$session['id'].', payment_intent #'.$setup_intent['id'].')');
		}

		$eCustomer['stripeSepa']['stripePaymentMethodId'] = $setup_intent['payment_method'];
		StripeCustomerSepa::model()
			->select('stripePaymentMethodId')
			->whereCustomer($eCustomer)
			->update($eCustomer['stripeSepa']);

		// Troisième étape : Rattacher le payment_method au customer Stripe.
		$arguments = ['customer' => $eCustomer['stripeSepa']['stripeCustomerId']];
		$endpoint = 'payment_methods/'.$eCustomer['stripeSepa']['stripePaymentMethodId'].'/attach?'.http_build_query($arguments);
		$payment_method = self::sendStripeRequest($eStripeFarm, $endpoint);

		// Quatrième étape : Enregistrer sur OTF que le customer est OK.
		$eCustomer['stripeSepa']['status'] = StripeCustomerSepa::VALID;
		StripeCustomerSepa::model()
			->select('status')
			->whereCustomer($eCustomer)
			->update($eCustomer['stripeSepa']);

		StripeCustomerSepa::model()->commit();

	}

	public static function webhook(array $event): void {

		if($event['type'] === 'checkout.session.completed' and $event['data']['object']['mode'] === 'setup') {
			self::webhookSetupSepaDebit($event);
		} else if(str_starts_with($event['type'], 'payment_intent')) {
			self::webhookPaymentIntent($event);
		}

	}

	public static function webhookPaymentIntent(array $event): void {

		$eSale = self::getSaleFromPaymentIntent($event);
		$object = $event['data']['object'];

		$error = FALSE;
		$stripePaymentMethods = $object['payment_method_types'];

		switch($eSale['paymentMethod']) {

			case \selling\Sale::ONLINE_SEPA :
				if(in_array('sepa_debit', $stripePaymentMethods) === FALSE) {
					$error = TRUE;
				}
				break;

			case \selling\Sale::ONLINE_CARD :
				if(in_array('card', $object['payment_method_types']) === FALSE) {
					$error = TRUE;
				}
				break;

			default :
				$error = TRUE;
				break;

		}

		if($error) {
			trigger_error('Sale::'.$eSale['paymentMethod'].' found, Stripe '.implode(', ', $stripePaymentMethods).' expected in sale #'.$eSale['id'].' (event #'.$event['id'].')', E_USER_WARNING);
			return;
		}

		switch($event['type']) {

			// Suivi des paiements SEPA
			case 'payment_intent.created' :

				if($eSale['paymentMethod'] === \selling\Sale::ONLINE_SEPA) {
					\shop\SaleLib::completeCheckoutSepaDebit($eSale, $event);
				}
				break;

			case 'payment_intent.partially_funded' :
			case 'payment_intent.payment_failed' :
				\shop\SaleLib::paymentFailed($eSale, $event);
				break;

			case 'payment_intent.succeeded' :
				\shop\SaleLib::paymentSucceeded($eSale, $event);
				break;

			case 'payment_intent.processing' :
				if($eSale['paymentMethod'] === \selling\Sale::ONLINE_SEPA) {
					\shop\SaleLib::paymentProcessingSepaDebit($eSale, $event);
				}
				break;

			case 'payment_intent.canceled':
				\shop\SaleLib::paymentFailed($eSale, $event);
				break;

		}

	}

	private static function getSaleFromPaymentIntent(array $event) {

		$object = $event['data']['object'];

		$ePayment = \selling\PaymentLib::getByProviderId(\selling\Payment::STRIPE, $object['id']);

		if($ePayment->empty()) {
			throw new \Exception('Unknown payment intent '.$object['id']);
		}

		$eSale = \selling\SaleLib::getById($ePayment['sale']);

		if($eSale->empty()) {
			throw new \Exception('Unknown sale #'.$ePayment['sale']['id']);
		}

		return $eSale;

	}

	public static function webhookSetupSepaDebit(array $event): void {

			$object = $event['data']['object'];

			$eStripeCustomerSepa = new StripeCustomerSepa();
			StripeCustomerSepa::model()
				->select(StripeCustomerSepa::getSelection() + ['customer' => ['id', 'farm', 'user']])
				->whereStripeCustomerId($object['id'])
				->get($eStripeCustomerSepa);
			$eCustomer = $eStripeCustomerSepa['customer'];

			$eStripeFarm = new StripeFarm();
			StripeFarm::model()
				->select(StripeFarm::getSelection())
				->whereFarm($eCustomer['farm'])
				->get($eStripeFarm);

			self::attachPaymentMethodToCustomerViaSession($eStripeFarm, $object['id'], $eCustomer);
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

		$header = [];
		$options = [
			CURLOPT_HTTPHEADER => array_merge(['Authorization: Bearer '.$key], $header),
			CURLOPT_VERBOSE => TRUE,
		];

		return json_decode((new \util\CurlLib())->exec(self::API_URL.$endpoint, $arguments, $mode, $options), TRUE);

	}


}
