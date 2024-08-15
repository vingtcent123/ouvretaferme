<?php
namespace selling;

class ProductLib extends ProductCrud {

	public static function getPropertiesCreate(): array {
		return array_merge(['unit'], self::getPropertiesWrite());
	}

	public static function getPropertiesUpdate(): array {
		return array_merge(self::getPropertiesWrite(), ['privateStep', 'proStep']);
	}

	public static function getPropertiesWrite(): array {
		return ['name', 'category', 'variety', 'size', 'description', 'quality', 'plant', 'pro', 'proPrice', 'proPackaging', 'private', 'privatePrice', 'vat'];
	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type, ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			Product::model()->whereId(substr($query, 1));

		} else if($query !== '') {

			Product::model()
				->where('
					name LIKE '.Product::model()->format('%'.$query.'%').'
				')
				->sort([
					new \Sql('
						IF(
							name LIKE '.Product::model()->format($query.'%').',
							2,
							IF(
								name LIKE '.Product::model()->format('%'.$query.'%').',
								1, 0
							)
						) DESC'),
					'name' => SORT_ASC
				]);

		} else {
			Product::model()->sort('name');
		}

		switch($type) {
			case Customer::PRO :
				Product::model()->wherePro(TRUE);
				break;
			case Customer::PRIVATE :
				Product::model()->wherePrivate(TRUE);
				break;
		}

		return Product::model()
			->select($properties ?: Product::getSelection())
			->whereFarm($eFarm)
			->whereStatus(Product::ACTIVE)
			->getCollection();

	}

	public static function getByPlant(\plant\Plant $ePlant): \Collection {

		return Product::model()
			->select(Product::getSelection())
			->wherePlant($ePlant)
			->sort('name')
			->getCollection();

	}

	public static function countByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): array {

		self::applySearch($eFarm, $search);

		return Product::model()
			->select([
				'category',
				'count' => new \Sql('COUNT(*)', 'int')
			])
			->whereFarm($eFarm)
			->group('category')
			->getCollection()
			->toArray(fn($eProduct) => [$eProduct['category']->empty() ? NULL : $eProduct['category']['id'], $eProduct['count']], TRUE);

	}

	public static function getByFarm(\farm\Farm $eFarm, ?Category $eCategory = NULL, bool $selectSales = FALSE, \Search $search = new \Search()): \Collection {

		if($selectSales) {

			AnalyzeLib::filterItemStats();

			Product::model()
				->select([
					'eItemTotal' => Item::model()
						->select([
							'product',
							'all' => new \Sql('SUM(priceExcludingVat)', 'float'),
							'year' => new \Sql('SUM(IF(EXTRACT(YEAR FROM deliveredAt) = '.date('Y').', priceExcludingVat, 0))', 'float'),
							'yearBefore' => new \Sql('SUM(IF(EXTRACT(YEAR FROM deliveredAt) = '.(date('Y') - 1).', priceExcludingVat, 0))', 'float'),
						])
						->where(new \Sql('EXTRACT(YEAR FROM deliveredAt)'), 'IN', [(int)date('Y'), (int)date('Y') - 1])
						->group(['product'])
						->delegateElement('product')
				]);

		}

		self::applySearch($eFarm, $search);

		$search->validateSort(['name', 'id', 'stockUpdatedAt']);

		return Product::model()
			->select(Product::getSelection())
			->whereCategory($eCategory, if: $eCategory !== NULL)
			->whereFarm($eFarm)
			->sort($search->buildSort())
			->getCollection();

	}

	public static function applySearch(\farm\Farm $eFarm, \Search $search): void {

		if($search->get('name')) {
			Product::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		if($search->get('stock')) {
			Product::model()->whereStock('!=', NULL);
		}

		if($search->get('plant')) {
			$cPlant = \plant\PlantLib::getFromQuery($search->get('plant'), $eFarm);
			Product::model()->wherePlant('IN', $cPlant);
		}

	}

	public static function getForDate(\shop\Date $eDate): \Collection {

		$eDate->expects(['farm', 'type']);

		return Product::model()
			->select(Product::getSelection())
			->whereFarm($eDate['farm'])
			->where($eDate['type'], TRUE)
			->whereStatus(Product::ACTIVE)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function getByCustomer(Customer $e, bool $onlyWithPrice = FALSE): \Collection {

		$e->expects(['farm']);

		$cProduct = Product::model()
			->select([
				'id', 'name', 'variety', 'vignette', 'size', 'unit',
				'privatePrice', 'privateStep',
				'proPrice', 'proPackaging', 'proStep',
				'vat',
				'eGrid' => Grid::model()
					->select(['id', 'price', 'packaging'])
					->whereCustomer($e)
					->delegateElement('product')
			])
			->whereFarm($e['farm'])
			->whereStatus(Product::ACTIVE)
			->sort(['name' => SORT_ASC])
			->getCollection();

		if($onlyWithPrice) {
			$cProduct->filter(fn($eProduct) => $eProduct['eGrid']->notEmpty());
		}

		return $cProduct;

	}

	public static function getForReport(\plant\Plant $ePlant, string $firstSale, string $lastSale): \Collection {

		AnalyzeLib::filterItemStats();

		return Product::model()
			->select(Product::getSelection() + [
				'sales' => Item::model()
					->select([
						'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
						'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
					])
					->whereDeliveredAt('BETWEEN', new \Sql(Item::model()->format($firstSale).' AND '.Item::model()->format($lastSale)))
					->group('product')
					->delegateElement('product')
			])
			->wherePlant($ePlant)
			->sort('name')
			->getCollection();

	}

	public static function delete(Product $e): void {

		$e->expects(['id']);

		if(Item::model()
			->whereProduct($e)
			->exists()) {
			Product::fail('deletedSaleUsed');
			return;
		}

		if(\shop\Product::model()
			->whereProduct($e)
			->exists()) {
			Product::fail('deletedShopUsed');
			return;
		}

		Product::model()->beginTransaction();

		Grid::model()
			->whereProduct($e)
			->delete();

		Product::model()->delete($e);

		Product::model()->commit();

	}

}
?>
