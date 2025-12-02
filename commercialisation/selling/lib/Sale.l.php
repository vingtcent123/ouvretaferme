<?php
namespace selling;

class SaleLib extends SaleCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Sale $e) {

			return $e->isComposition() ?
				['customer', 'deliveredAt', 'productsList'] :
				['customer', 'shopDate', 'deliveredAt', 'productsList', 'shipping', 'preparationStatus'];

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Sale $e) {

			$e->expects(['preparationStatus']);

			$properties = ['comment'];

			if($e->isLocked() === FALSE) {

				$properties[] = 'deliveredAt';

				if($e->acceptDiscount()) {
					$properties[] = 'discount';
				}

			}

			if($e->acceptUpdateShipping()) {
				$properties[] = 'shipping';

				if($e['hasVat']) {
					$properties[] = 'shippingVatRate';
				}
			}

			if($e->acceptUpdateShopPoint()) {
				$properties[] = 'shopPointPermissive';
			}

			if($e->acceptUpdatePayment()) {
				$properties[] = 'paymentMethod';
				$properties[] = 'paymentStatus';
			}

			return $properties;

		};

	}

	public static function getExample(\farm\Farm $eFarm, string $type, \shop\Shop $eShop = new \shop\Shop()): Sale {

		$id = match($type) {
			Customer::PRO => SellingSetting::EXAMPLE_SALE_PRO,
			Customer::PRIVATE => SellingSetting::EXAMPLE_SALE_PRIVATE,
		};

		$eMethod = \payment\MethodLib::getByFqn(get('paymentMethod', 'string', \payment\MethodLib::CARD));
		if($eMethod->empty()) {
			$cPayment = new \Collection();
		} else {
			$cPayment = new \Collection([
				new Payment([
					'method' => $eMethod,
					'onlineStatus' => ($eMethod['fqn'] ?? NULL) === \payment\MethodLib::ONLINE_CARD ? Payment::SUCCESS : NULL,
				])
			]);
		}

		$eSale = \selling\SaleLib::getById($id);
		$eSale['document'] = '123';
		$eSale['farm'] = $eFarm;
		$eSale['hasVat'] = $eFarm->getSelling('hasVat');
		$eSale['customer']['legalName'] = '[Nom du client]';
		$eSale['customer']['invoiceStreet1'] = '[Addresse]';
		$eSale['customer']['invoiceStreet2'] = NULL;
		$eSale['customer']['invoicePostcode'] = '[Code postal]';
		$eSale['customer']['invoiceCity'] = '[Ville]';
		$eSale['deliveryStreet1'] = $eSale['customer']['invoiceStreet1'];
		$eSale['deliveryStreet2'] = $eSale['customer']['invoiceStreet2'];
		$eSale['deliveryPostcode'] = $eSale['customer']['invoicePostcode'];
		$eSale['deliveryCity'] = $eSale['customer']['invoiceCity'];
		$eSale['customer']['email'] = 'client@email.com';
		$eSale['orderFormValidUntil'] = currentDate();
		$eSale['orderFormPaymentCondition'] = $eFarm->getSelling('orderFormPaymentCondition');
		$eSale['invoice']['taxes'] = \selling\Invoice::INCLUDING;
		$eSale['invoice']['hasVat'] = $eFarm->getSelling('hasVat');
		$eSale['invoice']['name'] = Configuration::getNumber($eFarm->getSelling('invoicePrefix'), 123);
		$eSale['invoice']['priceExcludingVat'] = $eSale['priceExcludingVat'];
		$eSale['invoice']['priceIncludingVat'] = $eSale['priceIncludingVat'];
		$eSale['invoice']['date'] = currentDate();
		$eSale['invoice']['paymentCondition'] = $eFarm->getSelling('invoicePaymentCondition');
		$eSale['invoice']['header'] = $eFarm->getSelling('invoiceHeader');
		$eSale['invoice']['footer'] = $eFarm->getSelling('invoiceFooter');
		$eSale['invoice']['customer'] = $eSale['customer'];
		$eSale['cItem'] = self::getItems($eSale);
		$eSale['cPayment'] = $cPayment;

		$position = 0;
		foreach($eSale['cItem'] as $eItem) {
			$eItem['name'] = 'Produit '.(++$position);
		}

		if($eShop->notEmpty()) {

			$eShop->expects(['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']);

			$eSale['shop'] = $eShop;
			$eSale['shop']['farm'] = $eFarm;
			$eSale['shopDate'] = new \shop\Date([
				'id' => 123,
				'deliveryDate' => ($eShop['opening'] === \shop\Shop::ALWAYS) ? NULL : currentDate(),
				'type' => $eShop['type']
			]);
			$eSale['shopPoints'] = [
				\shop\Point::PLACE => new \shop\Point([
					'type' => \shop\Point::PLACE,
					'name' => s("[Nom du point de retrait]"),
					'description' => s("[Informations sur le point de retrait]"),
					'address' => $eSale['deliveryStreet1'],
					'place' => $eSale['deliveryCity']
				]),
				\shop\Point::HOME => new \shop\Point([
					'type' => \shop\Point::HOME
				])
			];
		}

		return $eSale;
		
	}

	public static function getForLabelsByIds(\farm\Farm $eFarm, array $ids, bool $selectItems = FALSE): \Collection {

		Sale::model()
			->where('m1.id', 'IN', $ids)
			->sort(new \Sql('IF(lastName IS NULL, name, lastName), firstName, m1.id'))
			->where('m1.farm', $eFarm);

		return self::getForLabels($selectItems);
	}

	public static function getForLabelsByDate(\farm\Farm $eFarm, \shop\Date $eDate, bool $selectItems = FALSE, bool $selectPoint = FALSE): \Collection {

		Sale::model()
			->whereShopDate($eDate)
			->wherePreparationStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
			->sort(new \Sql('shopPoint, IF(lastName IS NULL, name, lastName), firstName, m1.id'))
			->where('m1.farm', $eFarm, if: $eFarm->notEmpty());


		return self::getForLabels($selectItems, $selectPoint);

	}

	public static function fillItems(\Collection $cSale): void {

		Sale::model()
			->select([
				'cItem' => Item::model()
					->select(Item::getSelection())
					->whereIngredientOf(NULL)
					->sort([
						'name' => SORT_ASC,
						'id' => SORT_ASC
					])
					->delegateCollection('sale')
			])
			->get($cSale);

	}

	public static function fillForExport(\Collection $cSale): void {

		Sale::model()
			->select([
				'ccItem' => Item::model()
					->select(Item::getSelection())
					->sort([
						'name' => SORT_ASC,
						'id' => SORT_ASC
					])
					->delegateCollection('sale', index: ['ingredientOf', NULL])
			])
			->get($cSale);

	}

	public static function fillForCreate(Sale $eSale): void {

		$eSale->expects(['farm', 'customer']);

		if(get_exists('shopDate')) {

			$eDate = \shop\DateLib::getById(GET('shopDate'), \shop\Date::getSelection() + ['shop' => ['shared']])
				->validateProperty('farm', $eSale['farm'])
				->validate('acceptOrder', 'acceptNotShared');

		} else {
			$eDate = new \shop\Date();
		}

		if(get_exists('catalog')) {
			$eCatalog = \shop\CatalogLib::getById(GET('catalog'))
				->validateProperty('farm', $eSale['farm']);
		} else {
			$eCatalog = new \shop\Catalog();
		}

		if($eSale['customer']->empty()) {

			$eSale->merge([
				'type' => NULL,
				'shopDate' => $eDate,
				'shopProducts' => FALSE,
				'shop' => $eDate->empty() ? new \shop\Shop() : $eDate['shop'],
				'cProduct' => new \Collection(),
				'discount' => 0,
			]);

			if($eSale['shopDate']->notEmpty()) {
				$eSale['type'] = $eSale['shopDate']['type'];
			} else if($eCatalog->notEmpty()) {
				$eSale['type'] = $eCatalog['type'];
			}

		} else {

			$eSale->merge([
				'type' => $eSale['customer']['type'],
				'discount' => $eSale['customer']['discount'],
				'shopDate' => $eDate,
				'shopProducts' => FALSE,
				'shop' => $eDate->empty() ? new \shop\Shop() : $eDate['shop']
			]);

			if($eDate->notEmpty()) {
				$eDate->validateProperty('type', $eSale['type']);
			}

			if($eCatalog->notEmpty()) {
				$eCatalog->validateProperty('type', $eSale['type']);
			}

			if($eSale['shopDate']->notEmpty()) {
				$eSale['cProduct'] = \shop\ProductLib::exportAsSelling(\shop\ProductLib::getByDate($eSale['shopDate'], $eSale['customer'], public: TRUE, withParents: FALSE));
				$eSale['shopProducts'] = TRUE;
			} else if($eCatalog->notEmpty()) {
				$eSale['cProduct'] = \shop\ProductLib::exportAsSelling(\shop\ProductLib::getByCatalog($eCatalog));
				$eSale['shopProducts'] = TRUE;
			} else {
				$eSale['cProduct'] = \selling\ProductLib::getForSale($eSale['farm'], $eSale['type']);
			}

		}

	}

	private static function getForLabels(bool $selectItems = FALSE, bool $selectPoint = FALSE): \Collection {

		if($selectPoint) {
			Sale::model()->select([
				'shopPoint' => \shop\Point::getSelection()
			]);
		}

		$cSale = Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select(Sale::getSelection())
			->getCollection();

		if($selectItems) {
			self::fillForExport($cSale);
		}

		return $cSale;

	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL, ?int $position = NULL, ?int $number = NULL, \Search $search = new \Search()): array {

		if($search->get('customerName')) {
			$cCustomer = CustomerLib::getFromQuery($search->get('customerName'), $eFarm);
			Sale::model()->where('m1.customer', 'IN', $cCustomer);
		}

		if($search->get('invoicing')) {
			Sale::model()
				->whereInvoice(NULL)
				->whereProfile('!=', Sale::MARKET)
				->whereItems('>', 0)
				->wherePreparationStatus(Sale::DELIVERED);
		}

		$search->validateSort(['id', 'firstName', 'lastName', 'deliveredAt', 'items', 'priceExcludingVat', 'preparationStatus'], 'preparationStatus-');

		$sort = 'FIELD(preparationStatus, "'.Sale::SELLING.'", "'.Sale::DRAFT.'", "'.Sale::CONFIRMED.'", "'.Sale::PREPARED.'", "'.Sale::DELIVERED.'", "'.Sale::EXPIRED.'", "'.Sale::CANCELED.'")';

		$joins = 1;

		if(str_starts_with($search->getSort(), 'firstName') or str_starts_with($search->getSort(), 'lastName')) {
			$joins++;
			Sale::model()->join(Customer::model(), 'm1.customer = m'.($joins).'.id');
		}

		if($search->get('paymentMethod')) {
			$joins++;
			Sale::model()->join(Payment::model(), 'm1.id = m'.($joins).'.sale AND method = '.$search->get('paymentMethod'));
		}

		$cSale = Sale::model()
			->select(Sale::getSelection())
			->select([
				'cPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->delegateCollection('sale', 'type'),
			])
			->option('count')
			->where('m1.id', 'NOT IN', $search->get('notId'), if: $search->get('notId')?->notEmpty())
			->whereDocument($search->get('document'), if: $search->get('document'))
			->where('m1.id', 'IN', fn() => explode(',', $search->get('ids')), if: $search->get('ids'))
			->where('m1.farm', $eFarm)
			->where('m1.type', $type, if: $type !== NULL)
			->where('m1.customer', $search->get('customer'), if: $search->get('customer'))
			->whereDeliveredAt('LIKE', '%'.$search->get('deliveredAt').'%', if: $search->get('deliveredAt'))
			->whereDeliveredAt('>', new \Sql('CURDATE() - INTERVAL '.Sale::model()->format($search->get('delivered')).' DAY'), if: $search->get('delivered'))
			->wherePreparationStatus($search->get('preparationStatus'), if: $search->get('preparationStatus'))
			->wherePreparationStatus('!=', Sale::COMPOSITION)
			->where('m1.stats', TRUE)
			->sort($search->buildSort([
				'firstName' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('IF(firstName IS NULL, name, firstName), lastName, m1.id'),
					SORT_DESC => new \Sql('IF(firstName IS NULL, name, firstName) DESC, lastName DESC, m1.id DESC')
				},
				'lastName' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('IF(lastName IS NULL, name, lastName), firstName, m1.id'),
					SORT_DESC => new \Sql('IF(lastName IS NULL, name, lastName) DESC, firstName DESC, m1.id DESC')
				},
				'preparationStatus' => fn($direction) => match($direction) {
					SORT_ASC => new \Sql('
						(deliveredAt = CURDATE()) ASC,
						(preparationStatus = "'.Sale::DRAFT.'") ASC,
						(deliveredAt < CURDATE()) DESC,
						IF(deliveredAt > CURDATE(), TO_DAYS(deliveredAt), TO_DAYS(deliveredAt) * -1) DESC,
						'.$sort.' DESC,
						m1.id ASC
					'),
					SORT_DESC => new \Sql('
						(deliveredAt = CURDATE()) DESC,
						(preparationStatus = "'.Sale::DRAFT.'") DESC,
						(deliveredAt < CURDATE()) ASC,
						IF(deliveredAt > CURDATE(), TO_DAYS(deliveredAt), TO_DAYS(deliveredAt) * -1) ASC,
						'.$sort.' ASC,
						m1.id DESC
					')
				}
			]))
			->getCollection($position, $number);

		return [$cSale, Sale::model()->found()];

	}

	public static function getNextByFarm(\farm\Farm $eFarm, ?string $type = NULL): array {

		$getSales = fn(string $sign, int $sort, int $number) => Sale::model()
			->select([
				'deliveredAt',
				'turnover' => new \Sql('SUM(priceExcludingVat)', 'float')
			])
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->wherePreparationStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::SELLING, Sale::DELIVERED])
			->whereDeliveredAt($sign, currentDate())
			->whereStats(TRUE)
			->group('deliveredAt')
			->sort(['deliveredAt' => $sort])
			->getCollection(0, $number)
			->getArrayCopy();

		$sales = $getSales('>=', SORT_ASC, 5);

		if(count($sales) < 5) {

			$oldSales = $getSales('<', SORT_DESC, 5 - count($sales));

			if($oldSales) {
				$sales = array_merge($sales, $oldSales);
			}

		}

		return $sales;

	}

	public static function getByFarmForLabel(\farm\Farm $eFarm): \Collection {

		return Sale::model()
			->select(Sale::getSelection())
			->whereFarm($eFarm)
			->wherePreparationStatus('IN', [Sale::CONFIRMED, Sale::PREPARED])
			->whereType(Customer::PRO)
			->sort(new \Sql('FIELD(preparationStatus, "'.Sale::CONFIRMED.'", "'.Sale::PREPARED.'") DESC, deliveredAt ASC, id ASC'))
			->getCollection();

	}

	public static function getByDeliveredDay(\farm\Farm $eFarm, string $date, ?string $type = NULL): \Collection {

		if($type !== NULL) {
			Sale::model()->whereType($type);
		}

		return Sale::model()
			->select(Sale::getSelection())
			->whereFarm($eFarm)
			->whereDeliveredAt($date)
			->wherePreparationStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
			->whereStats(TRUE)
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByDate(
		\shop\Date $eDate,
		?array $preparationStatus = NULL,
		\farm\Farm $eFarm = new \farm\Farm(),
		?array $select = NULL
	): \Collection {

		return Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select($select ?? Sale::getSelection())
			->whereShopDate($eDate)
			->where('m1.farm', $eFarm, if: $eFarm->notEmpty())
			->wherePreparationStatus('IN', $preparationStatus, if: $preparationStatus !== NULL)
			->sort(new \Sql('shopPoint ASC, IF(lastName IS NULL, name, lastName), firstName, m1.id'))
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByCustomer(Customer $eCustomer): \Collection {

		return Sale::model()
			->select(Sale::getSelection())
			->select([
				'cPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->delegateCollection('sale', 'type')
			])
			->select(['cPayment' => PaymentLib::delegateBySale()])
			->whereCustomer($eCustomer)
			->sort(new \Sql('FIELD(preparationStatus, "'.Sale::DRAFT.'", "'.Sale::CONFIRMED.'", "'.Sale::PREPARED.'", "other") DESC, id DESC'))
			->getCollection();

	}

	public static function getByInvoice(Invoice $eInvoice): \Collection {

		return Sale::model()
			->select(Sale::getSelection())
			->whereInvoice($eInvoice)
			->sort(['deliveredAt' => SORT_ASC])
			->getCollection();

	}

	public static function getForInvoice(Customer $eCustomer, array $ids, bool $checkInvoice = TRUE): Sale|\Collection {

		$cSale = Sale::model()
			->select(Sale::getSelection())
			->select([
				'cPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->delegateCollection('sale', 'type')
			])
			->whereCustomer($eCustomer)
			->whereId('IN', $ids)
			->whereItems('>', 0)
			->whereInvoice(NULL, if: $checkInvoice)
			->whereProfile('!=', Sale::MARKET)
			->wherePreparationStatus(Sale::DELIVERED)
			->sort(['id' => SORT_ASC])
			->getCollection();

		self::fillItems($cSale);

		return $cSale;

	}

	public static function getForMonthlyInvoice(\farm\Farm $eFarm, string $month, ?string $type): \Collection {

		if($type !== NULL and ctype_digit($type)) {
			$eCustomerGroup = CustomerGroupLib::getById($type);
		} else {
			$eCustomerGroup = new CustomerGroup();
		}

		$mSale = Sale::model()
			->select([
				'customer' => ['type', 'name'],
				'hasVat', 'taxes',
				'priceExcludingVat' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'priceIncludingVat' => new \Sql('SUM(priceIncludingVat)', 'float'),
				'number' => new \Sql('COUNT(*)'),
				'list' => new \Sql('GROUP_CONCAT(m1.id ORDER BY m1.id SEPARATOR ",")')
			])
			->where('m1.farm = '.$eFarm['id'])
			->whereCustomer('IN', fn() => CustomerLib::getByGroup($eCustomerGroup), if: $eCustomerGroup->notEmpty())
			->whereType($type, if: in_array($type, [Customer::PRIVATE, Customer::PRO]))
			->whereDeliveredAt('LIKE', $month.'%')
			->whereInvoice(NULL)
			->whereProfile('IN', [Sale::SALE, Sale::SALE_MARKET])
			->wherePreparationStatus(Sale::DELIVERED)
			->or(
			fn() => $this->wherePaymentStatus(Sale::NOT_PAID),
			fn() => $this->wherePaymentStatus(NULL)
			)
			->group(['m1.customer', 'taxes', 'hasVat']);

		if($type === \payment\MethodLib::TRANSFER) {
			$eMethodTransfer = \payment\MethodLib::getByFqn(\payment\MethodLib::TRANSFER);
			$mSale
				->join(Payment::model(), 'm1.id = m2.sale', 'LEFT')
				->where('m2.method = '.$eMethodTransfer['id']);
		}

		return $mSale
			->getCollection()
			->sort(['m1.customer' => ['name']]);

	}

	public static function getByCustomers(\Collection $cCustomer, ?int $limit = NULL): \Collection {

		if($cCustomer->empty()) {
			return new \Collection();
		}

		return Sale::model()
			->select(Sale::getSelection())
			->whereCustomer('IN', $cCustomer)
			->whereStats(TRUE)
			->sort([
				'id' => SORT_DESC
			])
			->getCollection(0, $limit);

	}

	public static function getByParent(Sale $eSale, bool $indexByStatus = TRUE): \Collection {

		$ccSale = Sale::model()
			->select(Sale::getSelection() + [
				'createdBy' => ['firstName', 'lastName', 'vignette'],
				'cPayment' => PaymentLib::delegateBySale()
			])
			->whereFarm($eSale['farm'])
			->whereMarketParent($eSale)
			->sort(new \Sql('FIELD(preparationStatus, "'.Sale::DRAFT.'", "'.Sale::CONFIRMED.'", "'.Sale::CANCELED.'") ASC, createdAt DESC'))
			->getCollection(NULL, NULL, $indexByStatus ? ['preparationStatus', NULL] : NULL);

		if($ccSale->empty()) {
			return $ccSale;
		}

		if($indexByStatus) {
			$ccSale[Sale::DRAFT] ??= new \Collection();
			$ccSale[Sale::DELIVERED] ??= new \Collection();
			$ccSale[Sale::CANCELED] ??= new \Collection();
		}

		return $ccSale;

	}

	public static function getByComposition(Product $eProduct): \Collection {

		if($eProduct['profile'] !== PRODUCT::COMPOSITION) {
			return new \Collection();
		}

		$cSale = Sale::model()
			->select(Sale::getSelection())
			->whereCompositionOf($eProduct)
			->sort(['deliveredAt' => SORT_DESC])
			->getCollection(0, 100);

		if($cSale->notEmpty()) {
			self::fillItems($cSale);
		}

		return $cSale;

	}

	public static function create(Sale $e): void {

		$e->expects([
			'farm' => ['hasSales'],
			'profile',
			'type', 'taxes', 'hasVat',
			'customer',
		]);

		Sale::model()->beginTransaction();

		// Nouvelle composition de produit
		if($e->isComposition()) {

			$e->expects(['compositionOf']);

			$e['preparationStatus'] = Sale::COMPOSITION;
			$e['stats'] = FALSE;

		} else {
		}

		$ePaymentMethod = new \payment\Method();

		if(
			$e->isSale() and
			$e['shop']->empty()
		) {

			if($e['customer']['defaultPaymentMethod']->notEmpty()) {
				$e['paymentStatus'] = Sale::NOT_PAID;
				$ePaymentMethod = $e['customer']['defaultPaymentMethod'];
			}

		}

		if($e->isMarket()) {
			$e['marketSales'] = 0;
			$e['paymentStatus'] = NULL;
			$e['preparationStatus'] ??= Sale::CONFIRMED;
		} else {
			$e['preparationStatus'] ??= Sale::DRAFT;
		}

		if($e['preparationStatus'] === Sale::BASKET) {
			$e['expiresAt'] = new \Sql('NOW() + INTERVAL 1 HOUR');
		}

		$e['document'] = ConfigurationLib::getNextDocumentSales($e['farm']);

		try {

			parent::create($e);

		} catch(\DuplicateException $e) {

			switch($e->getInfo()['duplicate']) {

				case ['compositionOf', 'deliveredAt'] :
					Sale::fail('deliveredAt.composition');
					break;

			}

			Sale::model()->rollBack();

			return;

		}

		HistoryLib::createBySale($e, 'sale-created');

		if($e['farm']['hasSales'] === FALSE) {

			\farm\Farm::model()->update($e['farm'], [
				'hasSales' => TRUE
			]);

		}

		// Ajouter des produits
		if(($e['cItemCreate'] ?? new \Collection())->notEmpty()) {
			\selling\ItemLib::createCollection($e, $e['cItemCreate']);
		}

		if($ePaymentMethod->notEmpty()) {

			$ePayment = new Payment([
				'sale' => $e,
				'customer' => $e['customer'],
				'farm' => $e['farm'],
				'checkoutId' => NULL,
				'method' => $e['customer']['defaultPaymentMethod'],
				'amountIncludingVat' => $e['priceIncludingVat'],
				'onlineStatus' => NULL,
			]);

			Payment::model()->insert($ePayment);

		}

		if($e->isComposition()) {
			self::reorderComposition($e);
		}

		Sale::model()->commit();

	}

	/**
	 * Modifie ou supprime une composition existante
	 */
	public static function recalculateComposition(Sale $e, \Collection $cItemCopy): void {

		$e->expects(['deliveredAt', 'compositionEndAt']);

		$eProductComposition = $e['compositionOf'];

		$cItemComposition = Item::model()
			->select(ItemElement::getSelection())
			->whereFarm($e['farm'])
			->whereProduct($eProductComposition)
			->whereDeliveredAt('>=', $e['deliveredAt'])
			->whereDeliveredAt('<=', $e['compositionEndAt'], if: $e['compositionEndAt'] !== NULL)
			->getCollection();

		if($cItemComposition->empty()) {
			return;
		}

		Item::model()
			->whereSale('IN', $cItemComposition->getColumn('sale'))
			->whereIngredientOf('IN', $cItemComposition)
			->delete();

		$cItemIngredient = new \Collection();

		foreach($cItemComposition as $eItemComposition) {
			ItemLib::buildIngredients($cItemIngredient, $eItemComposition, $cItemCopy);
		}

		Item::model()->insert($cItemIngredient);


	}

	/**
	 * Modifie ou supprime une composition existante
	 */
	public static function reorderComposition(Sale $e): void {

		$cSale = Sale::model()
			->select('id', 'compositionEndAt', 'deliveredAt')
			->whereCompositionOf($e['compositionOf'])
			->where('compositionEndAt IS NULL OR compositionEndAt >= CURDATE() - INTERVAL '.(SellingSetting::COMPOSITION_LOCKED + 1).' DAY')
			->sort(new \Sql('deliveredAt ASC'))
			->getCollection();

		foreach($cSale as $offset => $eSale) {

			$eSaleNext = $cSale[$offset + 1] ?? new Sale();

			Sale::model()->update($eSale, [
				'compositionEndAt' => $eSaleNext->empty() ?
					NULL :
					new \Sql(Sale::model()->format($eSaleNext['deliveredAt']).' - INTERVAL 1 DAY')
			]);

		}

	}

	public static function createFromMarket(Sale $eSale): Sale {

		$eSale->expects(['id', 'farm', 'profile']);

		if($eSale->isMarket() === FALSE) {
			throw new \Exception('Invalid sale');
		}

		$e = new Sale();

		$e['customer'] = new Customer();
		$e['farm'] = $eSale['farm'];
		$e['profile'] = Sale::SALE_MARKET;
		$e['type'] = Customer::PRIVATE;
		$e['taxes'] = $e->getTaxesFromType();
		$e['hasVat'] = $e['farm']->getSelling('hasVat');
		$e['deliveredAt'] = $eSale['deliveredAt'];
		$e['marketParent'] = $eSale;
		$e['stats'] = FALSE;

		self::create($e);

		\selling\PaymentLib::fillDefaultMarketPayment($e);

		return $e;

	}

	/**
	 * Dupliquer une vente
	 */
	public static function duplicate(Sale $eSale): Sale {

		$properties = array_diff(
			Sale::model()->getProperties(),
			[
				'id', 'createdAt', 'createdBy',
				'invoice', 'orderFormValidUntil', 'orderFormPaymentCondition'
			]
		);
		
		$eSale->expects($properties);

		if($eSale->acceptDuplicate() === FALSE) {
			throw new \NotExpectedAction('Can duplicate');
		}

		Sale::model()->beginTransaction();

		// Ajouter une nouvelle vente
		$eSaleNew = new Sale($eSale->extracts($properties));
		$eSaleNew['preparationStatus'] = Sale::DRAFT;
		$eSaleNew['closed'] = FALSE;
		$eSaleNew['closedBy'] = new \user\User();
		$eSaleNew['closedAt'] = NULL;
		$eSaleNew['paymentStatus'] = NULL;

		if($eSaleNew->isMarket()) {
			$eSaleNew['marketSales'] = 0;
			$eSaleNew['priceExcludingVat'] = 0;
			$eSaleNew['priceIncludingVat'] = 0;
		}

		self::create($eSaleNew);

		// Dupliquer les items
		$cItem = self::getItems($eSale);

		foreach($cItem as $eItem) {

			$eItem['sale'] = $eSaleNew;
			$eItem['deliveredAt'] = $eSaleNew['deliveredAt'];

			if($eSaleNew->isMarket()) {

				unset($eItem['price'], $eItem['priceStats']);

			}

			unset($eItem['id'], $eItem['createdAt']);

		}

		Item::model()->insert($cItem);

		Sale::model()->commit();

		return $eSaleNew;

	}

	public static function associateShop(Sale $e, array $input): void {

		$fw = new \FailWatch();

		$e->build(['shopDate'], $input, new \Properties('update'));

		$fw->validate();

		if($e['shopDate']->empty()) {
			return;
		}

		self::update($e, ['shop', 'shopDate']);

	}

	public static function dissociateShop(Sale $e): void {

		$e->build(['shopDate'], [], new \Properties('update'));

		$properties = ['shop', 'shopDate'];

		if($e['preparationStatus'] === Sale::BASKET) {

			$e['oldPreparationStatus'] = Sale::BASKET;
			$e['preparationStatus'] = Sale::DRAFT;

			$properties[] = 'preparationStatus';

		}

		self::update($e, $properties);

	}

	public static function update(Sale $e, array $properties): void {

		Sale::model()->beginTransaction();

		$updatePreparationStatus = (
			in_array('preparationStatus', $properties) and
			$e->expects(['oldPreparationStatus']) and
			($e['oldPreparationStatus'] !== $e['preparationStatus'])
		);

		$emptyPaymentMethod = (
			in_array('paymentMethod', $properties) and
			$e['cPayment']->empty()
		);

		if(in_array('paymentMethod', $properties)) {

			unset($properties[array_search('paymentMethod', $properties)]);
			$updatePayments = TRUE;

		} else {

			$updatePayments = FALSE;

		}

		if($emptyPaymentMethod) {

			$e['paymentStatus'] = NULL;
			$e['onlinePaymentStatus'] = NULL;
			$properties[] = 'paymentStatus';
			$properties[] = 'onlinePaymentStatus';

		}

		if(in_array('shippingVatRate', $properties)) {

			$e['shippingVatFixed'] = ($e['shippingVatRate'] !== NULL);
			$properties[] = 'shippingVatFixed';

		}

		if(in_array('closed', $properties)) {

			if($e['closed'] === FALSE) {
				throw new \Exception("Impossible de rouvrir une vente");
			}

			$e['closedAt'] = new \Sql('NOW()');
			$e['closedBy'] = \user\ConnectionLib::getOnline();

			$properties[] = 'closedAt';
			$properties[] = 'closedBy';

		}

		if(in_array('shopPointPermissive', $properties)) {

			$properties[] = 'shopPoint';
			array_delete($properties, 'shopPointPermissive');
			$e['shopPoint'] = $e['shopPointPermissive'];

			if(
				$e['customer']['user']->notEmpty() and
				$e['shopPoint']->notEmpty() and
				$e['shopPoint']['type'] === \shop\Point::HOME
			) {

				$eUser = \user\UserLib::getById($e['customer']['user']);

				$e->copyAddressFromUser($eUser, $properties);

			} else {

				$e->emptyAddress($properties);

			}


		}

		if(
			in_array('shopDate', $properties) and
			$e['shopDate']->notEmpty()
		) {

			$e['shopDate']->expects('deliveryDate');

			if($e['shopDate']['deliveryDate'] !== NULL) {
				$properties[] = 'deliveredAt';
				$e['deliveredAt'] = $e['shopDate']['deliveryDate'];
			}

		}

		if($updatePreparationStatus) {

			$properties[] = 'statusAt';
			$properties[] = 'statusBy';

			$e['statusAt'] = new \Sql('NOW()');
			$e['statusBy'] = \user\ConnectionLib::getOnline();

			if($e['preparationStatus'] !== Sale::CANCELED) {

				$properties[] = 'expiresAt';

				if($e['preparationStatus'] === Sale::BASKET) {
					$e['expiresAt'] = new \Sql('NOW() + INTERVAL 1 HOUR');
				} else {
					$e['expiresAt'] = NULL;
				}

			}


			if(
				$e['oldPreparationStatus'] === Sale::DELIVERED and
				$e['closed'] === FALSE and
				$e['paymentStatus'] === Sale::PAID
			) {

				$properties[] = 'paymentStatus';
				$e['paymentStatus'] = Sale::NOT_PAID;

			}

		}

		parent::update($e, $properties);

		$newItems = [];

		if($updatePreparationStatus) {

			if($e['oldPreparationStatus'] === Sale::DELIVERED) {
				HistoryLib::createBySale($e, 'sale-delivered-cancel');
			} else {
				HistoryLib::createBySale($e, 'sale-'.$e['preparationStatus']);
			}

			$newItems['status'] = $e['preparationStatus'];

		}

		if(in_array('shopDate', $properties)) {
			$newItems['shop'] = $e['shop'];
			$newItems['shopDate'] = $e['shopDate'];
		}

		if(in_array('deliveredAt', $properties)) {
			$newItems['deliveredAt'] = $e['deliveredAt'];
		}

		if(in_array('type', $properties)) {
			$newItems['type'] = $e['type'];
		}

		if(in_array('discount', $properties)) {

			$newItems['discount'] = $e['discount'];

			$priceExcludingVat = match($e['taxes']) {
				Sale::INCLUDING => 'price / (1 + vatRate / 100)',
				Sale::EXCLUDING => 'price',
				NULL => 'price'
			};

			$newItems['priceStats'] = new \Sql('ROUND('.$priceExcludingVat.' * (100 - '.$e['discount'].') / 100 * 100) / 100');

		}

		if($newItems) {

			Item::model()
				->whereSale($e)
				->update($newItems);

		}

		if($updatePreparationStatus) {

			if($e['preparationStatus'] === Sale::SELLING) {
				MarketLib::updateSaleMarket($e);
			}

			if($e->isMarketSale()) {
				MarketLib::updateSaleMarket($e['marketParent']);
			}

		}

		if($updatePayments) {

			\selling\PaymentLib::deleteBySale($e);

			if($e['cPayment']->notEmpty() and $e->isMarketSale() === FALSE) {

				foreach($e['cPayment'] as $ePayment) {
					unset($ePayment['id']);
					Payment::model()->insert($ePayment);
				}
			}

			// Si on a mis à jour et qu'il ne reste plus de paiement en ligne
			if($e['onlinePaymentStatus'] !== NULL) {

				$cPayment = PaymentLib::getBySale($e);

				$hasValidOnlinePayment = $cPayment->find(fn($ePayment) => $ePayment->isPaid() and $ePayment['method']->isOnline())->count() > 0;

				if($hasValidOnlinePayment === FALSE) {
					$e['onlinePaymentStatus'] = NULL;
					Sale::model()->update($e, ['onlinePaymentStatus' => NULL]);
				}

			}

		}

		if(in_array('shipping', $properties)) {
			self::recalculate($e);
		}

		// À faire obligatoirement après recalculate()
		if(in_array('deliveredAt', $properties) and $e->isComposition()) {
			self::reorderComposition($e);
		}

		Sale::model()->commit();

	}

	public static function updatePreparationStatus(Sale $e, string $newStatus): void {

		if($e['preparationStatus'] === $newStatus) {
			return;
		}

		$e['oldPreparationStatus'] = $e['preparationStatus'];
		$e['preparationStatus'] = $newStatus;

		self::update($e, ['preparationStatus']);

	}

	public static function updatePreparationStatusCollection(\Collection $c, string $newStatus): void {

		Sale::model()->beginTransaction();

		foreach($c as $e) {
			self::updatePreparationStatus($e, $newStatus);
		}

		Sale::model()->commit();

	}

	public static function updatePaymentMethodCollection(\Collection $c, \payment\Method $eMethod): void {

		Sale::model()->beginTransaction();

		$methodId = ($eMethod['id'] ?? NULL);

		foreach($c as $e) {

			if(
				($e['cPayment']->empty() and $methodId === NULL) or
				($e['cPayment']->notEmpty() and in_array($methodId, $e['cPayment']->getColumnCollection('method')->getIds()))
			) {
				continue;
			}

			$e['cPayment'] = new Payment();
			if($methodId !== NULL and ($e['paymentStatus'] === NULL or $e['paymentStatus'] === Sale::NOT_PAID)) {
				$e['cPayment']->append(
					new Payment([
						'sale' => $e,
						'customer' => $e['customer'],
						'farm' => $e['farm'],
						'checkoutId' => NULL,
						'method' => $eMethod,
						'amountIncludingVat' => $e['priceIncludingVat'],
						'onlineStatus' => NULL,
					])
				);
				$e['paymentStatus'] = Sale::NOT_PAID;
			}

			self::update($e, ['paymentMethod', 'paymentStatus']);

		}

		Sale::model()->commit();

	}

	public static function updateCustomer(Sale $e, Customer $eCustomer): void {

		Sale::model()->beginTransaction();

		Sale::model()->update($e, [
			'customer' => $eCustomer
		]);

		Pdf::model()
			->whereSale($e)
			->delete();

		Item::model()
			->whereSale($e)
			->update([
				'customer' => $eCustomer,
			]);

		Payment::model()
			->whereSale($e)
			->update([
				'customer' => $eCustomer,
			]);

		Sale::model()->commit();

	}

	public static function deleteCollection(\Collection $cSale): void {

		foreach($cSale as $eSale) {
			self::delete($eSale);
		}

	}

	public static function emptyOnlinePaymentMethod(Sale $e): void {

		$e->expects(['id']);

		$cOnlineMethod = \payment\MethodLib::getOnline();
		foreach($cOnlineMethod as $eMethod) {
			PaymentLib::deleteBySaleAndMethod($e, $eMethod);
		}

		Sale::model()
			->update($e, [
				'paymentStatus' => NULL
			]);
	}

	public static function delete(Sale $e): void {

		$e->expects([
			'id', 'profile',
			'shopDate',
			'preparationStatus',
		]);

		Sale::model()->beginTransaction();

		$deleted = Sale::model()
			->wherePreparationStatus('IN', $e->getDeleteStatuses())
			->or(
				fn() => $this->wherePaymentStatus(Sale::NOT_PAID),
				fn() => $this->wherePaymentStatus(NULL)
			)
			->delete($e);

		if($deleted > 0) {

			Item::model()
				->whereSale($e)
				->delete();

			if($e->isMarket()) {

				$cSaleMarket = Sale::model()
					->select('id')
					->whereFarm($e['farm'])
					->whereMarketParent($e)
					->getCollection();

				Sale::model()
					->whereId('IN', $cSaleMarket)
					->update([
						'profile' => Sale::SALE,
						'marketParent' => NULL
					]);

				Item::model()
					->whereSale('IN', $cSaleMarket)
					->update([
						'parent' => NULL
					]);

			}

			if($e->isMarketSale()) {
				MarketLib::updateSaleMarket($e['marketParent']);
			}

			if($e->isComposition()) {
				self::reorderComposition($e);
			}

		}

		Sale::model()->commit();

	}

	public static function getItems(Sale $e, bool $withIngredients = FALSE, bool $public = FALSE, ?string $index = NULL): \Collection {
		return self::getItemsBySales(new \Collection([$e]), $withIngredients, $public, $index);
	}

	public static function getItemsBySales(\Collection $cSale, bool $withIngredients = FALSE, bool $public = FALSE, ?string $index = NULL): \Collection {

		$cItem = Item::model()
			->select(Item::getSelection())
			->whereSale('IN', $cSale)
			->whereIngredientOf(NULL, if: $withIngredients === FALSE)
			->sort([
				new \Sql('ingredientOf IS NOT NULL'),
				'name' => SORT_ASC,
				'id' => SORT_ASC
			])
			->getCollection(NULL, NULL, $index);

		if($withIngredients) {
			return self::fillIngredients($cItem, $public);
		} else {
			return $cItem;
		}

	}

	public static function fillIngredients(\Collection $cItem, bool $public = FALSE): \Collection {

		if($cItem->empty()) {
			return $cItem;
		}

		$cItemIngredient = new \Collection();
		$cItemMain = new \Collection();

		foreach($cItem as $key => $eItem) {

			if($eItem['ingredientOf']->empty()) {

				$cItemMain[$key] = $eItem;

				if($eItem['productComposition']) {

					if($public === FALSE or $eItem['product']['compositionVisibility'] === Product::PUBLIC) {
						$cItemIngredient[$eItem['id']] = new \Collection();
						$cItemMain[$key]['cItemIngredient'] = $cItemIngredient[$eItem['id']];
					} else {
						$cItemMain[$key]['productComposition'] = FALSE;
					}

				}

			} else {

				if($cItemIngredient->offsetExists($eItem['ingredientOf']['id'])) {
					$cItemIngredient[$eItem['ingredientOf']['id']][] = $eItem;
				}
			}

		}

		return $cItemMain;

	}

	public static function delegateIngredients(string $deliveredAt, string $propertyParent): SaleModel {

		return Sale::model()
			->select([
				'id',
				'cItem' => new ItemModel()
					->select(Item::getSelection())
					->sort([
						'name' => SORT_ASC,
						'id' => SORT_ASC
					])
					->delegateCollection('sale')
			])
			->whereDeliveredAt('<=', $deliveredAt)
			->or(
				fn() => $this->whereCompositionEndAt(NULL),
				fn() => $this->whereCompositionEndAt('>=', $deliveredAt)
			)
			->delegateProperty('compositionOf', 'cItem', fn($value) => $value ?? new \Collection(), propertyParent: $propertyParent);

	}

	public static function countItems(Sale $e): int {

		return Item::model()
			->whereSale($e)
			->whereIngredientOf(NULL)
			->count();

	}

	/**
	 * Recalculer la TVA et les prix de la vente en fonction des items
	 */
	public static function recalculate(Sale $e): void {

		$e->expects(['farm', 'discount', 'taxes', 'shippingVatRate', 'shippingVatFixed']);

		$cItem = Item::model()
			->select(ItemElement::getSelection())
			->whereSale($e)
			->whereIngredientOf(NULL)
			->getCollection();

		if($e->isComposition()) {
			self::recalculateComposition($e, $cItem);
		}

		// Plus rien dans la vente
		if($cItem->empty()) {

			$newValues = [
				'items' => 0,
				'vat' => NULL,
				'vatByRate' => NULL,
				'organic' => FALSE,
				'priceGross' => NULL,
				'priceIncludingVat' => NULL,
				'priceExcludingVat' => NULL,
			];

			$e->merge($newValues);

			Sale::model()
				->select(array_keys($newValues))
				->update($e);

			return;

		}

		$vatList = [];

		$newValues = [
			'items' => $cItem->count(),
			'vat' => 0.0,
			'vatByRate' => [],
			'organic' => FALSE,
			'priceIncludingVat' => 0.0,
			'priceExcludingVat' => 0.0,
		];

		if($e['shippingVatFixed'] === FALSE) {

			$newValues += [
				'shippingVatRate' => ($e['shipping'] === NULL) ? NULL : SellingSetting::DEFAULT_VAT_RATE,
			];

		}

		// Add items
		foreach($cItem as $eItem) {

			$vatList[(string)$eItem['vatRate']] ??= 0;
			$vatList[(string)$eItem['vatRate']] += $eItem['price'];

			if($e['shippingVatFixed'] === FALSE and $e['shipping'] !== NULL) {
				$newValues['shippingVatRate'] ??= $eItem['vatRate'];
				$newValues['shippingVatRate'] = min($newValues['shippingVatRate'], $eItem['vatRate']);
			}

			if($eItem['quality'] === \farm\Farm::ORGANIC) {
				$newValues['organic'] = TRUE;
			} else if($eItem['quality'] === \farm\Farm::CONVERSION) {
				$newValues['conversion'] = TRUE;
			}

		}

		// On applique la remise commerciale
		if($e['discount'] > 0) {

			$total = array_sum($vatList);
			$totalDiscount = Sale::calculateDiscount($total, $e['discount']);

			$position = 0;

			foreach($vatList as $rate => $amount) {

				if(++$position === count($vatList)) {
					$discount = $totalDiscount;
				} else {
					$discount = Sale::calculateDiscount($amount, $e['discount']);
					$totalDiscount -= $discount;
				}

				$vatList[$rate] = round($amount - $discount, 2);

			}

			$newValues['priceGross'] = round($total, 2);

		} else {
			$newValues['priceGross'] = NULL;
		}

		if($e['shipping'] !== NULL) {

			if($e['shippingVatFixed'] === FALSE) {

				// On écrase le taux de TVA calculé
				$eConfiguration = \selling\ConfigurationLib::getByFarm($e['farm']);

				if($eConfiguration['defaultVatShipping'] !== NULL) {
					$newValues['shippingVatRate'] = SellingSetting::VAT_RATES[$eConfiguration['defaultVatShipping']];
				}

				$shippingVatRate = $newValues['shippingVatRate'];

			} else {
				$shippingVatRate = $e['shippingVatRate'];
			}

			$newValues['shippingExcludingVat'] = match($e['taxes']) {
				Sale::INCLUDING => round($e['shipping'] / (1 + $shippingVatRate / 100), 2),
				Sale::EXCLUDING => $e['shipping']
			};

			$vatList[(string)$shippingVatRate] ??= 0;
			$vatList[(string)$shippingVatRate] += $e['shipping'];

			if($newValues['priceGross'] !== NULL) {
				$newValues['priceGross'] += $e['shipping'];
			}

		} else {
			$newValues['shippingExcludingVat'] = NULL;
		}

		foreach($vatList as $vatRate => $amount) {

			$vatRate = (float)$vatRate;
			$amount = round($amount, 2);

			$vat = match($e['taxes']) {
				Sale::INCLUDING => round($amount - $amount / (1 + $vatRate / 100), 2),
				Sale::EXCLUDING => round($amount * $vatRate / 100, 2)
			};

			$newValues['priceExcludingVat'] += match($e['taxes']) {
				Sale::INCLUDING => $amount - $vat,
				Sale::EXCLUDING => $amount
			};

			$newValues['priceIncludingVat'] += match($e['taxes']) {
				Sale::INCLUDING => $amount,
				Sale::EXCLUDING => $amount + $vat
			};

			$newValues['vatByRate'][] = [
				'vatRate' => $vatRate,
				'amount' => $amount,
				'vat' => $vat
			];

			$newValues['vat'] += $vat;

		}

		$e->merge($newValues);

		Sale::model()
			->select(array_keys($newValues))
			->update($e);

	}

	public static function getDefaultVat(\farm\Farm $eFarm): int {
		return 2;
	}

	public static function getVatRates(\farm\Farm $eFarm): array {

		// A filtrer selon les pays le cas échéant

		return SellingSetting::VAT_RATES;

	}

	public static function filterForAccounting(\farm\Farm $eFarm, \Search $search): SaleModel {

		return Sale::model()
			->wherePreparationStatus('NOT IN', [Sale::COMPOSITION, Sale::CANCELED, Sale::EXPIRED, Sale::DRAFT, Sale::BASKET])
			->where('priceExcludingVat != 0.0')
			->where('m1.farm = '.$eFarm['id'])
			->where('deliveredAt BETWEEN '.Sale::model()->format($search->get('from')).' AND '.Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}

	public static function filterForAccountingCheck(\farm\Farm $eFarm, \Search $search): SaleModel {

		return Sale::model()
			->wherePreparationStatus('NOT IN', [Sale::COMPOSITION, Sale::CANCELED, Sale::EXPIRED, Sale::DRAFT, Sale::BASKET])
			->where('priceExcludingVat != 0.0')
			->whereProfile('NOT IN', [Sale::MARKET])
			->where('m1.farm = '.$eFarm['id'])
			->where('deliveredAt BETWEEN '.Sale::model()->format($search->get('from')).' AND '.Sale::model()->format($search->get('to')))
			->where(new \Sql('DATE(deliveredAt) < CURDATE()'));

	}
	public static function countForAccountingCheck(\farm\Farm $eFarm, \Search $search): array {

		$nMissingPayment = self::filterForAccountingCheck($eFarm, $search)
       ->join(Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.Payment::model()->format(Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
       ->where('m2.id IS NULL')
			->count();

		$nSaleNotClosed = self::filterForAccountingCheck($eFarm, $search)
			->whereClosed(FALSE)
			->count();

		$nSalePreparationStatus = self::filterForAccountingCheck($eFarm, $search)
			->wherePreparationStatus('!=', Sale::DELIVERED)
			->count();

		return [
			'missingPayment' => $nMissingPayment,
			'notClosed' => $nSaleNotClosed,
			'preparationStatus' => $nSalePreparationStatus,
		];

	}

	public static function getForAccountingCheck(\farm\Farm $eFarm, \Search $search, string $type): \Collection {

		$select = [
			'id', 'customer' => ['name', 'type', 'destination'], 'preparationStatus', 'priceIncludingVat',
			'deliveredAt', 'document', 'farm', 'profile', 'createdAt', 'taxes', 'hasVat', 'priceExcludingVat',
			'onlinePaymentStatus', 'paymentStatus', 'closed',
			'marketParent' => ['customer' => ['name', 'type', 'destination']],
			'cPayment' => Payment::model()
				->select(Payment::getSelection())
				->or(
					fn() => $this->whereOnlineStatus(NULL),
					fn() => $this->whereOnlineStatus(Payment::SUCCESS)
				)
				->delegateCollection('sale', 'id')
		];

		if($type === 'missingPayment') {

			return self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->join(Payment::model(), 'm1.id = m2.sale AND (m2.onlineStatus = '.Payment::model()->format(Payment::SUCCESS).' OR onlineStatus IS NULL)', 'LEFT')
				->where('m2.id IS NULL')
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id');

		}

		if($type === 'notClosed') {

			return self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->whereClosed(FALSE)
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id');

		}

		if($type === 'preparationStatus') {

			return self::filterForAccountingCheck($eFarm, $search)
				->select($select)
				->wherePreparationStatus('!=', Sale::DELIVERED)
				->sort(['deliveredAt' => SORT_DESC])
				->getCollection(NULL, NULL, 'id');
		}

		return new \Collection();

	}
}
?>
