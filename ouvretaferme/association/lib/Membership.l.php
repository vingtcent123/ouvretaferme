<?php
namespace association;

class MembershipLib {

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

	public static function count(): int {

		return \Cache::redis()->query('membership-count', function() {

			return \farm\Farm::model()
				->whereMembership(TRUE)
				->count();

		}, 86400);


	}

	public static function getAssociationStripeFarm(\farm\Farm $eFarmOtf = new \farm\Farm()): \payment\StripeFarm {

		if($eFarmOtf->empty()) {

			$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);

		}

		return \payment\StripeLib::getByFarm($eFarmOtf);

	}

	public static function sendDocuments(): void {

		$cHistory = History::model()
			->select(History::getSelection())
			->whereDocumentStatus(History::GENERATED)
			->getCollection();

		$eFarmOtf = \farm\FarmLib::getById(\association\AssociationSetting::FARM);

		foreach($cHistory as $eHistory) {

			if(History::model()->update($eHistory, ['documentStatus' => History::SENDING]) === 0) {
				continue;
			}

			History::model()->beginTransaction();

				$pdfContent = HistoryLib::getPdfContent($eHistory['document']);
				$eHistory['customer']['invoiceEmail'] = 'emilie.guth@gmail.com';

				// Envoi d'un email
				new \mail\SendLib()
					->setFarm($eFarmOtf)
					->setCustomer($eHistory['sale']['customer'])
					->setFromName($eFarmOtf['name'])
					->setTo($eHistory['customer']['invoiceEmail'])
					->setReplyTo($eFarmOtf['legalEmail'])
					->setContent(...new AssociationUi()->getDocumentMail($eFarmOtf, $eHistory))
					->addAttachment($pdfContent, new AssociationUi()->getDocumentFilename($eHistory).'.pdf', 'application/pdf')
					->send();

				History::model()->update($eHistory, ['documentStatus' => History::SENT]);

			History::model()->commit();

		}
	}

	public static function generateDocuments(): void {

		$cHistory = History::model()
			->select(History::getSelection())
			->whereStatus(History::VALID)
			->whereDocument(NULL)
			->whereDocumentStatus(History::WAITING)
			->getCollection();

		$eFarmOtf = \farm\FarmLib::getById(\association\AssociationSetting::FARM);

		foreach($cHistory as $eHistory) {

			if(History::model()->update($eHistory, ['documentStatus' => History::GENERATING]) === 0) {
				continue;
			}

			History::model()->beginTransaction();

				HistoryLib::generateDocument($eHistory);

			History::model()->commit();

		}
	}

	public static function createPaymentByAdmin(): void {

		$eFarm = \farm\FarmLib::getById(POST('farm'));
		$eMethod = \payment\MethodLib::getById(POST('method'));
		$type = POST('type');
		$amount = POST('amount', 'int');

		History::model()->beginTransaction();

			$eCustomer = self::getCustomerByFarm($eFarm, $eMethod);

			$_POST['terms'] = TRUE;
			$eHistory = self::createOrUpdateHistory($eFarm, $eCustomer, $type);

			$eSale = self::createOrUpdateSale($eFarm, $eCustomer, $type, $amount);

			History::model()->update($eHistory, ['sale' => $eSale]);

			\association\MembershipLib::paymentSucceed($eSale);

		History::model()->commit();

	}

	public static function createPayment(\farm\Farm $eFarm, string $type, int $amount, \payment\Method $eMethod): string {

		History::model()->beginTransaction();

			$eCustomer = self::getCustomerByFarm($eFarm, $eMethod);

			$eHistory = self::createOrUpdateHistory($eFarm, $eCustomer, $type);

			$eSale = self::createOrUpdateSale($eFarm, $eCustomer, $type, $amount);
			$eSale['customer'] = $eCustomer;

			History::model()->update($eHistory, ['sale' => $eSale]);

			$url = self::createStripePayment($eFarm, $eSale, $eMethod, $type);

		History::model()->commit();

		return $url;

	}
	public static function fail(\selling\Sale $eSale): void {

		History::model()
			->whereCustomer($eSale['customer'])
			->whereStatus(History::PROCESSING)
			->update(['status' => History::INVALID, 'paymentStatus' => History::FAILURE]);

	}

	public static function paymentSucceed(\selling\Sale $eSale): void {

		History::model()->beginTransaction();

		\selling\SaleLib::updatePreparationStatus($eSale, \selling\Sale::DELIVERED);

		$eHistory = History::model()
			->select(History::getSelection())
			->whereSale($eSale)
			->wherePaymentStatus(History::INITIALIZED)
			->whereStatus(History::PROCESSING)
			->get();

		if($eHistory->empty()) {
			return;
		}

		$eFarm = $eHistory['farm'];

		if($eHistory['type'] === History::MEMBERSHIP) {

			$eFarm['membership'] = TRUE;
			\farm\FarmLib::update($eFarm, ['membership']);

		}

		$newValues = [
			'customer' => $eSale['customer'],
			'paymentStatus' => $eHistory['paymentStatus'] !== NULL ? History::SUCCESS : NULL,
			'status' => History::VALID,
			'sale' => $eSale,
			'paidAt' => new \Sql('NOW()'),
			'documentStatus' => History::WAITING,
		];
		History::model()->update($eHistory, $newValues);

		// Promo adhésion 2025-2026
		// Promotion : toute adhésion en 2025 donne droit à une adhésion pour 2026
		if($eHistory['type'] === History::MEMBERSHIP and $eHistory['membership'] === 2025) {

			$eHistory2026 = new History([
				'farm' => $eFarm,
				'customer' => $eSale['customer'],
				'membership' => 2026,
				'type' => History::MEMBERSHIP,
				'status' => History::VALID,
				'amount' => 0,
			]);

			History::model()->insert($eHistory2026);

		}

		History::model()->commit();

		\Cache::redis()->delete('membership-count');

	}

	public static function paymentFailed(\selling\Sale $eSale): void {

		HistoryLib::updateBySale($eSale, [
			'paymentStatus' => History::FAILURE,
			'status' => History::INVALID
		]);

	}

	private static function getMembershipYear(\farm\Farm $eFarm, string $type): ?int {

		if($type === History::MEMBERSHIP) {

			$eHistoryLast = History::model()
				->select(['year' => new \Sql('MAX(membership)', 'int')])
				->whereFarm($eFarm)
				->whereType(History::MEMBERSHIP)
				->whereStatus(History::VALID)
				->get();

			if($eHistoryLast->empty() or $eHistoryLast['year'] === NULL) {
				return (int)date('Y');
			}

			return $eHistoryLast['year'] + 1;

		}

		return NULL;

	}

	private static function createOrUpdateSale(\farm\Farm $eFarm, \selling\Customer $eCustomer, string $type, int $amount): \selling\Sale {

		$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);

		$eSale = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereFarm($eFarmOtf)
			->whereCustomer($eCustomer)
			->wherePreparationStatus(\selling\Sale::DRAFT)
			->get();

		$eProduct = \selling\Product::model()
			->select(\selling\Product::getSelection())
			->whereName($type === History::MEMBERSHIP ? 'Adhésion' : 'Don')
			->get();

		$eItem = new \selling\Item([
			'farm' => $eFarmOtf,
			'name' => self::getProductName($type, self::getMembershipYear($eFarm, $type)),
			'product' => $eProduct,
			'unitPrice' => $amount,
			'unitPriceInitial' => NULL,
			'number' => 1,
			'quality' => \selling\Item::NO,
			'locked' => \selling\Item::PRICE,
			'packaging' => NULL,
		]);

		if($eSale->empty()) {

			// Création d'une vente et de l'item
			$eSale = new \selling\Sale([
				'farm' => $eFarmOtf,
				'customer'=> $eCustomer,
				'profile' => \selling\Sale::SALE,
			]);

			$fw = new \FailWatch();

			$properties = \selling\SaleLib::getPropertiesCreate()($eSale);
			$eSale->build($properties, [
				'customer' => $eCustomer['id'],
				'deliveredAt' => date('Y-m-d'),
				'preparationStatus' => \selling\Sale::DRAFT,
			], new \Properties('create'));

			$eItem['sale'] = $eSale;
			$eSale['cItemCreate'] = new \Collection([$eItem]);

			$fw->validate();

			\selling\SaleLib::create($eSale);

		} else {

			$eItem['sale'] = $eSale;
			\selling\ItemLib::createCollection($eSale, new \Collection([$eItem]), TRUE);

		}

		$eSale['cItem'] = \selling\Item::model()
			->select(\selling\Item::getSelection() + [
				'customer' => ['type', 'name', 'invoiceEmail']
			])
			->whereSale($eSale)
			->getCollection();

		return $eSale;

	}

	private static function createStripePayment(\farm\Farm $eFarm, \selling\Sale $eSale, \payment\Method $eMethod, string $type): string {

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
					'name' => $eSale['cItem']->first()['name'],
				],
				'unit_amount' => ($eSale['priceIncludingVat'] * 100),
			]
		];

		$successUrl = AssociationUi::confirmationUrl($eFarm, $eSale, $type, POST('from'));
		$cancelUrl = $eFarm->empty() ? POST('from') : AssociationUi::url($eFarm);

		$arguments = [
			'payment_intent_data' => [
				'metadata' => ['source' => 'otf', 'type' => 'association', 'membershipType' => $type, 'userId' => \user\ConnectionLib::getOnline()['id'] ?? NULL]
			],
			'expires_at' => time() + 60 * 45,
			'client_reference_id' => $eSale['customer']['id'],
			'line_items' => $items,
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments);

		$ePayment = new \selling\Payment([
			'sale' => $eSale,
			'amountIncludingVat' => $eSale['priceIncludingVat'],
			'method' => $eMethod,
			'status' => \selling\Payment::NOT_PAID,
			'onlineCheckoutId' => $stripeSession['id']
		]);

		\selling\PaymentTransactionLib::replace($eSale, new \Collection([$ePayment]));

		return $stripeSession['url'];

	}

	private static function getCustomerByFarm(\farm\Farm $eFarm, \payment\Method $ePaymentMethod): \selling\Customer {

		$eFarmOtf = \farm\FarmLib::getById(AssociationSetting::FARM);
		$eUserConnected = \user\ConnectionLib::getOnline();

		if($eFarm->notEmpty()) {

			$eFarm->expects(['id']);

			$eUser = $eUserConnected;

			$eCustomer = History::model()
				->select('customer')
				->whereFarm($eFarm)
				->getValue('customer', new \selling\Customer());

		} else {

			$eUser = new \user\User(['visibility' => \user\User::PUBLIC]);

			$fw = new \FailWatch();

			if(\Filter::check('email', $_POST['email'])) {
				$eUser['email'] = $_POST['email'];
			} else {
				\user\User::fail('email.check');
			}

			$eUser->build(['firstName', 'lastName', 'phone', 'invoiceStreet1', 'invoiceStreet2', 'invoicePostcode', 'invoiceCity', 'invoiceCountry'], $_POST);

			if($eUser['invoiceStreet1'] === NULL and $eUser['invoiceStreet2'] === NULL and $eUser['invoicePostcode'] === NULL and $eUser['invoiceCity'] === NULL) {
				\user\User::fail('invoiceAddress.check');
			}
			$fw->validate();

			$eCustomer = \selling\Customer::model()
				->select(\selling\Customer::getSelection())
				->whereInvoiceEmail($eUser['email'])
				->whereFarm($eFarmOtf)
				->whereFirstName($eUser['firstName'])
				->whereLastName($eUser['lastName'])
				->wherePhone($eUser['phone'])
				->whereInvoiceStreet1($eUser['invoiceStreet1'])
				->whereInvoiceStreet2($eUser['invoiceStreet2'])
				->whereInvoicePostcode($eUser['invoicePostcode'])
				->whereInvoiceCity($eUser['invoiceCity'])
				->sort(['createdAt' => SORT_DESC])
				->get();

		}

		// Création du customer
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
					'destination' => NULL,
					'name' => $eFarm['name'],
					'commercialName' => $eFarm['legalName'] ?? $eFarm['name'],
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
					'invoiceEmail' => $eUser['email'],
				]);

				$eUser->copyInvoiceAddress($eCustomer);

			}

			\selling\CustomerLib::create($eCustomer);

		}

		return $eCustomer;

	}

	private static function createOrUpdateHistory(\farm\Farm $eFarm, \selling\Customer $eCustomer, string $type): History {

		$properties = ['type', 'amount', 'terms'];

		$eHistory = new History(['farm' => $eFarm]);
		$eHistory->build($properties, $_POST + ['type' => $type]);

		$membershipYear = self::getMembershipYear($eFarm, $type);

		$eHistoryDb = History::model()
		  ->select(History::getSelection())
		  ->whereCustomer($eCustomer)
		  ->whereType($type)
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

			return $eHistory;

		}

		// On fait expirer les sessions précédentes avant de repartir sur celle ci (cas de plusieurs onglets : le dernier est le seul valable)
		if($eHistoryDb['onlineCheckoutId'] !== NULL) {
			$eStripeFarm = self::getAssociationStripeFarm();
			try {
				\payment\StripeLib::expiresCheckoutSession($eStripeFarm, $eHistoryDb['onlineCheckoutId']);
			} catch(\Exception) {}
		}

		History::model()->update(
			$eHistoryDb, [
				'amount' => $eHistory['amount'],
				'paymentStatus' => History::INITIALIZED,
				'status' => History::PROCESSING,
				'updatedAt' => new \Sql('NOW()'),
				'onlineCheckoutId' => NULL,
			]
		);

		// On re-récupère l'entrée à jour
		return History::model()
			->select(History::getSelection() + ['customer' => ['id', 'invoiceEmail']])
			->whereCustomer($eCustomer)
			->whereType($eHistory['type'])
			->whereMembership($membershipYear)
			->wherePaymentStatus(History::INITIALIZED)
			->get();

	}

	private static function getProductName(string $type, ?int $year) {

		return match($type) {
			History::MEMBERSHIP => new AssociationUi()->getMembershipProductName($year),
			History::DONATION => new AssociationUi()->getProductDonationName(),
		};

	}

}
