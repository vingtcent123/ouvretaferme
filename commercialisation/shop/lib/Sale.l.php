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

	public static function getByCustomersForDate(Shop $eShop, Date $eDate, \Collection $cCustomer, ?array $sales = NULL): \Collection {

		if($cCustomer->empty()) {
			return new \Collection();
		}

		$statuses = [\selling\Sale::BASKET, \selling\Sale::CONFIRMED, \selling\Sale::PREPARED, \selling\Sale::DELIVERED];

		if($eDate['deliveryDate'] === NULL) {

			if($sales === NULL) {
				$statuses = [\selling\Sale::BASKET];
			} else {
				\selling\Sale::model()->whereId('IN', $sales);
			}

		}

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereShop($eShop)
			->whereShopDate($eDate)
			->whereCustomer('IN', $cCustomer)
			->wherePreparationStatus('IN', $statuses)
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
		$ccItem = self::buildItems($cFarm, $eSaleReference['basket'], $total);

		self::applyShopOrderMin($eSaleReference, $total);
		self::applyShopShipping($eSaleReference, $total);

		\selling\Sale::model()->beginTransaction();

		// Création du client sur la ferme à l'origine de la boutique partagée
		if($eShop->isShared()) {

			$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eShop['farm'], autoCreate: TRUE);

			$eSaleReference['customer'] = $eCustomer;
			$eSaleReference['shopSharedCustomer'] = $eCustomer;

		}

		$cSale = new \Collection();

		foreach($ccItem as $farm => $cItem) {

			$eFarm = $cFarm[$farm];
			$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eFarm, autoCreate: TRUE);

			$eSale = (clone $eSaleReference)->merge([
				'farm' => $eFarm,
				'customer' => $eCustomer,
				'discount' => $discounts[$farm] ?? 0,
				'hasVat' => \farm\ConfigurationLib::getByFarm($eFarm)['hasVat'],
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

			$cSale[] = $eSale;

		}

		if($eShop->isShared()) {
			$cItemLinearized = $ccItem->linearize();
			$group = TRUE;
			self::notify('saleConfirmed', $eSaleReference, $cItemLinearized, $group);
		}

		\selling\Sale::model()->commit();

		if($eShop['hasPayment'] === FALSE) {
			return ShopUi::confirmationUrl($eShop, $eDate, $cSale);
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

			} else {

				if($eSale['customer']['defaultPaymentMethod']->notEmpty()) {
					$eMethod = $eSale['customer']['defaultPaymentMethod'];
				} else {
					$eMethod = $eShop['paymentMethod'];
				}

			}

			$eMethod = \payment\MethodLib::getById($eMethod);

			self::createDirectPayment($eMethod, $eSale);

		}

	}

	public static function buildReference(\selling\Sale $eSaleReference, \user\User $eUser, array &$properties = []): void {

		$referenceDate = $eSaleReference['shopDate']['deliveryDate'] ?? currentDate();

		$eSaleReference->merge([
			'profile' => \selling\Sale::SALE,
			'type' => $eSaleReference['shopDate']['type'],
			'deliveredAt' => $referenceDate,
			'shopPoint' => PointLib::getById($eSaleReference['shopPoint'])
		]);

		$eSaleReference['taxes'] = $eSaleReference->getTaxesFromType();

		if(
			$eSaleReference['shopPoint']->notEmpty() and
			$eSaleReference['shopPoint']['type'] === Point::HOME
		) {
			$eUser->copyDeliveryAddress($eSaleReference, $properties);
		}

	}

	public static function buildItems(\Collection $cFarm, array $basket, float &$total): \Collection {

		$ccItem = new \Collection()->setDepth(2);

		foreach($basket as ['product' => $eProduct, 'number' => $number]) {

			$eProductSelling = $eProduct['product'];
			$eFarm = $cFarm[$eProductSelling['farm']['id']];

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
				'vatRate' => \selling\SellingSetting::getVatRate($eFarm, $eProductSelling['vat']),
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
			$eSale['cPayment']->contains(fn($ePayment) => (
				$ePayment['method']['fqn'] === \payment\MethodLib::ONLINE_CARD and
				$ePayment['status'] !== \selling\Payment::FAILED
			)) === FALSE
		);

	}

	public static function changePaymentForShop(\selling\Sale $eSale): void {

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
		$ccItem = self::buildItems($cFarm, $eSaleReference['basket'], $total);

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
						] + $eSaleReference->extracts($properties));

						$cItem->setColumn('sale', $eSaleExisting);
						$cItem->setColumn('customer', $eSaleExisting['customer']);

						\selling\SaleLib::update($eSaleExisting, $properties);
						\selling\ItemLib::createCollection($eSaleExisting, $cItem, replace: TRUE);

						\selling\HistoryLib::createBySale($eSaleExisting, 'sale-updated-customer');

					} else {

						$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eFarm, autoCreate: TRUE);

						$eSaleNew = (clone $eSaleReference)->merge([
							'id' => NULL,
							'farm' => $eFarm,
							'customer' => $eCustomer,
							'discount' => $discounts[$eFarm['id']] ?? 0,
							'hasVat' => \farm\ConfigurationLib::getByFarm($eFarm)['hasVat'],
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

	public static function createPayment(?string $payment, \Collection $cSale, \selling\Sale $eSaleReference): string {

		if($eSaleReference['cPayment']->notEmpty()) {

			SaleLib::changePaymentForShop($eSaleReference);

			// On annule les précédentes tentatives de paiement pour cette vente
			\selling\PaymentTransactionLib::deleteAll($eSaleReference);

		}

		// On crée le paiement
		switch($payment) {

			case \payment\MethodLib::ONLINE_CARD :
				return self::createCardPayment($eSaleReference);

			case \payment\MethodLib::TRANSFER :
				self::createDirectPayment(\payment\MethodLib::getByFqn($eSaleReference['farm'], \payment\MethodLib::TRANSFER), $eSaleReference);
				return ShopUi::confirmationUrl($eSaleReference['shop'], $eSaleReference['shopDate'], $cSale);

			default :
				self::createDirectPayment(new \payment\Method(), $eSaleReference);
				return ShopUi::confirmationUrl($eSaleReference['shop'], $eSaleReference['shopDate'], $cSale);

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

		$successUrl = ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate'], new \Collection([$eSale]));
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

		// On prolonge le délai d'expiration de la vente
		if($eSale['preparationStatus'] === \selling\Sale::BASKET) {

			$eSale['expiresAt'] = new \Sql('NOW() + INTERVAL 1 HOUR');
			\selling\SaleLib::update($eSale, ['expiresAt']);

		}

		$eMethod = \payment\MethodLib::getByFqn($eSale['farm'], \payment\MethodLib::ONLINE_CARD);


		$ePayment = new \selling\Payment([
			'method' => $eMethod,
			'status' => \selling\Payment::NOT_PAID,
			'onlineCheckoutId' => $stripeSession['id']
		]);

		\selling\PaymentTransactionLib::createForTransaction($eSale, $ePayment);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-initiated', 'Stripe checkout id #'.$stripeSession['id'], ePayment: $ePayment);

		\selling\Sale::model()->commit();

		return $stripeSession['url'];

	}

	public static function createDirectPayment(\payment\Method $eMethod, \selling\Sale $eSale): void {

		$eSale->expects([
			'farm',
			'shopDate',
			'shop' => [
				'farm' => ['name']
			],
			'customer'
		]);


		\selling\Sale::model()->beginTransaction();

			$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
			\selling\SaleLib::update($eSale, ['preparationStatus']);

			if($eMethod->empty()) {
				\selling\PaymentTransactionLib::deleteAll($eSale);
			} else {

				$cPayment = new \Collection([
					new \selling\Payment([
						'method' => $eMethod,
						'amountIncludingVat' => NULL,
						'status' => \selling\Payment::NOT_PAID
					])
				]);

				\selling\PaymentTransactionLib::replace($eSale, $cPayment);

			}


			// On re-récupère la liste de moyens de paiement à jour (pour le notify)
			$eSale['cPayment'] = \selling\PaymentTransactionLib::getAll($eSale);

			$group = FALSE;
			self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['cItem'], $group);

		\selling\Sale::model()->commit();

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

		$hasFailed = \selling\PaymentOnlineLib::failByPaymentIntentId($eSale, $object['id']);

		if($hasFailed) {

			\selling\HistoryLib::createBySale($eSale, 'shop-payment-failed', 'Stripe event id #'.$object['id'].' (event type '.$event['type'].')');
			self::notify('saleFailed', $eSale);

		}

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

		\selling\Sale::model()->beginTransaction();

			$hasSucceeded = \selling\PaymentOnlineLib::payByPaymentIntentId($eSale, $object['id'], $eSale['priceIncludingVat']);

			if($hasSucceeded) {
				$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
				\selling\SaleLib::update($eSale, ['preparationStatus']);
			}

		\selling\Sale::model()->commit();

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-succeeded', 'Stripe event #'.$object['id']);

		// Récupération des données actualisées
		$cItem = \selling\SaleLib::getItems($eSale);
		$eSale['cPayment'] = \selling\PaymentTransactionLib::getAll($eSale);

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
