<?php
namespace shop;

class SharedLib extends SharedCrud {

	public static function match(Shop $eShop, \farm\Farm $eFarm): bool {

		return Shared::model()
			->whereShop($eShop)
			->whereFarm($eFarm)
			->exists();

	}

}
