<?php
namespace shop;

class CatalogLib extends CatalogCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function getByFarm(\farm\Farm $eFarm, ?string $type = NULL, mixed $index = NULL): \Collection {

		return Catalog::model()
			->select(Catalog::getSelection())
			->whereFarm($eFarm)
			->whereType($type, if: $type !== NULL)
			->whereStatus(Catalog::ACTIVE)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection(index: $index);

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
				'status' => Catalog::DELETED,
			]);

		} else {
			Catalog::model()->delete($e);
		}

		Catalog::model()->commit();

	}

	public static function recalculate(Catalog $e): void {

		$e['products'] = ProductLib::countByCatalog($e);

		Catalog::model()
			->select('products')
			->update($e);

	}


}
?>