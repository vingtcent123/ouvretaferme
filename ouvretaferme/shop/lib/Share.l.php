<?php
namespace shop;

class ShareLib extends ShareCrud {

	private static array $cacheList = [];
	private static array $cacheMatch = [];

	public static function getPropertiesUpdate(): array {
		return ['label'];
	}

	public static function getForShop(Shop $eShop): \Collection {

		self::$cacheList[$eShop['id']] ??= Share::model()
			->select(Share::getSelection())
			->whereShop($eShop)
			->getCollection()
			->sort(['farm' => ['name']]);

		return self::$cacheList[$eShop['id']];

	}

	public static function match(Shop $eShop, \farm\Farm $eFarm): bool {

		self::$cacheMatch[$eShop['id']][$eFarm['id']] ??= Share::model()
			->whereShop($eShop)
			->whereFarm($eFarm)
			->exists();

		return self::$cacheMatch[$eShop['id']][$eFarm['id']];

	}

	public static function remove(Shop $eShop, \farm\Farm $eFarm): void {

		Share::model()
			->whereShop($eShop)
			->whereFarm($eFarm)
			->delete();

	}

}
