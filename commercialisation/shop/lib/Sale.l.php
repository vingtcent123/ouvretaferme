<?php
namespace shop;

/**
 * Shop sales management
 */
class SaleLib {

	use \Notifiable;

	/**
	 * Récupère la liste des produits déjà réservés / vendus pour une date donnée.
	 *
	 * @param Date $eDate
	 * @return \Collection Collection de produits / quantités vendues
	 */
	public static function getProductsByDate(Date $eDate, \Collection $cSaleExclude = new \Collection()): \Collection {

		if($cSaleExclude->notEmpty()) {
			\selling\Sale::model()->whereId('NOT IN', $cSaleExclude);
		}

		$cSale = \selling\Sale::model()
			->select(['id'])
			->whereShopDate($eDate)
			->wherePreparationStatus('NOT IN', [\selling\Sale::CANCELED, \selling\Sale::EXPIRED])
			->getCollection();

		return \selling\Item::model()
			->select([
				'product',
				'quantity'=> new \Sql('SUM(number)', 'float'),
			])
			->whereSale('in', $cSale)
			->whereIngredientOf(NULL)
			->group('product')
			->getCollection(NULL, NULL, 'product');

	}

	public static function getDiscounts(\Collection $cSale, \Collection $cCustomer): array {

		$farms = [];

		foreach($cSale as $eSale) {
			$farms += [
				$eSale['farm']['id'] => $eSale['discount']
			];
		}

		foreach($cCustomer as $eCustomer) {
			if($eCustomer->notEmpty()) {
				$farms += [
					$eCustomer['farm']['id'] => $eCustomer['discount']
				];
			}
		}

		return $farms;

	}

	public static function getCustomersByShop(Shop $eShop, \selling\Customer $eCustomer): \Collection {

		if($eShop['shared']) {

			$eShop->expects(['cShare']);

			$cCustomer = \selling\Customer::model()
				->select('id')
				->whereUser($eCustomer['user'])
				->whereFarm('IN', $eShop['cShare']->getColumn('farm'))
				->getCollection();

			return $cCustomer;

		} else {
			return new \Collection([$eCustomer]);
		}

	}

	public static function getByCustomersForDate(Shop $eShop, Date $eDate, \Collection $cCustomer): \Collection {

		if($cCustomer->empty()) {
			return new \Collection();
		}

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereShop($eShop)
			->whereShopDate($eDate)
			->whereCustomer('IN', $cCustomer)
			->wherePreparationStatus('NOT IN', [\selling\Sale::CANCELED, \selling\Sale::EXPIRED, \selling\Sale::DRAFT])
			->getCollection(index: 'farm');

	}

	public static function hasExpired(Shop $eShop, Date $eDate, \Collection $cCustomer): bool {

		if($cCustomer->empty()) {
			return FALSE;
		}

		return \selling\Sale::model()
			->whereShop($eShop)
			->whereShopDate($eDate)
			->whereCustomer('IN', $cCustomer)
			->wherePreparationStatus(\selling\Sale::EXPIRED)
			->exists();

	}

	public static function createForShop(\selling\Sale $eSaleReference, \user\User $eUser, array $discounts): string {

		$eSaleReference->expects([
			'shop' => [
				'cFarm',
				'farm' => ['name'],
				'shared',
				'hasPayment'
			],
			'shopDate', 'shopPoint',
			'basket'
		]);


		$eShop = $eSaleReference['shop'];
		$eDate = $eSaleReference['shopDate'];
		$cFarm = $eShop['cFarm'];

		$eSaleReference['preparationStatus'] = \selling\Sale::BASKET;

		self::buildReference($eSaleReference, $eUser);

		// Création des produits
		$total = 0.0;
		$ccItem = self::buildItems($eSaleReference['basket'], $total);

		self::applyShopOrderMin($eSaleReference, $total);
		self::applyShopShipping($eSaleReference, $total);

		\selling\Sale::model()->beginTransaction();

		// Création du client sur la ferme à l'origine de la boutique partagée
		if($eShop->isShared()) {
			$eSaleReference['customer'] = \selling\CustomerLib::getByUserAndFarm($eUser, $eShop['farm'], autoCreate: TRUE, autoCreateType: $eSaleReference['type']);
		}

		foreach($ccItem as $farm => $cItem) {

			$eFarm = $cFarm[$farm];
			$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eFarm, autoCreate: TRUE, autoCreateType: $eSaleReference['type']);

			$eSale = (clone $eSaleReference)->merge([
				'farm' => $eFarm,
				'customer' => $eCustomer,
				'discount' => $discounts[$farm] ?? 0,
				'hasVat' => \selling\ConfigurationLib::getByFarm($eFarm)['hasVat'],
				'cItem' => $cItem
			]);

			$cItem->setColumn('sale', $eSale);
			$cItem->setColumn('customer', $eCustomer);

			\selling\SaleLib::create($eSale);
			\selling\ItemLib::createCollection($eSale, $cItem);

			// Récupération du montant de la commande à jour après l'ajout des produits
			\selling\Sale::model()
				->select(['priceIncludingVat', 'priceExcludingVat', 'vat'])
				->get($eSale);

			self::autoCreatePayment($eFarm, $eShop, $eSale);

		}

