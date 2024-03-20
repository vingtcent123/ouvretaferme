<?php
namespace mail;

class CustomizeLib extends CustomizeCrud {

	public static function getPropertiesCreate(): array {
		return ['template'];
	}

	public static function getTemplate(\farm\Farm $eFarm, string $type, \shop\Shop $eShop = new \shop\Shop()): ?string {

		return Customize::model()
			->whereType($type)
			->whereFarm($eFarm)
			->whereShop($eShop)
			->getValue('template');

	}

	public static function getExisting(Customize $e): Customize {

		return Customize::model()
			->select(Customize::getSelection())
			->whereType($e['type'])
			->whereFarm($e['farm'])
			->whereShop($e['shop'])
			->get();

	}

	public static function getByFarm(\farm\Farm $eFarm, \shop\Shop $eShop = new \shop\Shop()): \Collection {

		return Customize::model()
			->select(Customize::getSelection())
			->whereFarm($eFarm)
			->whereShop($eShop)
			->getCollection(index: 'type');

	}

	public static function create(Customize $e): void {

		Customize::model()
			->option('add-replace')
			->insert($e);

	}

}
?>
