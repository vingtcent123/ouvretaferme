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

		if(OTF_DEMO) {

			$eSale['customer'] = new Customer([
				'siret' => '00000000000000',
				'vatNumber' => 'FR0000000000',
				'invoiceStreet1' => '3 rue des pissenlits',
				'invoiceStreet2' => '',
				'invoiceCity' => 'Fleurville',
				'invoiceCountry' => new \user\Country(['id' => \user\UserSetting::FR]),
			]);

		}

		$eSale['document'] = '123';
		$eSale['farm'] = $eFarm;
		$eSale['hasVat'] = $eFarm->getConf('hasVat');
		$eSale['customer']['legalName'] = '[Nom du client]';
		$eSale['customer']['invoiceStreet1'] = '[Addresse]';
		$eSale['customer']['invoiceStreet2'] = NULL;
		$eSale['customer']['invoicePostcode'] = '[Code postal]';
		$eSale['customer']['invoiceCity'] = '[Ville]';
		$eSale['deliveryStreet1'] = $eSale['customer']['invoiceStreet1'];
		$eSale['deliveryStreet2'] = $eSale['customer']['invoiceStreet2'];
		$eSale['deliveryPostcode'] = $eSale['customer']['invoicePostcode'];
		$eSale['deliveryCity'] = $eSale['customer']['invoiceCity'];
		$eSale['deliveryCountry'] = $eSale['customer']['invoiceCountry'];
		$eSale['customer']['name'] = 'Julien Laferme';
		$eSale['customer']['email'] = 'client@email.com';
		$eSale['deliveryNoteDate'] = currentDate();
		$eSale['deliveryNoteHeader'] = $eFarm->getConf('deliveryNoteHeader');
		$eSale['deliveryNoteFooter'] = $eFarm->getConf('deliveryNoteFooter');
		$eSale['orderFormValidUntil'] = currentDate();
		$eSale['orderFormPaymentCondition'] = $eFarm->getConf('orderFormPaymentCondition');
		$eSale['orderFormHeader'] = $eFarm->getConf('orderFormHeader');
		$eSale['orderFormFooter'] = $eFarm->getConf('orderFormFooter');
		$eSale['invoice']['taxes'] = \selling\Invoice::INCLUDING;
		$eSale['invoice']['hasVat'] = $eFarm->getConf('hasVat');
		$eSale['invoice']['name'] = \farm\Configuration::getNumber($eFarm->getConf('invoicePrefix'), 123);
		$eSale['invoice']['priceExcludingVat'] = $eSale['priceExcludingVat'];
		$eSale['invoice']['priceIncludingVat'] = $eSale['priceIncludingVat'];
		$eSale['invoice']['date'] = currentDate();
		$eSale['invoice']['dueDate'] = date('Y-m-d', time() + 86400 * 7);
		$eSale['invoice']['paymentCondition'] = $eFarm->getConf('invoicePaymentCondition');
		$eSale['invoice']['header'] = $eFarm->getConf('invoiceHeader');
		$eSale['invoice']['footer'] = $eFarm->getConf('invoiceFooter');
		$eSale['invoice']['customer'] = $eSale['customer'];
		$eSale['cItem'] = self::getItems($eSale);
		$eSale['cPayment'] = $cPayment;
		$eSale['ccPdf'] = new \Collection();

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
				->validate('acceptCreateSale');

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

			if($search->get('paymentMethod') === '-1') {
				Sale::model()
		      ->join(Payment::model(), 'm1.id = m'.($joins).'.sale', 'LEFT')
					->where('m'.($joins).'.id IS NULL')
				;
			} else {
				Sale::model()->join(Payment::model(), 'm1.id = m'.($joins).'.sale AND method = '.$search->get('paymentMethod'));
			}
		}

		$cSale = Sale::model()
			->select(Sale::getSelection())
			->select([
				'ccPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->sort(['version' => SORT_DESC])
					->delegateCollection('sale', ['type', NULL]),
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
			->wherePaymentStatus($search->get('paymentStatus'), if: $search->get('paymentStatus'))
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
		\farm\Farm $eFarm,
		int $page,
		int $number
	): \Collection {

		return Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select(\selling\Sale::getSelection() + [
				'shopPoint' => \shop\PointElement::getSelection()
			])
			->whereShopDate($eDate)
			->where('m1.farm', $eFarm, if: $eFarm->notEmpty())
			->sort(
				$eDate['deliveryDate'] === NULL ?
					['m1.id' => SORT_DESC] :
					new \Sql('shopPoint ASC, IF(lastName IS NULL, name, lastName), firstName, m1.id')
			)
			->getCollection($page * $number, $number, 'id');

	}

	public static function countByDate(
		\shop\Date $eDate,
		\farm\Farm $eFarm
	): int {

		return Sale::model()
			->whereShopDate($eDate)
			->whereFarm($eFarm, if: $eFarm->notEmpty())
			->count();

	}

	public static function getByCustomer(Customer $eCustomer): \Collection {

		return Sale::model()
			->select(Sale::getSelection())
			->select([
				'ccPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->sort(['version' => SORT_DESC])
					->delegateCollection('sale', ['type', NULL])
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
				'ccPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->sort(['version' => SORT_DESC])
					->delegateCollection('sale', ['type', NULL])
			])
			->whereCustomer($eCustomer)
			->whereId('IN', $ids)
			->whereItems('>', 0)
			->whereInvoice(NULL, if: $checkInvoice)
			->whereProfile(Sale::SALE)
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

		}

		$ePaymentMethod = new \payment\Method();

		if($e->isSale()) {

			$e->expects(['shop']);

			if($e['shop']->empty()) {

				if($e['customer']['defaultPaymentMethod']->notEmpty()) {

					$e['paymentStatus'] = Sale::NOT_PAID;
					$ePaymentMethod = \payment\MethodLib::getById($e['customer']['defaultPaymentMethod']);

				}

			} else {

				$e['secured'] = ($e['customer']['type'] === Customer::PRIVATE);

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

		$e['document'] = \farm\ConfigurationLib::getNextDocumentSales($e['farm']);

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
			PaymentLib::createByMethod($e, $ePaymentMethod);
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
		$e['hasVat'] = $e['farm']->getConf('hasVat');
		$e['deliveredAt'] = $eSale['deliveredAt'];
		$e['marketParent'] = $eSale;
		$e['stats'] = FALSE;

		self::create($e);

		$eMethod = $eSale['farm']->getConf('marketSalePaymentMethod');

		if($eMethod->notEmpty()) {
			$eMethod = \payment\MethodLib::getById($eMethod['id']);
			PaymentLib::createByMethod($eSale, $eMethod);
		}

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
				'closed', 'closedBy', 'closedAt',
				'invoice', 'orderFormValidUntil', 'orderFormPaymentCondition',
				// On ne conserve pas les informations de boutique
				'shop', 'shopDate', 'shopLocked', 'shopShared', 'shopUpdated', 'shopPoint', 'shopComment'
			]
		);
		
		$eSale->expects($properties);

		if($eSale->acceptDuplicate() === FALSE) {
			throw new \NotExpectedAction('Can duplicate');
		}

		Sale::model()->beginTransaction();

		// Ajouter une nouvelle vente
		$eSaleNew = new Sale($eSale->extracts($properties));
		$eSaleNew['shop'] = new \shop\Shop();

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

			$eItem['isComposition'] = $eItem['composition']->notEmpty();
			$eItem['composition'] = new Sale();

			// Requis pour récupérer les IDs
			Item::model()->insert($eItem);

		}

		// Traitement des produits composés
		foreach($cItem as $eItem) {

			if($eItem['isComposition']) {
				ItemLib::createIngredients($eItem);
			}

		}

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

		self::update($e, ['shop', 'shopDate', 'shopShared']);

	}

	public static function dissociateShop(Sale $e): void {

		$e->build(['shopDate'], [], new \Properties('update'));

		$properties = ['shop', 'shopDate', 'shopShared'];

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

		$updatePayments = array_delete($properties, 'paymentMethod');

		if($updatePayments) {

			if($e['cPayment']->empty()) {

				if(in_array($e['paymentStatus'], [Invoice::PAID, Invoice::NOT_PAID])) {

					$e['paymentStatus'] = NULL;
					$e['onlinePaymentStatus'] = NULL;

					$properties[] = 'paymentStatus';
					$properties[] = 'onlinePaymentStatus';

				}

			} else {

				// On met un statut de paiement par défaut s'il n'est pas renseigné
				if($e['paymentStatus'] === NULL) {

					$e['paymentStatus'] = Sale::NOT_PAID;
					$e['onlinePaymentStatus'] = NULL;

					$properties[] = 'paymentStatus';
					$properties[] = 'onlinePaymentStatus';

				}

			}

		}

		if(in_array('paymentStatus', $properties)) {

			if(
				$e['paymentStatus'] !== Sale::PAID and
				$e['onlinePaymentStatus'] !== NULL
			) {

				$e['onlinePaymentStatus'] = NULL;
				$properties[] = 'onlinePaymentStatus';

			}

			if($e['paymentStatus'] !== Sale::PAID) {

				$e['paidAt'] = NULL;
				$properties[] = 'paidAt';

			}

		}

		if(in_array('shippingVatRate', $properties)) {

			$e['shippingVatFixed'] = ($e['shippingVatRate'] !== NULL);
			$properties[] = 'shippingVatFixed';

		}

		if(in_array('closed', $properties)) {
			throw new \Exception("Clôture par des fonctions dédiées");
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
				$eUser->copyDeliveryAddress($e, $properties);

			} else {

				$e->emptyDeliveryAddress($properties);

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
				$e['preparationStatus'] === Sale::DELIVERED and
				$e->acceptSecuring()
			) {

				$properties[] = 'secured';
				$properties[] = 'securedAt';

				$e['secured'] = TRUE;
				$e['securedAt'] = new \Sql('NOW()');

			}

			if(
				$e['oldPreparationStatus'] === Sale::DELIVERED and
				$e['closed'] === FALSE and
				$e['paymentStatus'] === Sale::PAID
			) {

				$properties[] = 'paymentStatus';
				$properties[] = 'paidAt';

				$e['paymentStatus'] = Sale::NOT_PAID;
				$e['paidAt'] = NULL;

			}

		}

		if($properties) { // Si seul le moyen de paiement est modifié, pas de changement dans la table Sale
			parent::update($e, $properties);
		}

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

			// On doit associer ou dissocier le lien produit / catalogue des articles de la vente
			if(
				$e['shopDate']->notEmpty() and
				$e['shopDate']['catalogs']
			) {
				\shop\ProductLib::associateItems($e, $e['shopDate']['catalogs']);
			} else {
				$newItems['shopProduct'] = new \shop\Product();
			}


		}

		if(in_array('deliveredAt', $properties)) {
			$newItems['deliveredAt'] = $e['deliveredAt'];
		}

		if(in_array('profile', $properties)) {
			$newItems['profile'] = $e['profile'];
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

			$values = $e['cPayment']->toArray(function(Payment $ePayment) {
				return [
					'method' => $ePayment['method'],
					'amount' => $ePayment['amountIncludingVat'] ?? NULL
				];
			});

			PaymentLib::replaceSeveralBySale($e, $values);

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

		if(
			in_array('shipping', $properties) or
			($e['secured'] and $updatePayments) or
			($e['secured'] and in_array('paidAt', $properties)) or
			($e['secured'] and $updatePreparationStatus)
		) {
			self::recalculate($e);
		}

		// À faire obligatoirement après recalculate()
		if(in_array('deliveredAt', $properties) and $e->isComposition()) {
			self::reorderComposition($e);
		}

		Sale::model()->commit();

	}

	public static function updateNeverPaid(Sale $e): void {

		$e['cPayment'] = new \Collection();
		$e['paymentStatus'] = Sale::NEVER_PAID;
		$e['paidAt'] = NULL;

		self::update($e, ['paymentMethod', 'paymentStatus', 'paidAt']);

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

		if($newStatus === Sale::DELIVERED) {
			$c->first()['farm']->validateLegal();
		}

		Sale::model()->beginTransaction();

		foreach($c as $e) {
			self::updatePreparationStatus($e, $newStatus);
		}

		Sale::model()->commit();

	}

	public static function updatePaymentMethodCollection(\Collection $c, \payment\Method $eMethod): void {

		foreach($c as $e) {

			if(
				($e['cPayment']->empty() and $eMethod->empty()) or
				($e['cPayment']->notEmpty() and $eMethod->notEmpty() and in_array($eMethod['id'], $e['cPayment']->getColumnCollection('method')->getIds()))
			) {
				continue;
			}

			Sale::model()->beginTransaction();

			$e['cPayment'] = new \Collection();

			if($eMethod->notEmpty()) {

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

			}

			self::update($e, ['paymentMethod', 'paymentStatus']);

			Sale::model()->commit();

		}

	}

	public static function deletePayment(Sale $e): void {

		$e['cPayment'] = new \Collection();
		$e['paymentStatus'] = NULL;

		self::update($e, ['paymentMethod', 'paymentStatus']);

	}

	public static function updatePaymentStatusCollection(\Collection $c, string $paymentStatus): void {

		$properties = ['paymentStatus'];

		if($paymentStatus === Sale::PAID) {
			$properties[] = 'paidAt';
		}

		foreach($c as $e) {

			$e['paymentStatus'] = $paymentStatus;

			if($paymentStatus === Sale::PAID) {
				$e['paidAt'] = currentDate();
			}

			self::update($e, $properties);

		}

	}

	public static function updateReadyForAccountingCollection(\Collection $c, ?bool $value): void {

		Sale::model()->beginTransaction();

		Sale::model()
			->whereId('IN', $c->getIds())
			->update(['readyForAccounting' => $value]);

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

			PaymentLib::deleteBySale($e);

		}

		Sale::model()->commit();

	}

	public static function getItemsForDocument(Sale $e, string $type): \Collection {

		if($type === Pdf::DELIVERY_NOTE) {
			Item::model()->whereNature(Item::GOOD);
		}

		return self::getItemsBySales(new \Collection([$e]));

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

				if($eItem['composition']->notEmpty()) {

					if($public === FALSE or $eItem['product']['compositionVisibility'] === Product::PUBLIC) {
						$cItemIngredient[$eItem['id']] = new \Collection();
						$cItemMain[$key]['cItemIngredient'] = $cItemIngredient[$eItem['id']];
					} else {
						$cItemMain[$key]['composition'] = new Sale();
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

		$e->expects([
			'farm' => ['legalCountry'],
			'discount', 'taxes', 'shippingVatRate', 'shippingVatFixed'
		]);

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
				'crc32' => NULL,
				'nature' => NULL,
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

			if($e['secured']) {
				\securing\SignatureLib::signSale($e, $cItem);
			}

			return;

		}

		$vatList = [];

		$hash = '';

		$newValues = [
			'items' => $cItem->count(),
			'nature' => NULL,
			'vat' => 0.0,
			'vatByRate' => [],
			'organic' => FALSE,
			'priceIncludingVat' => 0.0,
			'priceExcludingVat' => 0.0,
		];

		if($e['shippingVatFixed'] === FALSE) {

			$newValues += [
				'shippingVatRate' => ($e['shipping'] === NULL) ? NULL : SellingSetting::getStandardVatRate($e['farm']),
			];

		}

		// Add items
		foreach($cItem as $eItem) {

			$hash .= json_encode($eItem->extracts(['name', 'additional', 'origin', 'nature', 'packaging', 'unit', 'unitPrice', 'unitPriceInitial', 'discount', 'number', 'price', 'vatRate'], fn($value) => ($value instanceof \Element) ? ($value['id'] ?? NULL) : $value));

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

			if($eItem['nature'] !== Sale::MIXED) {

				if($eItem['nature'] === Item::GOOD) {
					$newValues['nature'] = ($newValues['nature'] === Sale::SERVICE) ? Sale::MIXED : Sale::GOOD;
				} else{
					$newValues['nature'] = ($newValues['nature'] === Sale::GOOD) ? Sale::MIXED : Sale::SERVICE;
				}

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
				$eConfiguration = \farm\ConfigurationLib::getByFarm($e['farm']);

				if($eConfiguration['defaultVatShipping'] !== NULL) {
					$newValues['shippingVatRate'] = SellingSetting::getVatRate($e['farm'], $eConfiguration['defaultVatShipping']);
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

		$hash .= json_encode($newValues);

		$newValues['crc32'] = crc32($hash);

		// Vérification pour la comptabilité
		if(self::isReadyForAccounting($e)) {
			$newValues['readyForAccounting'] = TRUE;
		}

		$e->merge($newValues);

		Sale::model()
			->select(array_keys($newValues))
			->update($e);

		if($e['secured']) {
			\securing\SignatureLib::signSale($e, $cItem);
		}

	}

	public static function isReadyForAccounting(Sale $eSale): bool {

		if(
			$eSale['closed'] === FALSE or
			$eSale['accountingHash'] !== NULL or
			$eSale['profile'] === Sale::SALE_MARKET or
			$eSale['readyForAccounting'] === TRUE or // On l'a déjà setté
			$eSale['invoice']->notEmpty()  // On doit passer par la facture
		) {
			return FALSE;
		}

		if($eSale['profile'] === Sale::MARKET) {

			if($eSale['preparationStatus'] !== Sale::DELIVERED or $eSale['closed'] !== TRUE) {
				return FALSE;
			}

			$cSaleMarket = Sale::model()
				->select([
					'id',
					'cPayment' => Payment::model()
						->select(Payment::getSelection())
						->or(
						fn() => $this->whereOnlineStatus(NULL),
						fn() => $this->whereOnlineStatus(Payment::SUCCESS)
					)
					->delegateCollection('sale'),
				])
				->whereMarketParent($eSale)
				->wherePreparationStatus(Sale::DELIVERED)
				->getCollection();

			$payments = $cSaleMarket->getColumnCollection('cPayment');
			foreach($payments as $cPayment) {
				if($cPayment->empty()) {
					return FALSE;
				}
			}

			if(Item::model()
				->whereSale('IN', $cSaleMarket)
				->whereAccount(NULL)
				->count() > 0) {
				return FALSE;
			}

		} else {
			if(Payment::model()
				->whereSale($eSale)
				->or(
					fn() => $this->whereOnlineStatus(NULL),
					fn() => $this->whereOnlineStatus(Payment::SUCCESS)
				)
				->count() === 0) {
				return FALSE;
			}
			if(Item::model()
				->whereSale($eSale)
				->whereAccount(NULL)
				->count() > 0) {
				return FALSE;
			}
		}

		return TRUE;
	}

	public static function closeMarket(Sale $eSale): void {

		Sale::model()
			->whereFarm($eSale['farm'])
			->whereMarketParent($eSale)
			->whereSecured(FALSE)
			->update([
				'secured' => TRUE,
				'securedAt' => new \Sql('NOW()'),
			]);

		Sale::model()
			->whereFarm($eSale['farm'])
			->whereMarketParent($eSale)
			->whereClosed(FALSE)
			->update([
				'closed' => TRUE,
				'closedAt' => new \Sql('NOW()'),
				'closedBy' => \user\ConnectionLib::getOnline()
			]);

	}

	public static function close(Sale $eSale): void {

		$properties = ['closed', 'closedAt', 'closedBy'];

		$eSale['closed'] = TRUE;
		$eSale['closedAt'] = new \Sql('NOW()');
		$eSale['closedBy'] = \user\ConnectionLib::getOnline();

		if($eSale->acceptSecuring()) {

			$eSale['secured'] = TRUE;
			$eSale['securedAt'] = new \Sql('NOW()');

			$properties[] = 'secured';
			$properties[] = 'securedAt';

		}

		Sale::model()
			->select($properties)
			->whereClosed(FALSE)
			->update($eSale);

	}

	public static function autoClosing(): void {

		// Clôture des caisses
		$cSale = Sale::model()
			->select(Sale::getSelection())
			->join(\farm\Configuration::model(), 'm1.farm = m2.farm')
			->whereProfile(Sale::MARKET)
			->wherePreparationStatus(Sale::SELLING)
			->where(new \Sql('GREATEST(COALESCE(DATE(statusAt), "0000-00-00"), COALESCE(deliveredAt, "0000-00-00")) < NOW() - INTERVAL m2.saleClosing DAY'))
			->getCollection();

		foreach($cSale as $eSale) {
			MarketLib::close($eSale);
		}

	}

}
?>