		if($eShop->isShared()) {
			$cItemLinearized = $ccItem->linearize();
			$group = TRUE;
			self::notify('saleConfirmed', $eSaleReference, $cItemLinearized, $group);
		}

		\selling\Sale::model()->commit();

		if($eShop['hasPayment'] === FALSE) {
			return ShopUi::confirmationUrl($eShop, $eDate);
		} else {
			return ShopUi::paymentUrl($eShop, $eDate);
		}

	}

	public static function autoCreatePayment(\farm\Farm $eFarm, Shop $eShop, \selling\Sale $eSale): void {

		$eSale->expects([
			'customer' => ['defaultPaymentMethod']
		]);

		if($eShop['hasPayment'] === FALSE) {

			if($eShop->isShared()) {

				$eShare = $eShop['cShare'][$eFarm['id']];

				if($eSale['customer']['defaultPaymentMethod']->notEmpty()) {
					$eMethod = $eSale['customer']['defaultPaymentMethod'];
				} else {
					$eMethod = $eShare['paymentMethod'];
				}

				self::createDirectPayment($eMethod, $eSale);

			} else {

				if($eSale['customer']['defaultPaymentMethod']->notEmpty()) {
					$eMethod = $eSale['customer']['defaultPaymentMethod'];
				} else {
					$eMethod = $eShop['paymentMethod'];
				}

				self::createDirectPayment($eMethod, $eSale);

			}

		}

	}

	public static function buildReference(\selling\Sale $eSaleReference, \user\User $eUser, array &$properties = []): void {

		$eSaleReference->merge([
			'profile' => \selling\Sale::SALE,
			'type' => $eSaleReference['shopDate']['type'],
			'deliveredAt' => $eSaleReference['shopDate']['deliveryDate'],
			'shopPoint' => PointLib::getById($eSaleReference['shopPoint'])
		]);

		$eSaleReference['taxes'] = $eSaleReference->getTaxesFromType();

		if(
			$eSaleReference['shopPoint']->notEmpty() and
			$eSaleReference['shopPoint']['type'] === Point::HOME
		) {
			$eSaleReference->copyAddressFromUser($eUser, $properties);
		}

	}

	public static function buildItems(array $basket, float &$total): \Collection {

		$ccItem = new \Collection()->setDepth(2);

		foreach($basket as ['product' => $eProduct, 'number' => $number]) {

			$eProductSelling = $eProduct['product'];
			$eFarm = $eProductSelling['farm'];

			$eItem = new \selling\Item([
				'farm' => $eFarm,
				'shopProduct' => $eProduct,
				'product' => $eProductSelling,
				'name' => $eProductSelling->getName(),
				'quality' => $eProductSelling['quality'],
				'packaging' => $eProduct['packaging'],
				'locked' => \selling\Item::PRICE,
				'unit' => $eProductSelling['unit'],
				'unitPrice' => $eProduct['price'],
				'unitPriceInitial' => $eProduct['priceInitial'],
				'number' => $number,
				'vatRate' => \selling\SellingSetting::VAT_RATES[$eProductSelling['vat']],
			]);

			$total += $number * ($eProduct['packaging'] ?? 1) * $eProduct['price'];

			$ccItem[$eFarm['id']] ??= new \Collection();
			$ccItem[$eFarm['id']]->append($eItem);

		}

		$total = round($total, 2);

		return $ccItem;

	}

	private static function applyShopOrderMin(\selling\Sale $eSale, float $price): void {

		$orderMin = $eSale['shopPoint']['orderMin'] ?? $eSale['shop']['orderMin'];

		if($orderMin > 0 and $price < $orderMin) {
			throw new \FailAction('selling\Sale::orderMin.check');
		}

	}

	private static function applyShopShipping(\selling\Sale $eSale, float $price) {

		$shipping = $eSale['shopPoint']['shipping'] ?? $eSale['shop']['shipping'];
		$shippingUntil = $eSale['shopPoint']['shippingUntil'] ?? $eSale['shop']['shippingUntil'];

		if(
			$shipping > 0 and
			($shippingUntil === NULL or $price < $shippingUntil)
		) {
			$eSale['shipping'] = $shipping;
		} else {
			$eSale['shipping'] = NULL;
		}

	}

	public static function canUpdateForShop(\selling\Sale $eSale): bool {

		return (
			$eSale['cPayment']->empty() or
			$eSale['cPayment']->contains(fn($ePayment) => ($ePayment['method']['fqn'] === \payment\MethodLib::ONLINE_CARD and in_array($ePayment['onlineStatus'], [\selling\Payment::EXPIRED, \selling\Payment::FAILURE]) === FALSE)) === FALSE
		);

	}

	public static function changePaymentForShop(\selling\Sale $eSale): void {

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::BASKET;

		\selling\SaleLib::update($eSale, ['preparationStatus']);

		\selling\HistoryLib::createBySale($eSale, 'sale-update-payment');

	}

	public static function updateForShop(\selling\Sale $eSaleReference, \Collection $cSaleExisting, \user\User $eUser, array $discounts): ?string {

		$eSaleReference->expects(['basket']);

		if(self::canUpdateForShop($eSaleReference) === FALSE) {
			throw new \Exception('Payment security');
		}

		$eShop = $eSaleReference['shop'];
		$eDate = $eSaleReference['shopDate'];
		$cFarm = $eShop['cFarm'];

		$properties = ['preparationStatus', 'shopPoint', 'shopUpdated', 'shipping'];

		if($eShop['comment']) {
			$properties[] = 'shopComment';
		}

		self::buildReference($eSaleReference, $eUser, $properties);

		// Ajout des produits
		$total = 0.0;
		$ccItem = self::buildItems($eSaleReference['basket'], $total);

		self::applyShopOrderMin($eSaleReference, $total);
		self::applyShopShipping($eSaleReference, $total);

		\selling\Sale::model()->beginTransaction();

			foreach($cFarm as $eFarm) {

				$eSaleExisting = $cSaleExisting[$eFarm['id']] ?? new \selling\Sale();

				if($ccItem->offsetExists($eFarm['id'])) {

					$cItem = $ccItem[$eFarm['id']];

					if($eSaleExisting->notEmpty()) {

						$eSaleExisting->merge([
							'shopUpdated' => TRUE,
							'cItem' => $cItem,
							'oldPreparationStatus' => $eSaleExisting['preparationStatus'],
						] + $eSaleReference->extracts($properties));

						$cItem->setColumn('sale', $eSaleExisting);
						$cItem->setColumn('customer', $eSaleExisting['customer']);

						\selling\SaleLib::update($eSaleExisting, $properties);
						\selling\ItemLib::createCollection($eSaleExisting, $cItem, replace: TRUE);

						self::autoCreatePayment($eFarm, $eShop, $eSaleExisting);

					} else {

						$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eFarm, autoCreate: TRUE, autoCreateType: $eSaleReference['type']);

						$eSaleNew = (clone $eSaleReference)->merge([
							'id' => NULL,
							'farm' => $eFarm,
							'customer' => $eCustomer,
							'discount' => $discounts[$eFarm['id']] ?? 0,
							'hasVat' => \selling\ConfigurationLib::getByFarm($eFarm)['hasVat'],
							'cItem' => $cItem
						]);

						$cItem->setColumn('sale', $eSaleNew);
						$cItem->setColumn('customer', $eCustomer);

						\selling\SaleLib::create($eSaleNew);
						\selling\ItemLib::createCollection($eSaleNew, $cItem);

						self::autoCreatePayment($eFarm, $eShop, $eSaleNew);

					}

				} else {

					if($eSaleExisting->notEmpty()) {

						// Annuler la vente
						\selling\SaleLib::updatePreparationStatusCollection(new \Collection([$eSaleExisting]), \selling\Sale::CANCELED);

					}

				}

			}

		\selling\Sale::model()->commit();

		$cItemLinearized = $ccItem->linearize();
		$group = $eShop->isShared();
		self::notify('saleUpdated', $eSaleReference, $cItemLinearized, $group);

		return ShopUi::confirmationUrl($eShop, $eDate);

	}

	public static function createPayment(?string $payment, \selling\Sale $eSale): string {

		if($eSale['cPayment']->notEmpty()) {

			SaleLib::changePaymentForShop($eSale);

			// On annule les précédentes tentatives de paiement pour cette vente
			\selling\PaymentLib::expiresPaymentSessions($eSale);

		}

		// On crée le paiement
		return match($payment) {
			\payment\MethodLib::ONLINE_CARD => self::createCardPayment($eSale),
			\payment\MethodLib::TRANSFER => self::createDirectPayment(\payment\MethodLib::getByFqn(\payment\MethodLib::TRANSFER), $eSale),
			default => self::createDirectPayment(new \payment\Method(), $eSale),
		};

	}

	public static function createCardPayment(\selling\Sale $eSale): string {

		$eSale->expects(['farm', 'shopDate', 'customer', 'cItem']);

		$eCustomer = $eSale['customer'];

		$eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);

		if($eStripeFarm->empty()) {
			throw new \Exception('Missing stripe configuration');
		}

		$items = [];
		$items[] = [
			'quantity' => 1,
			'price_data' => [
				'currency' => 'EUR',
				'product_data' => [
					'name' => \selling\ItemUi::getNumber($eSale['cItem']),
				],
				'unit_amount' => ($eSale['priceIncludingVat'] * 100), // in cents, how much to charge
			]
		];

		$successUrl = ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate']);
		$cancelUrl = ShopUi::paymentUrl($eSale['shop'], $eSale['shopDate']);

		$arguments = [
			'payment_intent_data' => [
				'metadata' => ['source' => 'otf']
			],
			'expires_at' => time() + 60 * 45,
			'client_reference_id' => $eCustomer['id'],
			'line_items' => $items,
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments);

		\selling\Sale::model()->beginTransaction();

		$properties = ['paymentStatus', 'onlinePaymentStatus'];

		$eSale['paymentStatus'] = \selling\Sale::NOT_PAID;
		$eSale['onlinePaymentStatus'] = \selling\Sale::INITIALIZED;

		// On prolonge le délai d'expiration de la vente
		if($eSale['preparationStatus'] === \selling\Sale::BASKET) {
			$eSale['expiresAt'] = new \Sql('NOW() + INTERVAL 1 HOUR');
			$properties[] = 'expiresAt';
		}

		$eMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		\selling\SaleLib::update($eSale, $properties);
		$ePayment = \selling\PaymentLib::createBySale($eSale, $eMethod, $stripeSession['id']);
		\selling\HistoryLib::createBySale($eSale, 'shop-payment-initiated', 'Stripe checkout id #'.$stripeSession['id'], ePayment: $ePayment);

		\selling\Sale::model()->commit();

		return $stripeSession['url'];

	}

	public static function createDirectPayment(\payment\Method $eMethod, \selling\Sale $eSale): string {

		$eSale->expects([
			'farm',
			'shopDate',
			'shop' => [
				'farm' => ['name']
			],
			'customer'
		]);

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;

		if($eMethod->empty()) {
			$eSale['paymentStatus'] = NULL;
		} else {
			$eSale['paymentStatus'] = \selling\Sale::NOT_PAID;
		}

		\selling\Sale::model()->beginTransaction();

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentStatus']);

		\selling\PaymentLib::deleteBySale($eSale);
		\selling\PaymentLib::putBySale($eSale, $eMethod);

		// On re-récupère la liste de moyens de paiement à jour (pour le notify)
		$eSale['cPayment'] = \selling\PaymentLib::getBySale($eSale);

		$group = FALSE;
		self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['cItem'], $group);

		\selling\Sale::model()->commit();

		return ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate']);

	}

	public static function cancelForShop(Shop $eShop, Date $eDate, \selling\Customer $eCustomer) {

		\selling\Sale::model()->beginTransaction();

			$cCustomer = \shop\SaleLib::getCustomersByShop($eShop, $eCustomer);
			$cSale = \shop\SaleLib::getByCustomersForDate($eShop, $eDate, $cCustomer)->validate('acceptUpdateByCustomer');
			$cSale->setColumn('shop', $eShop);
			$cSale->setColumn('shopDate', $eDate);

			\selling\SaleLib::updatePreparationStatusCollection($cSale, \selling\Sale::CANCELED);

			foreach($cSale as $eSale) {
				$group = FALSE;
				self::notify('saleCanceled', $eSale, $group);
			}

			if($eShop->isShared()) {

				$eSaleReference = (clone $eSale)->merge([
					'customer' => $eCustomer
				]);

				$group = TRUE;
				self::notify('saleCanceled', $eSaleReference, $group);

			}

		\selling\Sale::model()->commit();

	}

	public static function paymentFailed(\selling\Sale $eSale, array $event): void {

		$object = $event['data']['object'];

		\selling\Sale::model()->beginTransaction();

		$affected = \selling\PaymentLib::updateByPaymentIntentId($object['id'], [
			'onlineStatus' => \selling\Payment::FAILURE
		]);

		if(
			$affected > 0 and
			\selling\PaymentLib::hasSuccess($eSale) === FALSE
		) {

			$newValues = [
				'paymentStatus' => \selling\Sale::NOT_PAID,
				'onlinePaymentStatus' => \selling\Sale::FAILURE,
			];

			$affected = \selling\Sale::model()
				->wherePaymentStatus('NOT IN', [\selling\Sale::PAID]) // En cas de concurrence (si le client a réussi entre temps)
				->update($eSale, $newValues);

			if($affected > 0) {

				\selling\HistoryLib::createBySale($eSale, 'shop-payment-failed', 'Stripe event id #'.$object['id'].' (event type '.$event['type'].')');

				self::notify('saleFailed', $eSale);

			}

		}

		\selling\Sale::model()->commit();

	}

	/**
	 * Validation d'un paiement par carte bancaire
	 */
	public static function paymentSucceeded(\selling\Sale $eSale, array $event): void {

		\selling\Sale::model()->beginTransaction();

		$object = $event['data']['object'];

		$amountReceived = (int)$object['amount_received'];
		$amountExpected = (int)round($eSale['priceIncludingVat'] * 100);

		if($amountReceived !== $amountExpected) {
			trigger_error('Amount received '.($object['amount_received'] / 100).' different from amount expected '.($eSale['priceIncludingVat']).' in sale #'.$eSale['id'].' (event #'.$object['id'].')', E_USER_WARNING);
			return;
		}

		\selling\PaymentLib::updateByPaymentIntentId($object['id'], [
			'onlineStatus' => \selling\Payment::SUCCESS,
			'amountIncludingVat' => $eSale['priceIncludingVat'],
		]);

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;

		$eSale['paymentStatus'] = \selling\Sale::PAID;
		$eSale['onlinePaymentStatus'] = \selling\Sale::SUCCESS;

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentStatus', 'onlinePaymentStatus']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-succeeded', 'Stripe event #'.$object['id']);

		// Récupération des données actualisées
		$cItem = \selling\SaleLib::getItems($eSale);
		$eSale['cPayment'] = \selling\PaymentLib::getBySale($eSale);

		self::notify('salePaid', $eSale, $cItem);

		\selling\Sale::model()->commit();

	}

	public static function cancelExpired(): void {

		$cSale = \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->wherePreparationStatus(\selling\Sale::BASKET)
			->whereExpiresAt('<', new \Sql('NOW()'))
			->getCollection();

		foreach($cSale as $eSale) {

			$cItem = \selling\SaleLib::getItems($eSale);

			\selling\SaleLib::updatePreparationStatus($eSale, \selling\Sale::EXPIRED);

			ProductLib::addAvailable($eSale, $cItem);

		}

	}

}
?>
