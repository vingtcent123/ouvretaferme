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
	 * @param Sale $eSaleExclude Exclure une vente du calcul des disponibilités
	 * @return \Collection Collection de produits / quantités vendues
	 */
	public static function getProductsByDate(Date $eDate, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		if($eSaleExclude->notEmpty()) {
			\selling\Sale::model()->whereId('!=', $eSaleExclude);
		}

		$cSale = \selling\Sale::model()
			->select(['id'])
			->whereShopDate($eDate)
			->wherePreparationStatus('!=', \selling\Sale::CANCELED)
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

	public static function getDiscount(Shop $eShop, \selling\Sale $eSale, \selling\Customer $eCustomer): int {

		if($eShop['shared']) {
			return 0;
		} else if($eSale->notEmpty()) {
			return $eSale['discount'];
		} else if($eCustomer->notEmpty()) {
			return $eCustomer['discount'];
		} else {
			return 0;
		}

	}

	public static function getByCustomerForDate(Date $eDate, \selling\Customer $eCustomer): \selling\Sale {

		if($eCustomer->empty()) {
			return new \selling\Sale();
		}

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection() + [
				'cItem' => \selling\Item::model()
					->select(\selling\Item::getSelection())
					->whereIngredientOf(NULL)
					->delegateCollection('sale')
			])
			->whereShopDate($eDate)
			->whereShopParent(NULL)
			->whereCustomer($eCustomer)
			->wherePreparationStatus('NOT IN', [\selling\Sale::CANCELED, \selling\Sale::DRAFT])
			->get();

	}

	public static function getConfirmedForDate(Date $eDate): \Collection {

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection() )
			->whereShopDate($eDate)
			->whereShopMaster(TRUE)
			->wherePreparationStatus(\selling\Sale::CONFIRMED)
			->getCollection();

	}

	public static function getShopCustomer(Shop $eShop, \user\User $eUser, bool $autocreate = FALSE): \selling\Customer {

		if($eUser->empty()) {
			return new \selling\Customer();
		}

		$eShop->expects(['farm']);

		$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eShop['farm']);

		if($eCustomer->empty() and $autocreate) {
			// Possible problème de DUPLICATE si le customer a été créé entre cette instruction et la précédente
			$eCustomer = \selling\CustomerLib::createFromUser($eUser, $eShop['farm'], \selling\Customer::PRIVATE);
		}

		return $eCustomer;

	}

	public static function createForShop(\selling\Sale $eSale, \user\User $eUser): string {

		$eSale->expects([
			'shop' => [
				'farm' => ['name'],
				'shared',
				'hasPayment'
			],
			'shopDate', 'shopPoint',
			'basket'
		]);

		$eCustomer = self::getShopCustomer($eSale['shop'], $eUser, autocreate: TRUE);
		$eDate = $eSale['shopDate'];

		$eSale->merge([
			'farm' => $eSale['shop']['farm'],
			'customer' => $eCustomer,
			'compositionOf' => new \selling\Product(),
			'discount' => \shop\SaleLib::getDiscount($eSale['shop'], new \selling\Sale(), $eCustomer),
			'from' => \selling\Sale::SHOP,
			'market' => FALSE,
			'marketParent' => new \selling\Sale(),
			'type' => $eDate['type'],
			'preparationStatus' => \selling\Sale::BASKET,
			'deliveredAt' => $eSale['shopDate']['deliveryDate'],
			'stats' => ($eSale['shop']['shared'] === FALSE),
			'shopMaster' => $eSale['shop']['shared'],
		]);

		$eSale['taxes'] = $eSale->getTaxesFromType();
		$eSale['hasVat'] = \selling\ConfigurationLib::getByFarm($eSale['farm'])['hasVat'];

		if(
			$eSale['shopPoint']->notEmpty() and
			$eSale['shopPoint']['type'] === Point::HOME
		) {
			$eSale->copyAddressFromUser($eUser);
		}

		// Création des produits
		$cItem = new \Collection();
		$total = 0.0;

		foreach($eSale['basket'] as ['product' => $eProduct, 'number' => $number]) {

			$eProductSelling = $eProduct['product'];

			$eItem = new \selling\Item([
				'sale' => $eSale,
				'farm' => $eProductSelling['farm'],
				'customer' => $eSale['customer'],
				'shopProduct' => $eProduct,
				'product' => $eProductSelling,
				'name' => $eProductSelling->getName(),
				'quality' => $eProductSelling['quality'],
				'packaging' => $eProduct['packaging'],
				'locked' => \selling\Item::PRICE,
				'unit' => $eProductSelling['unit'],
				'unitPrice' => $eProduct['price'],
				'number' => $number,
				'vatRate' => \Setting::get('selling\vatRates')[$eProductSelling['vat']],
			]);

			$total += $number * ($eProduct['packaging'] ?? 1) * $eProduct['price'];

			$cItem->append($eItem);

		}

		$total = round($total, 2);

		if(self::applyShopOrderMin($eSale, $total) === FALSE) {
			return new \selling\Sale();
		}

		self::applyShopShipping($eSale, $total);

		\selling\Sale::model()->beginTransaction();

		\selling\SaleLib::create($eSale);

		\selling\ItemLib::createCollection($eSale, $cItem);

		// Récupération du montant de la commande à jour après l'ajout des produits
		\selling\Sale::model()
			->select(['priceIncludingVat', 'priceExcludingVat', 'vat'])
			->get($eSale);

		\selling\Sale::model()->commit();

		$eSale['cItem'] = $cItem;

		if($eSale['shop']['hasPayment'] === FALSE) {
			return self::createDirectPayment(NULL, $eSale);
		} else {
			return \shop\ShopUi::paymentUrl($eSale['shop'], $eSale['shopDate']);
		}


	}

	private static function applyShopOrderMin(\selling\Sale $eSale, float $price): bool {

		$orderMin = $eSale['shopPoint']['orderMin'] ?? $eSale['shop']['orderMin'];

		if($orderMin > 0 and $price < $orderMin) {
			\selling\Sale::fail('orderMin.check');
			return FALSE;
		} else {
			return TRUE;
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
			$eSale['paymentMethod'] === \selling\Sale::TRANSFER or
			$eSale['paymentMethod'] === \selling\Sale::OFFLINE or
			$eSale['paymentMethod'] === NULL
		);

	}

	public static function updateForShop(\selling\Sale $eSale, Shop $eShop, \user\User $eUser): ?string {

		$eSale->expects(['basket', 'paymentMethod']);

		if(self::canUpdateForShop($eSale) === FALSE) {
			throw new \Exception('Payment security');
		}

		$properties = ['preparationStatus', 'shopPoint', 'shopUpdated', 'shipping'];

		if($eShop['comment']) {
			$properties[] = 'shopComment';
		}

		$eSale['shopPoint'] = PointLib::getById($eSale['shopPoint']);
		$eSale['shopUpdated'] = TRUE;
		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::BASKET;

		if(
			$eSale['shopPoint']->notEmpty() and
			$eSale['shopPoint']['type'] === Point::HOME
		) {
			$eSale->copyAddressFromUser($eUser, $properties);
		}

		// Ajout des produits
		$cItem = new \Collection();
		$total = 0.0;

		foreach($eSale['basket'] as ['product' => $eProduct, 'number' => $number]) {

			$eProductSelling = $eProduct['product'];

			$eItem = new \selling\Item([
				'sale' => $eSale,
				'farm' => $eProductSelling['farm'],
				'customer' => $eSale['customer'],
				'shopProduct' => $eProduct,
				'product' => $eProductSelling,
				'name' => $eProductSelling->getName(),
				'quality' => $eProductSelling['quality'],
				'packaging' => $eProduct['packaging'],
				'unit' => $eProductSelling['unit'],
				'unitPrice' => $eProduct['price'],
				'number' => $number,
				'vatRate' => \Setting::get('selling\vatRates')[$eProductSelling['vat']],
				'locked' => \selling\Item::PRICE
			]);

			$total += $number * ($eProduct['packaging'] ?? 1) * $eProduct['price'];

			$cItem->append($eItem);

		}

		if(self::applyShopOrderMin($eSale, $total) === FALSE) {
			return NULL;
		}

		self::applyShopShipping($eSale, $total);

		\selling\Sale::model()->beginTransaction();

			// Suppression des anciens items
			\selling\ItemLib::deleteCollection($eSale, $eSale['cItem']);

			// Nouveaux items pour les mails de confirmation
			$eSale['cItem'] = $cItem;

			\selling\ItemLib::createCollection($eSale, $cItem);

			\selling\SaleLib::update($eSale, $properties);

		\selling\Sale::model()->commit();

		if($eSale['shop']['hasPayment'] === FALSE) {
			return self::createDirectPayment(NULL, $eSale);
		} else {
			return \shop\ShopUi::paymentUrl($eSale['shop'], $eSale['shopDate']);
		}

	}

	public static function createPayment(string $payment, Date $eDate, \selling\Sale $eSale): string {

		return match($payment) {
			\selling\Sale::ONLINE_CARD => self::createCardPayment($eSale),
			\selling\Sale::OFFLINE => self::createDirectPayment(\selling\Sale::OFFLINE, $eSale),
			\selling\Sale::TRANSFER => self::createDirectPayment(\selling\Sale::TRANSFER, $eSale)
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
			'client_reference_id' => $eCustomer['id'],
			'line_items' => $items,
			'success_url' => $successUrl,
			'cancel_url' => $cancelUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments);

		\selling\Sale::model()->beginTransaction();

		$eSale['paymentMethod'] = \selling\Sale::ONLINE_CARD;

		\selling\SaleLib::update($eSale, ['paymentMethod']);

		$ePayment = \selling\PaymentLib::createBySale($eSale, $stripeSession['id']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-initiated', 'Stripe checkout id #'.$stripeSession['id'], ePayment: $ePayment);

		\selling\Sale::model()->commit();

		return $stripeSession['url'];

	}

	public static function createDirectPayment(?string $method, \selling\Sale $eSale): string {

		$eSale->expects(['farm', 'shopDate', 'shop', 'customer']);

		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentMethod'] = $method;

		\selling\Sale::model()->beginTransaction();

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentMethod']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-delivery');

		self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['cItem']);

		\selling\SaleLib::recalculateMaster($eSale, $eSale['cItem']);

		\selling\Sale::model()->commit();

		return ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate'], $eSale);

	}

	public static function cancel(\selling\Sale $eSale) {

		if($eSale->acceptCustomerCancel() === FALSE) {
			throw new \NotExpectedAction('Cannot cancel sale #'.$eSale['id']);
		}

		\selling\Sale::model()->beginTransaction();

			$eSale['oldStatus'] = $eSale['preparationStatus'];
			$eSale['preparationStatus'] = \selling\Sale::CANCELED;

			\selling\SaleLib::update($eSale, ['preparationStatus']);

			\selling\HistoryLib::createBySale($eSale, 'sale-canceled');

			self::notify('saleCanceled', $eSale);

		\selling\Sale::model()->commit();

	}

	public static function paymentFailed(\selling\Sale $eSale, array $event): void {

		$object = $event['data']['object'];

		\selling\Sale::model()->beginTransaction();

		$affected = \selling\PaymentLib::updateStatus($object['id'], \selling\Payment::FAILURE);

		if(
			$affected > 0 and
			\selling\PaymentLib::hasSuccess($eSale) === FALSE
		) {

			\selling\Sale::model()
				->wherePaymentStatus('NOT IN', [\selling\Sale::PAID])
				->update($eSale, [
					'paymentStatus' => \selling\Sale::FAILED
				]);

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

		\selling\PaymentLib::updateStatus($object['id'], \selling\Payment::SUCCESS);

		self::completePaid($eSale, $object['id']);

		\selling\Sale::model()->commit();

	}

	private static function completePaid(\selling\Sale $eSale, string $eventId): void {

		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentStatus'] = \selling\Sale::PAID;

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentStatus']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-succeeded', 'Stripe event #'.$eventId);

		$cItem = \selling\SaleLib::getItems($eSale);

		\selling\SaleLib::recalculateMaster($eSale, $cItem);

		self::notify('salePaid', $eSale, $cItem);

	}

}
?>