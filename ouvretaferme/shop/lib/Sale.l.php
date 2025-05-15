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

	public static function getByCustomerForDate(Shop $eShop, Date $eDate, \selling\Customer $eCustomer): \Collection {

		if($eCustomer->empty()) {
			return new \Collection();
		}

		if($eShop['shared']) {

			$eShop->expects(['cShare']);

			$cCustomer = \selling\Customer::model()
				->select('id')
				->whereUser($eCustomer['user'])
				->whereFarm('IN', $eShop['cShare']->getColumn('farm'))
				->getCollection();

			if($cCustomer->empty()) {
				return new \Collection();
			}

			\selling\Sale::model()->whereCustomer('IN', $cCustomer);

		} else {
			\selling\Sale::model()->whereCustomer($eCustomer);
		}

		return \selling\Sale::model()
			->select(\selling\Sale::getSelection())
			->whereShop($eShop)
			->whereShopDate($eDate)
			->wherePreparationStatus('NOT IN', [\selling\Sale::CANCELED, \selling\Sale::DRAFT])
			->getCollection(index: 'farm');

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

		self::buildReference($eSaleReference, $eUser);

		// Création des produits
		$total = 0.0;
		$ccItem = self::buildItems($eSaleReference['basket'], $total);

		self::applyShopOrderMin($eSaleReference, $total);
		self::applyShopShipping($eSaleReference, $total);

		\selling\Sale::model()->beginTransaction();

		// Création du client sur la ferme à l'origine de la boutique partagée
		if($eShop->isShared()) {
			\selling\CustomerLib::getByUserAndFarm($eUser, $eShop['farm'], autoCreate: TRUE, autoCreateType: $eSaleReference['type']);
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

			self::checkPayment($eFarm, $eShop, $eSale);

		}

		if($eShop->isShared()) {
			$cItemLinearized = $ccItem->linearize();
			$group = TRUE;
			self::notify('saleConfirmed', $eSaleReference, $eUser, $cItemLinearized, $group);
		}

		\selling\Sale::model()->commit();

		if($eShop['hasPayment'] === FALSE) {
			return ShopUi::confirmationUrl($eShop, $eDate);
		} else {
			return ShopUi::paymentUrl($eShop, $eDate);
		}

	}

	public static function checkPayment(\farm\Farm $eFarm, Shop $eShop, \selling\Sale $eSale): void {

		if($eShop['hasPayment'] === FALSE) {

			if($eShop->isShared()) {

				$eShare = $eShop['cShare'][$eFarm['id']];
				self::createDirectPayment($eShare['paymentMethod'], $eSale);

			} else {
				self::createDirectPayment(NULL, $eSale);
			}

		}

	}

	public static function buildReference(\selling\Sale $eSaleReference, \user\User $eUser): void {

		$eSaleReference->merge([
			'origin' => \selling\Sale::SALE,
			'type' => $eSaleReference['shopDate']['type'],
			'preparationStatus' => \selling\Sale::BASKET,
			'deliveredAt' => $eSaleReference['shopDate']['deliveryDate'],
			'shopPoint' => PointLib::getById($eSaleReference['shopPoint'])
		]);

		$eSaleReference['taxes'] = $eSaleReference->getTaxesFromType();

		if(
			$eSaleReference['shopPoint']->notEmpty() and
			$eSaleReference['shopPoint']['type'] === Point::HOME
		) {
			$eSaleReference->copyAddressFromUser($eUser);
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
				'number' => $number,
				'vatRate' => \Setting::get('selling\vatRates')[$eProductSelling['vat']],
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
			$eSale['paymentMethod']->empty() or
			$eSale['paymentMethod']['fqn'] === \payment\MethodLib::TRANSFER
		);

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

		self::buildReference($eSaleReference, $eUser);

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
							'oldPaymentMethod' => $eSaleExisting['paymentMethod'],
							'oldPaymentStatus' => $eSaleExisting['paymentStatus'],
						] + $eSaleReference->extracts($properties));

						$cItem->setColumn('sale', $eSaleExisting);
						$cItem->setColumn('customer', $eSaleExisting['customer']);

						\selling\SaleLib::update($eSaleExisting, $properties);
						\selling\ItemLib::createCollection($eSaleExisting, $cItem, replace: TRUE);

						self::checkPayment($eFarm, $eShop, $eSaleExisting);

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

						self::checkPayment($eFarm, $eShop, $eSaleNew);

					}

				} else {

					if($eSaleExisting->notEmpty()) {

						// Annuler la vente
						\selling\SaleLib::updatePreparationStatusCollection(new \Collection([$eSaleExisting]), \selling\Sale::CANCELED);

						$group = FALSE;
						self::notify('saleCanceled', $eSaleExisting, $group);

					}

				}

			}

			// Supprimer les paiements liés
			\selling\PaymentLib::deleteBySale($eSaleReference);

		\selling\Sale::model()->commit();


		if($eShop->isShared()) {
			$cItemLinearized = $ccItem->linearize();
			$group = TRUE;
			self::notify('saleUpdated', $eSaleReference, $eUser, $cItemLinearized, $group);
		}

		if($eShop['hasPayment'] === FALSE) {
			return ShopUi::confirmationUrl($eShop, $eDate);
		} else {
			return ShopUi::paymentUrl($eShop, $eDate);
		}

	}

	public static function createPayment(?string $payment, \selling\Sale $eSale): string {

		return match($payment) {
			\payment\MethodLib::ONLINE_CARD => self::createCardPayment($eSale),
			\payment\MethodLib::TRANSFER => self::createDirectPayment(\payment\MethodLib::TRANSFER, $eSale),
			default => self::createDirectPayment(NULL, $eSale),
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

		$eMethod = \payment\MethodLib::getByFqn(\payment\MethodLib::ONLINE_CARD);

		$ePayment = \selling\PaymentLib::createBySale($eSale, $eMethod, $stripeSession['id']);

		$eSale['paymentMethod'] = $eMethod;
		$eSale['paymentStatus'] = \selling\Sale::NOT_PAID;
		$eSale['onlinePaymentStatus'] = \selling\Sale::PENDING;

		\selling\SaleLib::update($eSale, ['paymentMethod', 'paymentStatus', 'onlinePaymentStatus']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-initiated', 'Stripe checkout id #'.$stripeSession['id'], ePayment: $ePayment);

		\selling\Sale::model()->commit();

		return $stripeSession['url'];

	}

	public static function createDirectPayment(?string $method, \selling\Sale $eSale): string {

		if(in_array($method, [NULL, \payment\MethodLib::TRANSFER]) === FALSE) {
			throw new \Exception('Invalid method');
		}

		$eSale->expects([
			'farm',
			'shopDate',
			'shop' => [
				'farm' => ['name']
			],
			'customer'
		]);

		$eMethod = $method !== NULL ? \payment\MethodLib::getByFqn($method) : new \payment\Method();

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentMethod'] = $eMethod;
		$eSale['paymentStatus'] = \selling\Sale::NOT_PAID;

		\selling\Sale::model()->beginTransaction();

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentMethod', 'paymentStatus']);

		if($method !== NULL) {

			$ePayment = new \selling\Payment([
				'sale' => $eSale,
				'method' => $eMethod,
				'customer' => $eSale['customer'],
				'farm' => $eSale['farm'],
				'amountIncludingVat' => $eSale['priceIncludingVat'],
				'checkoutId' => NULL,
			]);

			\selling\Payment::model()->insert($ePayment);
		}

		$group = FALSE;
		self::notify($eSale['shopUpdated'] ? 'saleUpdated' : 'saleConfirmed', $eSale, $eSale['customer']['user'], $eSale['cItem'], $group);

		\selling\Sale::model()->commit();

		return ShopUi::confirmationUrl($eSale['shop'], $eSale['shopDate']);

	}

	public static function cancelForShop(Shop $eShop, Date $eDate, \selling\Customer $eCustomer) {

		\selling\Sale::model()->beginTransaction();

			$cSale = \shop\SaleLib::getByCustomerForDate($eShop, $eDate, $eCustomer)->validate('acceptStatusCanceledByCustomer');
			$cSale->setColumn('shop', $eShop);
			$cSale->setColumn('shopDate', $eDate);

			\selling\SaleLib::updatePreparationStatusCollection($cSale, \selling\Sale::CANCELED);

			foreach($cSale as $eSale) {
				$group = FALSE;
				self::notify('saleCanceled', $eSale, $group);
			}

			if($eShop->isShared()) {
				$group = TRUE;
				self::notify('saleCanceled', $eSale, $group);
			}

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

			$newValues = [
				'paymentStatus' => \selling\Sale::NOT_PAID,
				'onlinePaymentStatus' => \selling\Sale::FAILURE,
				'paymentMethod' => \payment\MethodLib::getByFqn('online-card'),
			];
			\selling\Sale::model()
				->wherePaymentStatus('NOT IN', [\selling\Sale::PAID]) // En cas de concurrence (si le client a réussi entre temps)
				->update($eSale, $newValues);

			\selling\HistoryLib::createBySale($eSale, 'shop-payment-failed', 'Stripe event id #'.$object['id'].' (event type '.$event['type'].')');

			self::notify('saleFailed', $eSale, $eSale['customer']['user']);

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
			'status' => \selling\Payment::SUCCESS,
			'amountIncludingVat' => $eSale['priceIncludingVat'],
		]);

		self::completePaid($eSale, $object['id']);

		\selling\Sale::model()->commit();

	}

	protected static function completePaid(\selling\Sale $eSale, string $eventId): void {

		$eSale['oldPreparationStatus'] = $eSale['preparationStatus'];
		$eSale['preparationStatus'] = \selling\Sale::CONFIRMED;
		$eSale['paymentStatus'] = \selling\Sale::PAID;
		$eSale['paymentMethod'] = \payment\MethodLib::getByFqn('online-card');
		$eSale['onlinePaymentStatus'] = \selling\Sale::SUCCESS;

		\selling\SaleLib::update($eSale, ['preparationStatus', 'paymentStatus']);

		\selling\HistoryLib::createBySale($eSale, 'shop-payment-succeeded', 'Stripe event #'.$eventId);

		$cItem = \selling\SaleLib::getItems($eSale);

		self::notify('salePaid', $eSale, $eSale['customer']['user'], $cItem);

	}

}
?>
