<?php
namespace association;

class MembershipLib {

	public static function expires(): void {

		$ccHistory = History::model()
			->select(History::getSelection())
			->wherePaymentStatus(History::SUCCESS)
			->sort(['membership' => SORT_DESC])
			->getCollection(NULL, NULL, ['farm', 'membership']);

		foreach($ccHistory as $cHistory) {

			$eHistory = $cHistory->first();

			if($eHistory['membership'] < date('Y')) {

				$eFarm = $eHistory['farm'];
				$eFarm['membership'] = FALSE;
				\farm\FarmLib::update($eFarm, ['membership']);

			}

		}

	}

	public static function getAssociationStripeFarm(): \payment\StripeFarm {

		$eFarmOTF = \farm\FarmLib::getById(\Setting::get('association\farm'));

		return \payment\StripeLib::getByFarm($eFarmOTF);
		
	}

	public static function createPayment(\farm\Farm $eFarm): string {

		$eFarm->expects(['id', 'siret']);

		$fw = new \FailWatch();

		$amount = POST('amount', 'int');

		if(empty(POST('terms'))) {
			\Fail::log('Membership::terms');
		}

		if($amount === NULL or $amount < \Setting::get('association\membershipFee')) {
			\Fail::log('Membership::amount');
		}

		$fw->validate();

		$eStripeFarm = self::getAssociationStripeFarm();

		if($eStripeFarm->empty()) {
			throw new \Exception('Missing stripe configuration for OTF');
		}

		$curl = new \util\CurlLib();
		$url = 'https://suggestions.pappers.fr/v2?cibles=siret&q='.urlencode(str_replace(' ', '', $eFarm['siret']));
		$result = json_decode($curl->exec($url, []));
		$legalForm = $result->resultats_siret[0]->forme_juridique ?? '';

		$items = [];
		$items[] = [
			'quantity' => 1,
			'price_data' => [
				'currency' => 'EUR',
				'product_data' => [
					'name' => new AssociationUi()->getProductName($amount > \Setting::get('association\membershipFee')),
				],
				'unit_amount' => ($amount * 100), // in cents, how much to charge
			]
		];

		$successUrl = AssociationUi::confirmationUrl($eFarm);
		$cancelUrl = AssociationUi::url($eFarm);

		$arguments = [
			'payment_intent_data' => [
				'metadata' => ['source' => 'otf', 'type' => 'membership']
			],
			'expires_at' => time() + 60 * 45,
			'client_reference_id' => $eFarm['id'],
			'line_items' => $items,
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments);
		$membershipYear = date('Y');

		$eHistory = History::model()
			->select(History::getSelection())
			->whereFarm($eFarm)
			->whereMembership($membershipYear)
			->wherePaymentStatus(History::INITIALIZED)
			->get();

		if($eHistory->empty()) {

			$eHistory = new History([
				'farm' => $eFarm,
				'checkoutId' => $stripeSession['id'],
				'amount' => $amount,
				'membership' => $membershipYear,
				'paymentStatus' => History::INITIALIZED,
				'legalForm' => $legalForm,
			]);

			History::model()->insert($eHistory);

		} else {

			History::model()->update(
				$eHistory, [
					'checkoutId' => $stripeSession['id'],
					'amount' => $amount,
					'paymentStatus' => History::INITIALIZED,
					'legalForm' => $legalForm,
				]
			);

		}

		return $stripeSession['url'];

	}

	public static function webhookPaymentIntent(array $event): void {

		$eHistory = self::getHistoryFromPaymentIntent($event);

		if($eHistory->empty()) {
			return;
		}

		switch($event['type']) {

			case 'payment_intent.partially_funded' :
			case 'payment_intent.payment_failed' :
			case 'payment_intent.canceled':
				self::paymentFailed($eHistory, $event);
				break;

			case 'payment_intent.succeeded' :
				self::paymentSucceeded($eHistory, $event);
				break;

		}

	}

	public static function paymentFailed(History $eHistory, array $event): void {

		$object = $event['data']['object'];

		HistoryLib::updateByPaymentIntentId($object['id'], [
			'paymentStatus' => \selling\Payment::FAILURE,
		]);

	}

	/**
	 * Validation d'un paiement par carte bancaire
	 */
	public static function paymentSucceeded(History $eHistory, array $event): void {

		$object = $event['data']['object'];

		$amountReceived = (int)$object['amount_received'];
		$amountExpected = (int)round($eHistory['amount'] * 100);

		if($amountReceived !== $amountExpected) {
			trigger_error('Amount received '.($object['amount_received'] / 100).' different from amount expected '.($eHistory['amount']).' in membership #'.$eHistory['id'].' (event #'.$object['id'].')', E_USER_WARNING);
			return;
		}

		History::model()->beginTransaction();

		HistoryLib::updateByPaymentIntentId($object['id'], [
			'paymentStatus' => \selling\Payment::SUCCESS,
			'paidAt' => new \Sql('NOW()'),
		]);

		$eHistory['farm']['membership'] = TRUE;
		\farm\FarmLib::update($eHistory['farm'], ['membership']);

		History::model()->commit();
	}

	private static function getHistoryFromPaymentIntent(array $event): History {

		$object = $event['data']['object'];

		$eHistory = HistoryLib::getByPaymentIntentId($object['id']);

		if($eHistory->empty()) {
			throw new \Exception('Unknown history for intentId '.$object['id']);
		}

		return $eHistory;

	}
}
