<?php
namespace association;

class MembershipLib {

	private static function getProductName(string $type, ?int $year) {

		return match($type) {
			History::MEMBERSHIP => new AssociationUi()->getMembershipProductName($year),
			History::DONATION => new AssociationUi()->getProductDonationName(),
		};

	}

	// à ne faire tourner qu'une fois en début d'année
	public static function expires(): void {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereMembership(TRUE)
			->getCollection();

		$cHistoryNextYear = History::model()
			->select(History::getSelection())
			->whereFarm($cFarm)
			->whereType(History::MEMBERSHIP)
			->whereMembership(date('Y'))
			->whereStatus(History::VALID)
			->getCollection(NULL, NULL, 'farm');

		foreach($cFarm as $eFarm) {

			if($cHistoryNextYear->offsetExists($eFarm['id'])) {
				continue;
			}

			$eFarm['membership'] = FALSE;
			\farm\FarmLib::update($eFarm, ['membership']);

		}

	}

	public static function getAssociationStripeFarm(\farm\Farm $eFarmOtf = new \farm\Farm()): \payment\StripeFarm {

		if($eFarmOtf->empty()) {

			$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);

		}

		return \payment\StripeLib::getByFarm($eFarmOtf);
		
	}

	public static function createPayment(\farm\Farm $eFarm, string $type): string {

		$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);

		if($eFarm->notEmpty()) {

			$eFarm->expects(['id', 'siret']);
			$eCustomer = \selling\CustomerLib::getBySiret($eFarmOtf, $eFarm['siret']);
			$eUser = \user\ConnectionLib::getOnline();

		} else {

			$eUser = new \user\User(['visibility' => \user\User::PUBLIC]);

			$fw = new \FailWatch();

			if(\Filter::check('email', $_POST['email'])) {
				$eUser['email'] = $_POST['email'];
			} else {
				\user\User::fail('email.check');
			}

			$eUser->build(['firstName', 'lastName', 'phone', 'street1', 'street2', 'postcode', 'city'], $_POST);

			if($eUser['street1'] === NULL and $eUser['street2'] === NULL and $eUser['postcode'] === NULL and $eUser['city'] === NULL) {
				\user\User::fail('address.check');
			}
			$fw->validate();

			$eCustomer = \selling\Customer::model()
				->select(\selling\Customer::getSelection())
				->whereInvoiceEmail($eUser['email'])
				->whereFarm($eFarmOtf)
				->whereFirstName($eUser['firstName'])
				->whereLastName($eUser['lastName'])
				->wherePhone($eUser['phone'])
				->whereInvoiceStreet1($eUser['street1'])
				->whereInvoiceStreet2($eUser['street2'])
				->whereInvoicePostcode($eUser['postcode'])
				->whereInvoiceCity($eUser['city'])
				->sort(['createdAt' => SORT_DESC])
				->get();

		}

		$fw = new \FailWatch();

		$eHistory = new History();
		$eHistory->build(['type', 'amount', 'terms'], $_POST + ['type' => $type]);

		$fw->validate();

		$eStripeFarm = self::getAssociationStripeFarm($eFarmOtf);

		if($eStripeFarm->empty()) {
			throw new \Exception('Missing stripe configuration for OTF');
		}

		History::model()->beginTransaction();

		// Création du customer
		$ePaymentMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		if($eCustomer->empty()) {

			$eCustomer = new \selling\Customer([
				'firstName' => $eUser['firstName'],
				'lastName' => $eUser['lastName'],
				'defaultPaymentMethod' => $ePaymentMethod,
				'phone' => $eUser['phone'],
				'farm' => $eFarmOtf,
			]);

			if($eFarm->notEmpty()) {

				$eCustomer->merge([
					'type' => \selling\Customer::PRO,
					'name' => $eFarm['name'],
					'legalName' => $eFarm['legalName'],
					'invoiceStreet1' => $eFarm['legalStreet1'],
					'invoiceStreet2' => $eFarm['legalStreet2'],
					'invoicePostcode' => $eFarm['legalPostcode'],
					'invoiceCity' => $eFarm['legalCity'],
					'invoiceEmail' => $eFarm['legalEmail'],
					'siret' => $eFarm['siret'],
				]);

			} else {

				$eCustomer->merge([
					'name' => $eUser->getName(),
					'type' => \selling\Customer::PRIVATE,
					'destination' => \selling\Customer::INDIVIDUAL,
					'invoiceStreet1' => $eUser['street1'],
					'invoiceStreet2' => $eUser['street2'],
					'invoicePostcode' => $eUser['postcode'],
					'invoiceCity' => $eUser['city'],
					'invoiceEmail' => $eUser['email'],
				]);

			}

			\selling\CustomerLib::create($eCustomer);

		}

		if($type === History::MEMBERSHIP) {

			$currentYear = date('Y');

			// On cotise pour l'année prochaine
			if(
				History::model()
					->whereFarm($eFarm)
					->whereMembership($currentYear)
					->whereType(History::MEMBERSHIP)
					->whereStatus(History::VALID)
					->exists()
			) {

				$membershipYear = nextYear();

			} else {

				$membershipYear = $currentYear;

			}

		} else {
			$membershipYear = NULL;
		}

		$items = [];
		$items[] = [
			'quantity' => 1,
			'price_data' => [
				'currency' => 'EUR',
				'product_data' => [
					'name' => self::getProductName($type, $membershipYear),
				],
				'unit_amount' => ($eHistory['amount'] * 100),
			]
		];

		$eHistoryDb = History::model()
     ->select(History::getSelection() + ['customer' => ['id', 'invoiceEmail']])
     ->whereCustomer($eCustomer)
     ->whereType($eHistory['type'])
     ->whereMembership($membershipYear)
     ->wherePaymentStatus(History::INITIALIZED)
     ->get();

		if($eHistoryDb->empty()) {

			$eHistory->merge([
				'farm' => $eFarm,
				'membership' => $membershipYear,
				'paymentStatus' => History::INITIALIZED,
				'status' => History::PROCESSING,
				'customer' => $eCustomer,
			]);

			History::model()->insert($eHistory);

		} else {

			History::model()->update(
				$eHistoryDb, [
					'amount' => $eHistory['amount'],
					'paymentStatus' => History::INITIALIZED,
					'status' => History::PROCESSING,
					'updatedAt' => new \Sql('NOW()'),
				]
			);

			$eHistory = $eHistoryDb;

		}

		$successUrl = AssociationUi::confirmationUrl($eHistory, $type, POST('from'));
		$cancelUrl = $eFarm->empty() ? POST('from') : AssociationUi::url($eFarm);

		$arguments = [
			'payment_intent_data' => [
				'metadata' => ['source' => 'otf', 'type' => 'membership']
			],
			'expires_at' => time() + 60 * 45,
			'client_reference_id' => $eCustomer['id'],
			'line_items' => $items,
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments);

		History::model()->update(
			$eHistory, [
				'checkoutId' => $stripeSession['id'],
			]
		);

		History::model()->commit();

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
			'paymentStatus' => History::FAILURE,
			'status' => History::INVALID
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

		$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);

		$ePaymentMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		// Récupération ou création du customer
		$eCustomer = \selling\CustomerLib::getById($eHistory['customer']['id']);

		// Création d'une vente et de l'item
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
				'farm' => $eFarmOtf,
				'sale' => $eSale,
				'name' => self::getProductName($eHistory['type'], $eHistory['membership']),
				'customer' => $eCustomer,
				'unitPrice' => $eHistory['amount'],
				'number' => 1,
				'product' => new \selling\Product(),
				'locked' => \selling\Item::PRICE,
				'packaging' => NULL,
		]);

		\selling\ItemLib::create($eItem);

		$eSale['paymentStatus'] = \selling\Sale::PAID;
		\selling\Sale::model()
			->select(['paymentStatus'])
			->update($eSale);

		HistoryLib::updateByPaymentIntentId($object['id'], [
			'customer' => $eCustomer,
			'paymentStatus' => History::SUCCESS,
			'status' => History::VALID,
			'paidAt' => new \Sql('NOW()'),
			'sale' => $eSale,
		]);

		// Promo adhésion 2025-2026
		// Promotion : toute adhésion en 2025 donne droit à une adhésion pour 2026
		if($eHistory['type'] === History::MEMBERSHIP and $eHistory['membership'] === 2025) {

			$eHistory2026 = new History([
				'farm' => $eFarm,
				'customer' => $eCustomer,
				'membership' => 2026,
				'type' => History::MEMBERSHIP,
				'status' => History::VALID,
				'amount' => 0,
			]);

			History::model()->insert($eHistory2026);

		}

		History::model()->commit();

		$pdfContent = HistoryLib::generateDocument($eHistory);

		// Envoi d'un email
		new \mail\SendLib()
			->setFarm($eFarmOtf)
			->setCustomer($eCustomer)
			->setFromName($eFarmOtf['name'])
			->setTo($eCustomer['invoiceEmail'])
			->setReplyTo($eFarmOtf['legalEmail'])
			->setContent(...new AssociationUi()->getDocumentMail($eFarmOtf, $eHistory))
			->addAttachment($pdfContent, new AssociationUi()->getDocumentFilename($eHistory).'.pdf', 'application/pdf')
			->send();
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
