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

	}

	public static function recalculate(Catalog $e): void {

		$e['products'] = ProductLib::countByCatalog($e);

		Catalog::model()
			->select('products')
			->update($e);

	}


}
?>