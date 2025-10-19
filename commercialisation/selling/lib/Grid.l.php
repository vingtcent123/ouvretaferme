<?php
namespace selling;

class GridLib extends GridCrud {

	public static function getPropertiesCreate(): array {
		return ['customer', 'group', 'product', 'price', 'priceDiscount'];
	}

	public static function getPropertiesUpdate(): array {
		return ['price', 'priceDiscount'];
	}

	public static function getByProduct(Product $eProduct): \Collection {

		$eProduct->expects(['id']);

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->whereProduct($eProduct)
			->getCollection();

		$cGrid->sort(['customer' => ['name']]);

		return $cGrid;

	}

	public static function getByGroup(Group $eGroup, mixed $index = NULL): \Collection {

		$eGroup->expects(['id']);

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->whereGroup($eGroup)
			->getCollection(index: $index);

		$cGrid->sort(['product' => ['name']]);

		return $cGrid;

	}

	public static function getByGroups(\Collection|array $cGroup): \Collection {

		return Grid::model()
			->select(Grid::getSelection())
			->whereGroup('IN', $cGroup)
			->sort(['price' => SORT_ASC, 'id' => SORT_ASC])
			->getCollection()
			->sort([
				'product' => ['name']
			]);

	}

	public static function getByCustomer(Customer $eCustomer, mixed $index = NULL): \Collection {

		$eCustomer->expects(['id']);

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->whereCustomer($eCustomer)
			->getCollection(index: $index);

		$cGrid->sort(['product' => ['name']]);

		return $cGrid;

	}

	public static function calculateByGroup(Group $eGroup, Product $eProduct = new Product()): \Collection|Grid {

		$eGroup->expects(['id']);

		Grid::model()->whereGroup($eGroup);

		return self::calculate($eProduct);

	}

	public static function calculateByCustomer(Customer $eCustomer, Product $eProduct = new Product()): \Collection|Grid {

		$eCustomer->expects(['id', 'groups']);

		if($eCustomer['groups']) {
			Grid::model()
				->or(
					fn() => $this->whereCustomer($eCustomer),
					fn() => $this->whereGroup('IN', $eCustomer['groups']),
				);
		} else {
			Grid::model()->whereCustomer($eCustomer);
		}

		return self::calculate($eProduct);

	}

	protected static function calculate(Product $eProduct = new Product()): \Collection|Grid {

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->whereProduct($eProduct, if: $eProduct->notEmpty())
			// Attention, le tri est inversé par getCollection() écrase les valeurs !
			// 1. La grille du client
			// 2. Sinon, le meilleur prix pour les grilles de groupe
			// 3. Sinon, par id ASC
			->sort(new \Sql('customer IS NOT NULL, price DESC, id DESC'))
			->getCollection(index: 'product');

		if($eProduct->notEmpty()) {
			return $cGrid->notEmpty() ? $cGrid->first() : new Grid();
		} else {
			return $cGrid;
		}

	}

	public static function create(Grid $e): void {

		try {
			parent::create($e);
		}
		catch(\Exception $e) {

			Grid::model()->rollBack();

			$duplicate = $e->getInfo()['duplicate'];

			if($duplicate === ['customer', 'product']) {
				Grid::fail('customer.duplicate');
			} else if($duplicate === ['group', 'product']) {
				Grid::fail('group.duplicate');
			}

		}

	}

	public static function update(Grid $e, array $properties): void {

		$properties[] = 'updatedAt';
		$e['updatedAt'] = new \Sql('NOW()');

		if(array_delete($properties, 'priceDiscount')) {
			$properties[] = 'priceInitial';
		}

		parent::update($e, $properties);

	}

	public static function updateGrid(\Collection $cGrid) {

		Grid::model()->beginTransaction();

		foreach($cGrid as $eGrid) {

			if($eGrid['price'] === NULL) {

				Grid::model()
					->whereCustomer($eGrid['customer'])
					->whereProduct($eGrid['product'])
					->delete();

			} else {

				try {

					Grid::model()->insert($eGrid);

				} catch(\DuplicateException) {

					Grid::model()
						->whereCustomer($eGrid['customer'])
						->whereProduct($eGrid['product'])
						->update([
							'price' => $eGrid['price'],
							'priceInitial' => $eGrid['priceInitial'] ?? NULL,
							'updatedAt' => new \Sql('NOW()')
						]);

				}

			}

		}

		Grid::model()->commit();

	}

	public static function deleteByGroup(Group $eGroup) {

		Grid::model()
			->whereGroup($eGroup)
			->delete();

	}

	public static function deleteByCustomer(Customer $eCustomer) {

		Grid::model()
			->whereCustomer($eCustomer)
			->delete();

	}

	public static function deleteByProduct(Product $eProduct) {

		Grid::model()
			->whereProduct($eProduct)
			->delete();

	}

}
?>
