<?php
namespace selling;

class SaleLib extends SaleCrud {

	public static function getPropertiesCreate(): \Closure {

		return function(Sale $e) {

			return $e['compositionOf']->empty() ?
				['market', 'customer', 'shopDate', 'deliveredAt', 'productsList', 'shipping'] :
				['market', 'customer', 'deliveredAt', 'productsList'];

		};

	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Sale $e) {

			$e->expects(['preparationStatus']);

			$properties = ['comment'];

			if($e->isClosed() === FALSE) {

				$properties[] = 'deliveredAt';

				if($e->isComposition() === FALSE) {

					if($e->hasDiscount()) {
						$properties[] = 'discount';
					}

					if($e->hasShipping()) {
						$properties[] = 'shipping';

						if($e['hasVat']) {
							$properties[] = 'shippingVatRate';
						}
					}

				}

			}

			return $properties;

		};

	}

	public static function getExample(\farm\Farm $eFarm, string $type, \shop\Shop $eShop = new \shop\Shop()): Sale {

		$id = match($type) {
			Customer::PRO => \Setting::get('selling\exampleSalePro'),
			Customer::PRIVATE => \Setting::get('selling\exampleSalePrivate')
		};

		$eSale = \selling\SaleLib::getById($id);
		$eSale['document'] = '123';
		$eSale['farm'] = $eFarm;
		$eSale['hasVat'] = $eFarm->getSelling('hasVat');
		$eSale['customer']['legalName'] = match($type) {
			Customer::PRO => 'Magasin ABC',
			Customer::PRIVATE => 'A. Bécé'
		};
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
		$eSale['invoice']['name'] = $eFarm->getSelling('invoicePrefix').'123';
		$eSale['invoice']['priceExcludingVat'] = $eSale['priceExcludingVat'];
		$eSale['invoice']['priceIncludingVat'] = $eSale['priceIncludingVat'];
		$eSale['invoice']['date'] = currentDate();
		$eSale['invoice']['paymentCondition'] = $eFarm->getSelling('invoicePaymentCondition');
		$eSale['invoice']['header'] = $eFarm->getSelling('invoiceHeader');
		$eSale['invoice']['footer'] = $eFarm->getSelling('invoiceFooter');
		$eSale['invoice']['customer'] = $eSale['customer'];
		$eSale['cItem'] = self::getItems($eSale);

		$position = 0;
		foreach($eSale['cItem'] as $eItem) {
			$eItem['name'] = 'Produit '.(++$position);
		}

		if($eShop->notEmpty()) {

			$eShop->expects(['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']);

			$eSale['shop'] = $eShop;
			$eSale['shopDate'] = new \shop\Shop([
				'deliveryDate' => currentDate(),
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
			->sort(new \Sql('IF(lastName IS NULL, name, lastName), firstName, m1.id'));

		return self::getForLabels($eFarm, $selectItems);
	}

	public static function getForLabelsByDate(\shop\Date $eDate, bool $selectItems = FALSE, bool $selectPoint = FALSE): \Collection {

		Sale::model()
			->whereShopDate($eDate)
			->wherePreparationStatus('IN', [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED])
			->sort(new \Sql('shopPoint, IF(lastName IS NULL, name, lastName), firstName, m1.id'));


		return self::getForLabels($eDate['farm'], $selectItems, $selectPoint);

	}

	public static function fillItems(\Collection $cSale): void {

		Sale::model()
			->select([
				'cItem' => Item::model()
					->select(Item::getSelection())
					->sort([
						'name' => SORT_ASC,
						'id' => SORT_ASC
					])
					->delegateCollection('sale')
			])
			->get($cSale);

	}

	private static function getForLabels(\farm\Farm $eFarm, bool $selectItems = FALSE, bool $selectPoint = FALSE): \Collection {

		if($selectPoint) {
			Sale::model()->select([
				'shopPoint' => \shop\Point::getSelection()
			]);
		}

		$cSale = Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select(Sale::getSelection())
			->where('m1.farm', $eFarm)
			->getCollection();

		if($selectItems) {
			self::fillItems($cSale);
		}

		return $cSale;

	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL, ?int $position = NULL, ?int $number = NULL, \Search $search = new \Search()): array {

		if($search->get('customerName')) {
			$cCustomer = CustomerLib::getFromQuery($search->get('customerName'), $eFarm);
			Sale::model()->whereCustomer('IN', $cCustomer);
		}

		if($search->get('invoicing')) {
			Sale::model()
				->whereInvoice(NULL)
				->whereMarket(FALSE)
				->whereItems('>', 0)
				->wherePreparationStatus(Sale::DELIVERED);
		}

		$search->validateSort(['id', 'firstName', 'lastName', 'deliveredAt', 'items', 'priceExcludingVat', 'preparationStatus'], 'preparationStatus-');

		$sort = 'FIELD(preparationStatus, "'.Sale::SELLING.'", "'.Sale::DRAFT.'", "'.Sale::CONFIRMED.'", "'.Sale::PREPARED.'", "'.Sale::DELIVERED.'", "'.Sale::CANCELED.'")';

		if(str_starts_with($search->getSort(), 'firstName') or str_starts_with($search->getSort(), 'lastName')) {
			Sale::model()->join(Customer::model(), 'm1.customer = m2.id');
		}

		$cSale = Sale::model()
			->select(Sale::getSelection())
			->select([
				'cPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->delegateCollection('sale', 'type')
			])
			->option('count')
			->where('m1.id', 'NOT IN', $search->get('notId'), if: $search->get('notId')?->notEmpty())
			->whereDocument($search->get('document'), if: $search->get('document'))
			->where('m1.id', 'IN', fn() => explode(',', $search->get('ids')), if: $search->get('ids'))
			->where('m1.farm', $eFarm)
			->whereType($type, if: $type !== NULL)
			->whereCustomer($search->get('customer'), if: $search->get('customer'))
			->whereDeliveredAt('LIKE', '%'.$search->get('deliveredAt').'%', if: $search->get('deliveredAt'))
			->whereDeliveredAt('>', new \Sql('CURDATE() - INTERVAL '.Sale::model()->format($search->get('delivered')).' DAY'), if: $search->get('delivered'))
			->wherePreparationStatus($search->get('preparationStatus'), if: $search->get('preparationStatus'))
			->wherePreparationStatus('!=', Sale::COMPOSITION)
			->wherePaymentMethod($search->get('paymentMethod'), if: $search->get('paymentMethod'))
			->whereMarketParent(NULL)
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
			->whereMarketParent(NULL)
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
			->sort(new \Sql('FIELD(preparationStatus, "'.Sale::CONFIRMED.'", "'.Sale::PREPARED.'") DESC, id DESC'))
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
			->whereMarketParent(NULL)
			->sort('id')
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByDate(
		\shop\Date $eDate,
		?array $preparationStatus = [Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED],
		?array $select = NULL,
		mixed $sort = new \Sql('shopPoint ASC, IF(lastName IS NULL, name, lastName), firstName, m1.id')
	): \Collection {

		return Sale::model()
			->join(Customer::model(), 'm1.customer = m2.id')
			->select($select ?? Sale::getSelection())
			->whereShopDate($eDate)
			->wherePreparationStatus('IN', $preparationStatus, if: empty($preparationStatus) === FALSE)
			->sort($sort)
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
			->select(SaleElement::getSelection())
			->select([
				'cPdf' => Pdf::model()
					->select(Pdf::getSelection())
					->delegateCollection('sale', 'type')
			])
			->whereCustomer($eCustomer)
			->whereId('IN', $ids)
			->whereItems('>', 0)
			->whereInvoice(NULL, if: $checkInvoice)
			->whereMarket(FALSE)
			->whereMarketParent(NULL)
			->wherePreparationStatus(Sale::DELIVERED)
			->sort(['id' => SORT_ASC])
			->getCollection();

		self::fillItems($cSale);

		return $cSale;

	}

	public static function getForMonthlyInvoice(\farm\Farm $eFarm, string $month, ?string $type): \Collection {

		return Sale::model()
			->select([
				'customer' => ['type', 'name'],
				'hasVat', 'taxes',
				'priceExcludingVat' => new \Sql('SUM(priceExcludingVat)', 'float'),
				'priceIncludingVat' => new \Sql('SUM(priceIncludingVat)', 'float'),
				'number' => new \Sql('COUNT(*)'),
				'list' => new \Sql('GROUP_CONCAT(id ORDER BY id SEPARATOR ",")')
			])
			->whereFarm($eFarm)
			->whereType($type, if: in_array($type, [Customer::PRIVATE, Customer::PRO]))
			->wherePaymentMethod(Sale::TRANSFER, if: $type === Sale::TRANSFER)
			->whereDeliveredAt('LIKE', $month.'%')
			->whereInvoice(NULL)
			->whereMarket(FALSE)
			->whereMarketParent(NULL)
			->wherePreparationStatus(Sale::DELIVERED)
			->group(['customer', 'taxes', 'hasVat'])
			->getCollection()
			->sort(['customer' => ['name']]);

	}

	public static function getByCustomers(\Collection $cCustomer, ?int $limit = 10): \Collection {

		if($cCustomer->empty()) {
			return new \Collection();
		}

		return Sale::model()
			->select(Sale::getSelection())
			->whereCustomer('IN', $cCustomer)
			->sort([
				'id' => SORT_DESC
			])
			->getCollection(0, $limit);

	}

	public static function getByParent(Sale $eSale, bool $indexByStatus = TRUE): \Collection {

		$ccSale = Sale::model()
			->select(Sale::getSelection() + [
				'createdBy' => ['firstName', 'lastName', 'vignette']
			])
			->whereFarm($eSale['farm']['id'])
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

		if($eProduct['composition'] === FALSE) {
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
			'type', 'taxes', 'hasVat', 'from', 'compositionOf',
			'customer',
		]);

		Sale::model()->beginTransaction();

		// Nouvelle composition de produit
		if($e->isComposition()) {

			$e['preparationStatus'] = Sale::COMPOSITION;
			$e['market'] = FALSE;
			$e['stats'] = FALSE;

		} else {
			$e->expects(['market']);
		}

		if($e['market']) {
			$e['marketSales'] = 0;
			$e['paymentStatus'] = Sale::UNDEFINED;
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
		if(($e['cItem'] ?? new \Collection())->notEmpty()) {
			\selling\ItemLib::createCollection($e['cItem']);
		}


		if($e->isComposition()) {
			self::reorderComposition($e);
		}

		Sale::model()->commit();

	}

	/**
	 * Modifie ou supprime une composition existante
	 */
	public static function reorderComposition(Sale $e): void {

		$cSale = Sale::model()
			->select('id', 'compositionEndAt', 'deliveredAt')
			->whereCompositionOf($e['compositionOf'])
			->where('compositionEndAt IS NULL OR compositionEndAt >= CURDATE() - INTERVAL '.(\Setting::get('compositionLocked') + 1).' DAY')
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

		$eSale->expects(['id', 'farm', 'market']);

		if($eSale['market'] === FALSE) {
			throw new \Exception('Invalid sale');
		}

		$e = new Sale();

		$e['customer'] = new Customer();
		$e['compositionOf'] = new Product();
		$e['farm'] = $eSale['farm'];
		$e['from'] = Sale::USER;
		$e['type'] = Customer::PRIVATE;
		$e['taxes'] = $e->getTaxesFromType();
		$e['hasVat'] = $e['farm']->getSelling('hasVat');
		$e['deliveredAt'] = $eSale['deliveredAt'];
		$e['market'] = FALSE;
		$e['marketParent'] = $eSale;
		$e['stats'] = FALSE;

		self::create($e);

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
			throw new NotExpectedAction('Can duplicate');
		}

		Sale::model()->beginTransaction();

		// Créer une nouvelle vente
		$eSaleNew = new Sale($eSale->extracts($properties));
		$eSaleNew['preparationStatus'] = Sale::DRAFT;
		$eSaleNew['paymentStatus'] = Sale::UNDEFINED;
		$eSaleNew['paymentMethod'] = NULL;

		if($eSaleNew['market']) {
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

			if($eSaleNew['market']) {

				unset($eItem['price'], $eItem['priceExcludingVat']);

			}

			unset($eItem['id'], $eItem['createdAt']);

		}

		Item::model()->insert($cItem);

		Sale::model()->commit();

		return $eSaleNew;

	}

	public static function associateShop(Sale $e, array $input): void {

		$fw = new \FailWatch();

		$e->build(['shopDate'], $input, for: 'update');

		$fw->validate();

		if($e['shopDate']->empty()) {
			return;
		}

		self::update($e, ['from', 'shop', 'shopDate']);

	}

	public static function dissociateShop(Sale $e): void {

		$e->build(['shopDate'], [], for: 'update');

		$properties = ['from', 'shop', 'shopDate'];

		if($e['preparationStatus'] === Sale::BASKET) {

			$e['oldStatus'] = Sale::BASKET;
			$e['preparationStatus'] = Sale::DRAFT;

			$properties[] = 'preparationStatus';

		}

		self::update($e, $properties);

	}

	public static function update(Sale $e, array $properties): void {

		Sale::model()->beginTransaction();

		$updatePreparationStatus = (
			in_array('preparationStatus', $properties) and
			$e->expects(['oldStatus', 'marketParent']) and
			($e['oldStatus'] !== $e['preparationStatus'])
		);

		if(in_array('shippingVatRate', $properties)) {

			$e['shippingVatFixed'] = ($e['shippingVatRate'] !== NULL);
			$properties[] = 'shippingVatFixed';

		}

		if(
			in_array('shopDate', $properties) and
			$e['shopDate']->notEmpty()
		) {

			$e['shopDate']->expects('deliveryDate');

			$properties[] = 'deliveredAt';
			$e['deliveredAt'] = $e['shopDate']['deliveryDate'];

		}

		if($updatePreparationStatus) {

			$properties[] = 'statusDeliveredAt';
			$e['statusDeliveredAt'] = ($e['preparationStatus'] === Sale::DELIVERED) ? new \Sql('NOW()') : NULL;

		}

		parent::update($e, $properties);

		$newItems = [];

		if($updatePreparationStatus) {

			if($e['oldStatus'] === Sale::DELIVERED) {
				HistoryLib::createBySale($e, 'sale-delivered-cancel');
			} else {
				HistoryLib::createBySale($e, 'sale-'.$e['preparationStatus']);
			}

			$newItems['status'] = $e['preparationStatus'];

			if($e['preparationStatus'] === Sale::SELLING) {

				MarketLib::updateSaleMarket($e);

			}

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

		if($newItems) {

			Item::model()
				->whereSale($e)
				->update($newItems);

		}

		if($updatePreparationStatus) {

			// Mise à jour des ingrédients au changement de statut sur les produits composés
			self::updateIngredients($e);

			if($e['marketParent']->notEmpty()) {
				MarketLib::updateSaleMarket($e['marketParent']);
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

	public static function updatePreparationStatusCollection(\Collection $c, string $newStatus): void {

		Sale::model()->beginTransaction();

		foreach($c as $e) {

			if($e['preparationStatus'] === $newStatus) {
				continue;
			}

			$e['oldStatus'] = $e['preparationStatus'];
			$e['preparationStatus'] = $newStatus;

			self::update($e, ['preparationStatus']);

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

		Sale::model()->commit();

	}

	public static function deleteCollection(\Collection $cSale): void {

		foreach($cSale as $eSale) {
			self::delete($eSale);
		}

	}

	public static function delete(Sale $e): void {

		$e->expects([
			'id',
			'shopDate',
			'preparationStatus',
			'market', 'marketParent'
		]);

		Sale::model()->beginTransaction();

		$deleted = Sale::model()
			->wherePreparationStatus('IN', $e->getDeleteStatuses())
			->wherePaymentStatus('IN', $e->getDeletePaymentStatuses())
			->delete($e);

		if($deleted > 0) {

			Item::model()
				->whereSale($e)
				->delete();

			if($e['market']) {

				Sale::model()
					->whereFarm($e['farm'])
					->whereMarketParent($e)
					->update([
						'marketParent' => NULL
					]);

			}

			if($e['marketParent']->notEmpty()) {
				MarketLib::updateSaleMarket($e['marketParent']);
			}

			if($e->isComposition()) {
				self::reorderComposition($e);
			}

		}

		Sale::model()->commit();

	}

	public static function getItems(Sale $e, bool $withIngredients = FALSE, bool $public = FALSE, ?string $index = NULL): \Collection {

		$cItem = Item::model()
			->select(Item::getSelection())
			->whereSale($e)
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

		$isDelivered = ($cItem->first()['status'] === Sale::DELIVERED);

		if($isDelivered) {

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

		} else {

			$deliveredAt = $cItem->first()['deliveredAt'];

			$cItemComposition = new \Collection();

			foreach($cItem as $eItem) {

				if($eItem['productComposition']) {

					if($public === FALSE or $eItem['product']['compositionVisibility'] === Product::PUBLIC) {
						$cItemComposition[] = $eItem;
					} else {
						$eItem['productComposition'] = FALSE;
					}

				}

			}

			Item::model()
				->select([
					'cItemIngredient' => self::delegateIngredients($deliveredAt, 'product')
				])
				->get($cItemComposition);

			return $cItem;

		}

	}

	public static function delegateIngredients(string $deliveredAt, string $propertyParent) {

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
			->delegateProperty('compositionOf', 'cItem', propertyParent: $propertyParent);

	}

	public static function updateIngredients(Sale $e): void {

		Item::model()
			->whereSale($e)
			->whereIngredientOf('!=', NULL)
			->delete();

		if($e['preparationStatus'] === Sale::DELIVERED) {

			$cItemComposition = Item::model()
				->select(ItemElement::getSelection() + [
					'cItemIngredient' => self::delegateIngredients($e['deliveredAt'], 'product')
				])
				->whereSale($e)
				->whereProductComposition(TRUE)
				->getCollection();

			$cItemIngredient = new \Collection();

			foreach($cItemComposition as $eItemComposition) {

				if($eItemComposition['cItemIngredient']->empty()) {
					continue;
				}

				$price = $eItemComposition['price'];
				$ingredientsPrice = $eItemComposition['cItemIngredient']->sum('price');

				$ratio = $price / $ingredientsPrice;

				$cItemCopy = $eItemComposition['cItemIngredient'];

				foreach($cItemCopy as $eItemCopy) {

					$eItemIngredient = (clone $eItemComposition);
					$eItemIngredient->merge([
					  'id' => NULL,
					  'name' => $eItemCopy['name'],
					  'product' => $eItemCopy['product'],
					  'productComposition' => FALSE,
					  'ingredientOf' => $eItemComposition,
					  'quality' => $eItemCopy['quality'],
					  'parent' => $eItemCopy['parent'],
					  'packaging' => $eItemCopy['packaging'],
					  'unit' => $eItemCopy['unit'],
					  'unitPrice' => $eItemCopy['unitPrice'] * $ratio,
					  'number' => $eItemCopy['number'],
					  'price' => $eItemCopy['price'] * $ratio,
					  'priceExcludingVat' => $eItemCopy['priceExcludingVat'] * $ratio,
					  'vatRate' => $eItemCopy['vatRate'],
					  'stats' => $eItemComposition['stats']
					]);

					$cItemIngredient[] = $eItemIngredient;


				}

			}

			Item::model()->insert($cItemIngredient);

		}

	}

	public static function countItems(Sale $e): int {

		return Item::model()
			->whereSale($e)
			->count();

	}

	/**
	 * Recalculer la TVA et les prix de la vente en fonction des items
	 */
	public static function recalculate(Sale $e): void {

		$e->expects(['farm', 'taxes', 'shippingVatRate', 'shippingVatFixed']);

		$cItem = Item::model()
			->select(['price', 'vatRate', 'quality'])
			->whereSale($e)
			->getCollection();

		// Plus rien dans la vente
		if($cItem->empty()) {

			$newValues = [
				'items' => 0,
				'vat' => NULL,
				'vatByRate' => NULL,
				'organic' => FALSE,
				'priceIncludingVat' => NULL,
				'priceExcludingVat' => NULL,
			];

			Sale::model()->update($e, $newValues);

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
				'shippingVatRate' => ($e['shipping'] === NULL) ? NULL : \Setting::get('defaultVatRate'),
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

		if($e['shipping'] !== NULL) {

			if($e['shippingVatFixed'] === FALSE) {

				// On écrase le taux de TVA calculé
				$eConfiguration = \selling\ConfigurationLib::getByFarm($e['farm']);

				if($eConfiguration['defaultVatShipping'] !== NULL) {
					$newValues['shippingVatRate'] = \Setting::get('selling\vatRates')[$eConfiguration['defaultVatShipping']];
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

		Sale::model()->update($e, $newValues);

	}

	public static function getDefaultVat(\farm\Farm $eFarm): int {
		return 2;
	}

	public static function getVatRate(int $vat): int {
		return \Setting::get('selling\vatRates')[$vat];
	}

	public static function getVatRates(\farm\Farm $eFarm): array {

		// A filtrer selon les pays le cas échéant

		return \Setting::get('selling\vatRates');

	}

}
?>
