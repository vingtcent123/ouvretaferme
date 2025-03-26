<?php
namespace shop;

class ShareLib extends ShareCrud {

	private static array $cache = [];

	public static function getForShop(Shop $eShop): Share {

		$cFarm = \farm\FarmLib::getOnline();

		self::$cache[$eShop['id']] ??= Share::model()
			->select(Share::getSelection())
			->whereShop($eShop)
			->whereFarm('IN', $cFarm)
			->get();

		return self::$cache[$eShop['id']];

	}

	public static function match(Shop $eShop, \farm\Farm $eFarm): bool {

		return Share::model()
			->whereShop($eShop)
			->whereFarm($eFarm)
			->exists();

	}

}
