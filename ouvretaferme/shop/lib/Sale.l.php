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
	 * @param Sale $eSaleExclude Exclure une vente du calcul des stocks
	 * @return \Collection Collection de produits / quantités vendues
	 */
	public static function getProductsByDate(Date $eDate, \selling\Sale $eSaleExclude = new \selling\Sale()): \Collection {

		if($eSaleExclude->notEmpty()) {
			\selling\Sale::model()->whereId('!=', $eSaleExclude);
		}

		$cSale = \selling\Sale::model()
			->select(['id'])
			->whereShopDate($eDate)
			->whereFrom(\selling\Sale::SHOP)
			->wherePreparationStatus('!=', \selling\Sale::CANCELED)
			->getCollection();

		return \selling\Item::model()
			->select([
				'product',
				'quantity'=> new \Sql('SUM(number)', 'float'),
			])
			->whereSale('in', $cSale)
			->group('product')
			->getCollection(NULL, NULL, 'product');

	}

	public static function getDiscount(\selling\Sale $eSale, \selling\Customer $eCustomer): int {

		$discount = 0;

		if($eSale->notEmpty()) {
			$discount = $eSale['discount'];
		} else if($eCustomer->notEmpty()) {
			$discount = $eCustomer['discount'];
		}

		return $discount;

	}

	public static function getSaleForDate(Date $eDate, \selling\Customer $eCustomer): \selling\Sale {

		if($eCustomer->empty()) {
			return new \selling\Sale();
		}

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection() + [
				'cItem' => \selling\Item::model()
					->select(\selling\Item::getSelection())
					->delegateCollection('sale')
			])
			->whereShopDate($eDate)
			->whereCustomer($eCustomer)
			->wherePreparationStatus('NOT IN', [\selling\Sale::CANCELED, \selling\Sale::DRAFT])
			->get();

	}

	public static function getShopCustomer(Shop $eShop, \user\User $eUser): \selling\Customer {

		if($eUser->empty()) {
			return new \selling\Customer();
		}

		$eShop->expects(['farm']);

		$eCustomer = \selling\CustomerLib::getByUserAndFarm($eUser, $eShop['farm']);

		if($eCustomer->empty()) {
			// Possible problème de DUPLICATE si le customer a été créé entre cette instruction et la précédente
			$eCustomer = \selling\CustomerLib::createFromUser($eUser, $eShop['farm'], \selling\Customer::PRIVATE);
		}

		return $eCustomer;

	}

	public static function createForShop(\selling\Sale $eSale, \user\User $eUser): \selling\Sale {

		$eSale->expects([
			'shopDate', 'shop', 'shopPoint',
			'basket'
		]);

		$eCustomer = self::getShopCustomer($eSale['shop'], $eUser);

		$eSale->merge([
			'farm' => $eSale['shop']['farm'],
			'customer' => $eCustomer,
			'discount' => $eCustomer['discount'],
			'from' => \selling\Sale::SHOP,
			'market' => FALSE,
			'type' => \selling\Sale::PRIVATE,
			'preparationStatus' => \selling\Sale::BASKET,
			'deliveredAt' => $eSale['shopDate']['deliveryDate'],
		]);

		if($eSale['shopPoint']['type'] === Point::HOME) {
			$eSale->copyAddressFromUser($eUser);
		}

		// Création des produits
		$cItem = new \Collection();
		$price = 0.0;

		foreach($eSale['basket'] as ['product' => $eProduct, 'quantity' => $quantity]) {

			$eItem = new \selling\Item([
				'sale' => $eSale,
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer'],
				'product' => $eProduct,
				'name' => $eProduct->getName(),
				'quality' => $eProduct['quality'],
				'packaging' => NULL,
				'locked' => \selling\Item::PRICE,
				'unit' => $eProduct['unit'],
				'unitPrice' => $eProduct['privatePrice'],
				'number' => $quantity,
				'vatRate' => \Setting::get('selling\vatRates')[$eProduct['vat']],
			]);

			$price += $quantity * $eProduct['privatePrice'];

			$cItem->append($eItem);

		}

		$price = round($price, 2);

		if(self::applyShopOrderMin($eSale, $price) === FALSE) {
			return new \selling\Sale();
		}

		self::applyShopShipping($eSale, $price);

		\selling\Sale::model()->beginTransaction();

		\selling\SaleLib::create($eSale);

		\selling\ItemLib::createCollection($cItem);

		// Récupération du montant de la commande à jour après l'ajout des produits
		\selling\Sale::model()
			->select(['priceIncludingVat', 'priceExcludingVat', 'vat'])
			->get($eSale);

		\selling\Sale::model()->commit();

		$eSale['cItem'] = $cItem;

		return $eSale;

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
			$shippingUntil > 0 and
			$price < $shippingUntil
		) {
			$eSale['shipping'] = $shipping;
		} else {
			$eSale['shipping'] = NULL;
		}

	}

	public static function canUpdateForShop(\selling\Sale $eSale): bool {

		return (
			$eSale['paymentMethod'] === \selling\Sale::ONLINE_SEPA or
			$eSale['paymentMethod'] === \selling\Sale::OFFLINE or
			$eSale['paymentMethod'] === NULL
		);

	}

	public static function updateForShop(\selling\Sale $eSale, \user\User $eUser): void {

		$eSale->expects(['basket', 'paymentMethod']);

		if(self::canUpdateForShop($eSale) === FALSE) {
			throw new \Exception('Payment security');
		}

		$eSale['shopPoint'] = PointLib::getById($eSale['shopPoint']);
		$eSale['shopUpdated'] = TRUE;
		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::BASKET;

		if($eSale['shopPoint']['type'] === Point::HOME) {
			$eSale->copyAddressFromUser($eUser);
		}

		// Ajout des produits
		$cItem = new \Collection();
		$price = 0.0;

		foreach($eSale['basket'] as ['product' => $eProduct, 'quantity' => $quantity]) {

			$eItem = new \selling\Item([
				'sale' => $eSale,
				'farm' => $eSale['farm'],
				'customer' => $eSale['customer'],
				'product' => $eProduct,
				'name' => $eProduct->getName(),
				'quality' => $eProduct['quality'],
				'packaging' => NULL,
				'unit' => $eProduct['unit'],
				'unitPrice' => $eProduct['privatePrice'],
				'number' => $quantity,
				'vatRate' => \Setting::get('selling\vatRates')[$eProduct['vat']],
				'locked' => \selling\Item::PRICE
			]);

			$price += $quantity * $eProduct['privatePrice'];

			$cItem->append($eItem);

		}

		if(self::applyShopOrderMin($eSale, $price) === FALSE) {
			return;
		}

		self::applyShopShipping($eSale, $price);

		\selling\Sale::model()->beginTransaction();

		// Suppression des anciens items
		foreach($eSale['cItem'] as $eItem) {
			\selling\ItemLib::delete($eItem);
		}

		\selling\ItemLib::createCollection($cItem);

		\selling\SaleLib::update($eSale, ['preparationStatus', 'shopPoint', 'shopUpdated', 'shipping']);

		\selling\Sale::model()->commit();

	}

	public static function createPayment(string $payment, Date $eDate, \selling\Sale $eSale): string {

		return match($payment) {
			'onlineCard' => self::createCardPayment($eDate, $eSale),
			'onlineSepaDebit' => self::createSepaPayment($eDate, $eSale),
			'offline' => self::createOfflinePayment($eDate, $eSale)
		};

	}

	public static function createCardPayment(Date $eDate, \selling\Sale $eSale): string {

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

		$redirectUrl = \Lime::getProtocol().'://'.\Setting::get('shop\domain').ShopUi::dateUrl($eSale['shop'], $eSale['shopDate'], 'confirmation');

		$arguments = [
			'client_reference_id' => $eCustomer['id'],
			'line_items' => $items,
			'success_url' => $redirectUrl,
			'cancel_url' => $redirectUrl,
		];

		$stripeSession = \payment\StripeLib::createCheckoutSession($eStripeFarm, $arguments, \selling\Sale::ONLINE_CARD);

		if(isset($stripeSession['error'])) {
			throw new \Exception(var_export($stripeSession['error'], TRUE));
		}

		\selling\Sale::model()->beginTransaction();

		$eSale['paymentMethod'] = \selling\Sale::ONLINE_CARD;

		\selling\SaleLib::update($eSale, ['paymentMethod']);

		$ePayment = \selling\PaymentLib::createBySale($eSale, $stripeSession['id']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-initiated', 'Stripe checkout id #'.$stripeSession['id'], ePayment: $ePayment);

		\selling\Sale::model()->commit();

		return $stripeSession['url'];

	}

	public static function createSepaPayment(Date $eDate, \selling\Sale $eSale): string {

		$eSale->expects(['farm', 'shopDate', 'shop', 'customer']);

		$eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);

		if($eStripeFarm->empty()) {
			throw new \Exception('Missing stripe configuration');
		}

		$redirectUrl = \Lime::getProtocol().'://'.\Setting::get('shop\domain').ShopUi::dateUrl($eSale['shop'], $eSale['shopDate'], 'confirmation');

		$arguments = [
			'success_url' => $redirectUrl,
			'cancel_url' => $redirectUrl,
		];

		$eSale['paymentMethod'] = \selling\Sale::ONLINE_SEPA;

		\selling\SaleLib::update($eSale, ['paymentMethod']);

		self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['cItem']);

		$stripeSession = \payment\StripeLib::createCheckoutSessionSepa($eStripeFarm, $arguments);
		return $stripeSession['url'];

	}

	public static function createOfflinePayment(Date $eDate, \selling\Sale $eSale): string {

		$eSale->expects(['farm', 'shopDate', 'shop', 'customer']);

		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentMethod'] = \selling\Sale::OFFLINE;

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentMethod']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-delivery');

		self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['cItem']);

		return \Lime::getProtocol().'://'.\Setting::get('shop\domain').ShopUi::dateUrl($eSale['shop'], $eSale['shopDate'], 'confirmation');

	}

	public static function attachCustomer(Date $eDate, \user\User $eUser, string $sessionId): void {

		$eDate['shop']->expects(['farm']);

		$eCustomer = self::getShopCustomer($eDate['shop'], $eUser);
		\payment\StripeLib::loadSepa($eCustomer);

		$eFarm = $eDate['shop']['farm'];
		$eStripeFarm = \payment\StripeLib::getByFarm($eFarm);

		\payment\StripeLib::attachPaymentMethodToCustomerViaSession($eStripeFarm, $sessionId, $eCustomer);

	}

	public static function cancel(\selling\Sale $eSale) {

		if($eSale->canCustomerCancel() === FALSE) {
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

	public static function getById(mixed $id): \selling\Sale {

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection() + ['cItem' => \selling\Item::model()
					->select(\selling\Item::getSelection())
					->delegateCollection('sale')])
			->whereId($id)
			->get();

	}

	public static function paymentFailed(\selling\Sale $eSale, array $event): void {

		$object = $event['data']['object'];

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

	}

	/**
	 * Passe un paiement SEPA au statut en cours.
	 * Lancé par Stripe après la création du payment intent (completeCheckoutSepaDebit).
	 */
	public static function paymentProcessingSepaDebit(\selling\Sale $eSale, array $event): void {

		\selling\Sale::model()->update($eSale, ['paymentStatus' => \selling\Sale::PROCESSING]);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-sepa-processing', 'Stripe event id #'.$event['id']);

	}

	/**
	 * Validation d'un paiement asynchrone comme le prélèvement SEPA.
	 * https://stripe.com/docs/testing#sepa-direct-debit
	 */
	public static function paymentSucceeded(\selling\Sale $eSale, array $event): void {

		$object = $event['data']['object'];

		$amountReceived = (int)$object['amount_received'];
		$amountExpected = (int)round($eSale['priceIncludingVat'] * 100);

		if($amountReceived !== $amountExpected) {
			trigger_error('Amount received '.($object['amount_received'] / 100).' different from amount expected '.($eSale['priceIncludingVat']).' in sale #'.$eSale['id'].' (event #'.$object['id'].')', E_USER_WARNING);
			return;
		}

		\selling\PaymentLib::updateStatus($object['id'], \selling\Payment::SUCCESS);

		self::completePaid($eSale, $object['id']);

	}

	/**
	 * Lance la collecte d'un prélèvement SEPA.
	 */
	public static function collect(\selling\Sale $eSale) {

		$eSale->expects(['id', 'customer', 'farm', 'shop', 'paymentStatus']);

		$eCustomer = \selling\CustomerLib::getById($eSale['customer']['id']);
		\payment\StripeLib::loadSepa($eCustomer);

		$arguments = [
			'amount' => $eSale['priceIncludingVat'] * 100
		];

		if($eCustomer['email']) {
			$arguments['receipt_email'] = $eCustomer['email'];
		}
		$eStripeFarm = \payment\StripeLib::getByFarm($eSale['farm']);

		if($eSale['paymentStatus'] !== \selling\Sale::UNDEFINED) {
			throw new \Exception('Sale '.$eSale['id'].' is not allowed for collection');
		}

		$payment_intent = \payment\StripeLib::createPayment($eCustomer['stripeSepa'], $eStripeFarm, $arguments);

		\selling\Sale::model()->update($eSale, ['paymentStatus' => \selling\Sale::WAITING]);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-sepa-collect', 'Stripe payment_intent id #'.$payment_intent['id']);

	}

	/**
	 * Création d'un payment intent pour un paiement par prélèvement SEPA.
	 *
	 * Lancé par Stripe après que OTF a lancé la collecte (collect).
	 */
	public static function completeCheckoutSepaDebit(\selling\Sale $eSale, array $event): void {

		$object = $event['data']['object'];

		\selling\Sale::model()->update($eSale, ['paymentStatus' => \selling\Sale::WAITING]);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-sepa-created', 'Stripe event id #'.$event['id']);

	}

	private static function completePaid(\selling\Sale $eSale, string $eventId): void {

		$eSale['oldStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentStatus'] = \selling\Sale::PAID;

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentStatus']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-succeeded', 'Stripe event #'.$eventId);

		$cItem = \selling\ItemLib::getBySale($eSale);

		self::notify('salePaid', $eSale, $cItem);

	}

}
?>