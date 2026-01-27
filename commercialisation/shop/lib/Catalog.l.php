<?php
namespace shop;

class CatalogLib extends CatalogCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name', 'comment'];
	}

	public static function create(Catalog $e): void {

		try {
			Catalog::model()->insert($e);
		} catch(\DuplicateException) {
			Catalog::fail('name.duplicate');
		}

	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL, mixed $index = NULL, array $onlyIds = []): \Collection {

		return Catalog::model()
			->select(Catalog::getSelection())
			->whereFarm($eFarm)
			->whereId('IN', $onlyIds, if: $onlyIds)
			->whereType($type, if: $type !== NULL)
			->whereStatus(Catalog::ACTIVE)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection(index: $index);

	}

	public static function getForShop(Shop $eShop, ?string $type = NULL, Date $eDateBase = new Date(), array $onlyIds = []): \Collection {

		if($eShop['shared']) {

			$cRange = Range::model()
					->select([
						'status',
						'catalog' => Catalog::getSelection(),
					])
					->whereCatalog('IN', $onlyIds, if: $onlyIds)
					->whereShop($eShop)
					->getCollection();

			$cCatalog = new \Collection();

			foreach($cRange as $eRange) {

				$cCatalog[$eRange['catalog']['id']] = $eRange['catalog']->merge([
					'selected' => $eRange['status'] === Range::AUTO
				]);

			}

			$cCatalog->sort('name', natural: TRUE);

		} else {

			$cCatalog = self::getByFarm($eShop['farm'], $type, index: 'id', onlyIds: $onlyIds);
			$cCatalog->setColumn('selected', FALSE);

		}

		if(
			$eDateBase->notEmpty() and
			$eDateBase['catalogs']
		) {

			foreach($eDateBase['catalogs'] as $catalog) {

				if($cCatalog->offsetExists($catalog)) {
					$cCatalog[$catalog]['selected'] = TRUE;
				}

			}

		}

		return $cCatalog;

	}

	public static function getForRange(\farm\Farm $eFarm, Shop $eShop): \Collection {

		$cCatalogUsed = Range::model()
			->whereFarm($eFarm)
			->whereShop($eShop)
			->getColumn('catalog');

		return Catalog::model()
			->select(Catalog::getSelection())
			->whereId('NOT IN', $cCatalogUsed, if: $cCatalogUsed->notEmpty())
			->whereFarm($eFarm)
			->whereType($eShop['type'])
			->whereStatus(Catalog::ACTIVE)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection(index: 'id');

	}

	public static function getByDates(\Collection $cDate): \Collection {

		$catalogs = $cDate->reduce(fn($e, $n) => array_merge($n, $e['catalogs'] ?? []), []);

		return Catalog::model()
			->select(Catalog::getSelection())
			->whereId('IN', $catalogs)
			->whereStatus(Catalog::ACTIVE)
			->getCollection(index: 'id');

	}

	public static function delete(Catalog $e): void {

		$e->expects(['farm']);

		Catalog::model()->beginTransaction();

		Department::model()
			->where('JSON_CONTAINS(catalogs, \''.$e['id'].'\')')
			->update([
				'catalogs' => new \Sql(Department::model()->pdo()->api->jsonRemove('catalogs', $e['id']))
			]);

		Range::model()
			->whereCatalog($e)
			->delete();

		if(
			// Un produit a déjà été créé avec ce catalogue
			Product::model()
				->whereCatalog($e)
				->exists() or
			// Une date a déjà été créée avec ce catalogue
			Date::model()
				->where('JSON_CONTAINS(catalogs, \''.$e['id'].'\')')
				->exists()
		) {

			Catalog::model()->update($e, [
				'name' => NULL,
				'status' => Catalog::DELETED,
			]);

		} else {
			Catalog::model()->delete($e);
		}

		Catalog::model()->commit();

	}

	public static function synchronizePrices(Catalog $e): void {

		$cProduct = ProductLib::getByCatalog($e, onlyActive: FALSE);

		foreach($cProduct as $eProduct) {

			if(
				$eProduct['product'][$eProduct['type'].'Price'] !== NULL and
				$eProduct['price'] !== $eProduct['product'][$eProduct['type'].'Price']
			) {

				$eProduct['price'] = $eProduct['product'][$eProduct['type'].'Price'];
				$eProduct['priceInitial'] = $eProduct['product'][$eProduct['type'].'PriceInitial'];

				ProductLib::update($eProduct, ['price', 'priceInitial']);

			}

		}

	}

	public static function recalculate(Catalog $e): void {

		$e['products'] = ProductLib::countByCatalog($e);

		Catalog::model()
			->select('products')
			->update($e);

	}


}
?>