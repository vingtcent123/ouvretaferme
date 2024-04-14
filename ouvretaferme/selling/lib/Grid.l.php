<?php
namespace selling;

class GridLib extends GridCrud {

	public static function getOne(Customer $eCustomer, Product $eProduct): Grid {

		$eCustomer->expects(['id']);
		$eProduct->expects(['id']);

		return Grid::model()
			->select(Grid::getSelection())
			->whereProduct($eProduct)
			->whereCustomer($eCustomer)
			->get();

	}

	public static function getByProduct(Product $e): \Collection {

		if($e['pro'] === FALSE) {
			return new \Collection();
		}

		$e->expects(['id']);

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->select([
				'customer' => ['name', 'type', 'destination']
			])
			->whereProduct($e)
			->getCollection();

		$cGrid->sort(['customer' => ['name']]);

		return $cGrid;

	}

	public static function getByCustomer(Customer $e): \Collection {

		$e->expects(['id', 'type']);

		if($e['type'] === Customer::PRIVATE) {
			return new \Collection();
		}

		$cGrid = Grid::model()
			->select(Grid::getSelection())
			->select([
				'product' => [
					'name', 'variety', 'vignette', 'unit', 'size',
					'proPrice', 'proPackaging',
					'privatePrice',
				]
			])
			->whereCustomer($e)
			->getCollection();

		$cGrid->sort(['product' => ['name']]);

		return $cGrid;

	}

	public static function prepareByCustomer(Customer $eCustomer, array $input): \Collection {

		$eCustomer->expects(['farm']);

		$cGrid = new \Collection();

		$products = array_keys($input['price'] ?? []);

		$cProduct = Product::model()
			->select('id')
			->whereId('IN', $products)
			->whereStatus(Product::ACTIVE)
			->whereFarm($eCustomer['farm'])
			->getCollection();

		foreach($cProduct as $eProduct) {

			$eGrid = new Grid([
				'customer' => $eCustomer,
				'product' => $eProduct,
				'farm' => $eCustomer['farm']
			]);

			$eGrid->buildIndex(['price', 'packaging'], $input, $eProduct['id']);

			$cGrid[] = $eGrid;

		}

		return $cGrid;

	}

	public static function prepareByProduct(Product $eProduct, array $input): \Collection {

		$eProduct->expects(['farm']);

		$cGrid = new \Collection();

		$customers = array_keys($input['price'] ?? []);

		$cCustomer = Customer::model()
			->select('id')
			->whereId('IN', $customers)
			->whereStatus(Customer::ACTIVE)
			->whereType(Customer::PRO)
			->whereFarm($eProduct['farm'])
			->getCollection();

		foreach($cCustomer as $eCustomer) {

			$eGrid = new Grid([
				'customer' => $eCustomer,
				'product' => $eProduct,
				'farm' => $eProduct['farm']
			]);

			$eGrid->buildIndex(['price', 'packaging'], $input, $eCustomer['id']);

			$cGrid[] = $eGrid;

		}

		return $cGrid;

	}

	public static function update(Grid $e, array $properties): void {

		$properties[] = 'updatedAt';
		$e['updatedAt'] = new \Sql('NOW()');

		parent::update($e, $properties);

	}

	public static function updateGrid(\Collection $cGrid) {

		Grid::model()->beginTransaction();

		foreach($cGrid as $eGrid) {

			if($eGrid['packaging'] === NULL and $eGrid['price'] === NULL) {

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
							'packaging' => $eGrid['packaging'],
							'price' => $eGrid['price'],
							'updatedAt' => new \Sql('NOW()')
						]);

				}

			}

		}

		Grid::model()->commit();

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
