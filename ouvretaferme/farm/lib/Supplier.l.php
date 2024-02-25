<?php
namespace farm;

class SupplierLib extends SupplierCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function getPropertiesUpdate(): array {
		return self::getPropertiesCreate();
	}

	public static function getFromQuery(string $query, \farm\Farm $eFarm, ?array $properties = []): \Collection {

		if($query !== '') {
			Supplier::model()->whereName('LIKE', '%'.$query.'%');
		}

		return Supplier::model()
			->select($properties ?: Supplier::getSelection())
			->whereFarm($eFarm)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function getByFarm(\farm\Farm $eFarm, \Search $search = new \Search()): \Collection {

		if($search->get('name')) {
			Supplier::model()->whereName('LIKE', '%'.$search->get('name').'%');
		}

		return Supplier::model()
			->select(Supplier::getSelection())
			->whereFarm($eFarm)
			->getCollection()
			->sort('name', natural: TRUE);

	}

	public static function delete(Supplier $e): void {

		$e->expects(['id', 'farm']);

		if(\plant\Variety::model()
				->whereSupplierSeed($e)
				->exists() or \plant\Variety::model()
				->whereSupplierPlant($e)
				->exists()) {
			Supplier::fail('deleteUsed');
			return;
		}

		Supplier::model()->beginTransaction();

		parent::delete($e);

		Supplier::model()->commit();

	}

}
?>
