<?php
namespace association;

class MembershipLib {

	private static function getProductName(string $type) {

		return match($type) {
			History::MEMBERSHIP => new AssociationUi()->getMembershipProductName(),
			History::DONATION => new AssociationUi()->getProductDonationName(),
		};

	}
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

	public static function createPayment(\farm\Farm $eFarm, string $type): string {

		$eFarm->expects(['id', 'siret']);

		$fw = new \FailWatch();

		$eHistory = new History();
		$eHistory->build(['type', 'amount', 'terms'], $_POST + ['type' => $type]);

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
					'name' => self::getProductName($type),
				],
				'unit_amount' => ($eHistory['amount'] * 100),
			]
		];

		$successUrl = AssociationUi::confirmationUrl($eFarm, $type);
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
		$membershipYear = $type === History::MEMBERSHIP ? date('Y') : NULL;

		$eHistoryDb = History::model()
			->select(History::getSelection())
			->whereFarm($eFarm)
			->whereType($eHistory['type'])
			->whereMembership($membershipYear)
			->wherePaymentStatus(History::INITIALIZED)
			->get();

		if($eHistoryDb->empty()) {

			$eHistory->merge([
				'farm' => $eFarm,
				'checkoutId' => $stripeSession['id'],
				'membership' => $membershipYear,
				'paymentStatus' => History::INITIALIZED,
			]);

			History::model()->insert($eHistory);

		} else {

			History::model()->update(
				$eHistory, [
					'checkoutId' => $stripeSession['id'],
					'paymentStatus' => History::INITIALIZED,
					'updatedAt' => new \Sql('NOW()'),
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
				self::paymentFailed($event);
				break;

			case 'payment_intent.succeeded' :
				self::paymentSucceeded($eHistory, $event);
				break;

		}

	}

	public static function paymentFailed(array $event): void {

		$object = $event['data']['object'];

		HistoryLib::updateByPaymentIntentId($object['id'], [
			'paymentStatus' => \selling\Payment::FAILURE,
		]);

	}

	public static function paymentSucceeded(History $eHistory, array $event): void {

		$object = $event['data']['object'];

		$amountReceived = (int)$object['amount_received'];
		$amountExpected = (int)round($eHistory['amount'] * 100);

		if($amountReceived !== $amountExpected) {
			trigger_error('Amount received '.($object['amount_received'] / 100).' different from amount expected '.($eHistory['amount']).' in history #'.$eHistory['id'].' (event #'.$object['id'].')', E_USER_WARNING);
			return;
		}

		History::model()->beginTransaction();

		$eFarm = $eHistory['farm'];

		if($eHistory['type'] === History::MEMBERSHIP) {

			$eFarm['membership'] = TRUE;
			\farm\FarmLib::update($eFarm, ['membership']);

		}

		$eFarmOtf = \farm\FarmLib::getById(\Setting::get('association\farm'));

		$ePaymentMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		// Récupération ou création du customer
		$eCustomer = \selling\CustomerLib::getBySiret($eFarmOtf, $eFarm['siret']);
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

		// Création d'une vente et du produit
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
			'onlinePaymentStatus' => \selling\Sale::SUCCESS,
			'deliveredAt' => new \Sql('NOW()'),
		]);
		\selling\SaleLib::create($eSale);

		$eItem = new \selling\Item([
				'farm' => $eFarm,
				'sale' => $eSale,
				'name' => self::getProductName($eHistory['type']),
				'customer' => $eCustomer,
				'unitPrice' => $eHistory['amount'],
				'number' => 1,
				'product' => new \selling\Product(),
				'locked' => \selling\Item::PRICE,
				'packaging' => NULL,
		]);

		\selling\ItemLib::create($eItem);

		$eSale['paymentStatus'] = \selling\Sale::PAID;
		$eSale['paymentMethod'] = $ePaymentMethod;
		\selling\Sale::model()
			->select(['paymentStatus', 'paymentMethod'])
			->update($eSale);

		HistoryLib::updateByPaymentIntentId($object['id'], [
			'customer' => $eCustomer,
			'paymentStatus' => \selling\Payment::SUCCESS,
			'paidAt' => new \Sql('NOW()'),
			'sale' => $eSale,
		]);

		HistoryLib::generateDocument($eHistory);

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
