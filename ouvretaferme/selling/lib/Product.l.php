<?php
namespace selling;

class ProductLib extends ProductCrud {

	public static function getPropertiesCreate(): \Closure {
		return fn($eProduct) => array_merge(['unit'], ProductLib::getPropertiesWrite($eProduct, 'create'));
	}

	public static function getPropertiesUpdate(): \Closure {

		return function(Product $eProduct) {

			$eProduct->expects(['cUnit']);

			$properties = [];

			if(
				$eProduct['unit']->empty() or
				$eProduct['unit']['approximate'] === FALSE
			) {
				$properties[] = 'unit';
			}

			return array_merge($properties, ProductLib::getPropertiesWrite($eProduct, 'update'));

		};

	}

	public static function getPropertiesWrite(Product $eProduct, string $for): array {

		if($eProduct['composition']) {

			$properties = ['name', 'category', 'description', 'quality', 'vat', 'compositionVisibility'];

			if($for === 'update') {

				if($eProduct['private']) {
					$properties = array_merge($properties, ['privatePrice', 'privateStep']);
				} else if($eProduct['pro']) {
					$properties = array_merge($properties, ['proPrice', 'proPackaging', 'proStep']);
				}

			} else if($for === 'create') {

				$properties = array_merge($properties, ['pro', 'proPrice', 'proPackaging', 'private', 'privatePrice', 'proOrPrivate']);

			}

			return $properties;

		} else {

			$properties = ['name', 'category', 'variety', 'size', 'origin', 'description', 'quality', 'plant', 'pro', 'proPrice', 'proPackaging', 'private', 'privatePrice', 'vat'];

			if($for === 'update') {
				$properties[] = 'privateStep';
				$properties[] = 'proStep';
			}

			return $properties;

		}

	}

	public static function getCompositionById(mixed $id, array $properties = []): Product {

		Product::model()->whereComposition(TRUE);

		return self::getById($id, $properties);

	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?string $type, ?string $stock, ?array $properties = []): \Collection {

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
			->whereStock(NULL, if: $stock === 'enable')
			->getCollection();

	}

	public static function getByPlant(\plant\Plant $ePlant, mixed $index = NULL): \Collection {

		return Product::model()
			->select(Product::getSelection())
			->wherePlant($ePlant)
			->sort('name')
			->getCollection(index: $index);

	}

	public static function countByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): array {

		self::applySearch($eFarm, $search);

		return Product::model()
			->select([
				'category',
				'count' => new \Sql('COUNT(*)', 'int')
			])
			->whereFarm($eFarm)
			->whereComposition(FALSE, if: $search->get('composition') === 'simple')
			->whereComposition(TRUE, if: $search->get('composition') === 'composed')
			->group('category')
			->getCollection()
			->toArray(fn($eProduct) => [$eProduct['category']->empty() ? NULL : $eProduct['category']['id'], $eProduct['count']], TRUE);

	}

	public static function getByFarm(\farm\Farm $eFarm, ?Category $eCategory = NULL, bool $selectSales = FALSE, \Search $search = new \Search()): \Collection {

		if($selectSales) {

			AnalyzeLib::filterItemStats();
			AnalyzeLib::filterItemComposition($eFarm);

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

		$search->validateSort(['name', 'id', 'stock', 'stockUpdatedAt']);

		return Product::model()
			->select(Product::getSelection())
			->whereCategory($eCategory, if: $eCategory !== NULL)
			->whereComposition(FALSE, if: $search->get('composition') === 'simple')
			->whereComposition(TRUE, if: $search->get('composition') === 'composed')
			->whereFarm($eFarm)
			->whereStatus('!=', Product::DELETED)
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

	public static function getForSale(\farm\Farm $eFarm, string $type, array $ids = [], bool $excludeComposition = FALSE): \Collection {

		return Product::model()
			->select(Product::getSelection())
			->whereFarm($eFarm)
			->whereId('IN', $ids, if: $ids)
			->where($type, TRUE)
			->whereStatus(Product::ACTIVE)
			->whereComposition(FALSE, if: $excludeComposition)
			->sort(['name' => SORT_ASC])
			->getCollection(NULL, NULL, 'id');

	}

	public static function applyItemsForSale(\Collection $cProduct, Sale $eSale): void {

		$eSale->expects(['farm', 'type', 'customer', 'discount']);

		if($eSale['compositionOf']->empty()) {
			$cGrid = \selling\GridLib::getByCustomer($eSale['customer'], index: 'product');
		} else {
			$cGrid = new \Collection();
		}

		foreach($cProduct as $eProduct) {
			$eProduct['item'] = \selling\ItemLib::getNew($eSale, $eProduct, $cGrid[$eProduct['id']] ?? new \selling\Grid());
		}

	}

	public static function getByCustomer(Customer $e, bool $onlyWithPrice = FALSE): \Collection {

		$e->expects(['farm']);

		$cProduct = Product::model()
			->select([
				'id', 'name', 'variety', 'vignette', 'farm', 'composition', 'size', 'origin',
				'unit' => \selling\Unit::getSelection(),
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

	public static function fillForReport(\farm\Farm $eFarm, \Collection $cProduct, string $firstSale, string $lastSale): void {

		AnalyzeLib::filterItemStats();
		AnalyzeLib::filterItemComposition($eFarm);

		Product::model()
			->select([
				'sales' => Item::model()
					->select([
						'turnover' => new \Sql('SUM(priceExcludingVat)', 'float'),
						'quantity' => new \Sql('SUM(IF(packaging IS NULL, 1, packaging) * number)', 'float'),
					])
					->whereDeliveredAt('BETWEEN', new \Sql(Item::model()->format($firstSale).' AND '.Item::model()->format($lastSale)))
					->group('product')
					->delegateElement('product')
			])
			->get($cProduct);

	}

	public static function delete(Product $e): void {

		$e->expects(['id']);

		Product::model()->beginTransaction();

			Grid::model()
				->whereProduct($e)
				->delete();

			StockLib::disable($e);

			if(
				Item::model()
					->whereProduct($e)
					->exists() or
				\shop\Product::model()
					->whereProduct($e)
					->exists()
			) {

				Product::model()->update($e, [
					'status' => Product::DELETED
				]);

			} else {

				Product::model()->delete($e);

			}

		Product::model()->commit();

	}

}
?>
