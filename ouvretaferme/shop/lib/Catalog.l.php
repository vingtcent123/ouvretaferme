<?php
namespace shop;

class CatalogLib extends CatalogCrud {

	public static function getPropertiesCreate(): array {
		return ['name', 'type'];
	}

	public static function getPropertiesUpdate(): array {
		return ['name'];
	}

	public static function getByFarm(\farm\Farm $eFarm, mixed $index = NULL): \Collection {

		return Catalog::model()
			->select(Catalog::getSelection())
			->whereFarm($eFarm)
			->sort([
				'name' => SORT_ASC
			])
			->getCollection(index: $index);

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


}
?>