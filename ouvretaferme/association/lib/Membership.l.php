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

		$items = [];
		$items[] = [
			'quantity' => 1,
			'price_data' => [
				'currency' => 'EUR',
				'product_data' => [
					'name' => new AssociationUi()->getProductName(),
				],
				'unit_amount' => (\Setting::get('association\membershipFee') * 100),
			]
		];

		if($amount > \Setting::get('association\membershipFee')) {

			$items[] = [
				'quantity' => 1,
				'price_data' => [
					'currency' => 'EUR',
					'product_data' => [
						'name' => new AssociationUi()->getProductDonationName(),
					],
					'unit_amount' => (($amount - \Setting::get('association\membershipFee')) * 100),
				]
			];
		}

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
			]);

			History::model()->insert($eHistory);

		} else {

			History::model()->update(
				$eHistory, [
					'checkoutId' => $stripeSession['id'],
					'amount' => $amount,
					'paymentStatus' => History::INITIALIZED,
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

		$eFarm = $eHistory['farm'];
		$eFarm['membership'] = TRUE;
		\farm\FarmLib::update($eFarm, ['membership']);

		$eFarmOtf = \farm\FarmLib::getById(\Setting::get('association\farm'));

		// Création d'une vente
		$eCustomer = \selling\CustomerLib::getBySiret($eFarmOtf, $eFarm['siret']);
		$ePaymentMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		if($eCustomer->empty()) {

			$eUser = \user\ConnectionLib::getOnline();
			$eCustomer = new \selling\Customer([
				'category' => \selling\Customer::PRO,
				'firstName' => $eUser['firstName'],
				'lastName' => $eUser['lastName'],
				'name' => $eFarm['name'],
				'legalName' => $eFarm['legalName'],
				'invoiceStreet1' => $eFarm['legalStreet1'],
				'invoiceStreet2' => $eFarm['legalStreet2'],
				'invoicePostcode' => $eFarm['legalPostcode'],
				'invoiceCity' => $eFarm['legalCity'],
				'invoiceEmail' => $eFarm['legalEmail'],
				'siret' => $eFarm['siret'],
				'invoiceVat' => $eFarm->getSelling('invoiceVat'),
				'defaultPaymentMethod' => $ePaymentMethod,
				'phone' => $eUser['phone'],
				'type' => \selling\Customer::PRO,
				'farm' => $eFarmOtf,
			]);
			\selling\CustomerLib::create($eCustomer);

		}

		$eSale = new \selling\Sale([
			'farm' => $eFarmOtf,
			'customer'=> $eCustomer,
			'origin' => \selling\Sale::SALE,
			'shop' => new \shop\Shop(),
			'taxes' => \selling\Sale::EXCLUDING,
			'type' => \selling\Sale::PRO,
			'hasVat' => FALSE,
			'priceGross' => $eHistory['amount'],
			'priceExcludingVat' => NULL,
			'priceIncludingVat' => $eHistory['amount'],
			'preparationStatus' => \selling\Sale::DELIVERED,
			'paymentMethod' => $ePaymentMethod,
			'paymentStatus' => \selling\Sale::PAID,
			'onlinePaymentStatus' => \selling\Sale::SUCCESS,
			'deliveredAt' => new \Sql('NOW()'),
		]);
		\selling\SaleLib::create($eSale);

		$cItem = new \Collection(
			[
				new \selling\Item([
					'farm' => $eFarm,
					'sale' => $eSale,
					'name' => new \association\AssociationUi()->getProductName(),
					'customer' => $eCustomer,
					'unitPrice' => \Setting::get('association\membershipFee'),
					'number' => 1,
					'product' => new \selling\Product(),
					'locked' => \selling\Item::PRICE,
					'packaging' => NULL,
				]),
			]
		);

		if($eHistory['amount'] > \Setting::get('association\membershipFee')) {
			$cItem->append(
				new \selling\Item([
					'farm' => $eFarm,
					'sale' => $eSale,
					'name' => new \association\AssociationUi()->getProductDonationName(),
					'customer' => $eCustomer,
					'unitPrice' => ($eHistory['amount'] - \Setting::get('association\membershipFee')),
					'number' => 1,
					'product' => new \selling\Product(),
					'locked' => \selling\Item::PRICE,
					'packaging' => NULL,
				])
			);
		}

		\selling\ItemLib::createCollection($eSale, $cItem);

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
