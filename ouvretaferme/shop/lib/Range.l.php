<?php
namespace shop;

class RangeLib extends RangeCrud {

	private static ?\Collection $cCatalogOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['catalog', 'status'];
	}

	public static function getOnlineCatalogs(): \Collection {

		if(self::$cCatalogOnline === NULL) {

			$cFarm = \farm\FarmLib::getOnline();

			if($cFarm->empty()) {
				return new \Collection();
			}

			$cShop = Shop::model()
				->select('id')
				->whereFarm('IN', $cFarm)
				->whereShared(TRUE)
				->getCollection()
				->mergeCollection(
					Share::model()
						->select('shop')
						->whereFarm('IN', $cFarm)
						->getColumn('shop')
				);

			if($cShop->empty()) {
				return new \Collection();
			}

			self::$cCatalogOnline ??= Range::model()
				->whereShop('IN', $cShop)
				->getColumn('catalog');

		}

		return self::$cCatalogOnline;

	}

	public static function getByShop(Shop $eShop): \Collection {

		return Range::model()
			->select(Range::getSelection())
			->whereShop($eShop)
			->getCollection(index: ['farm', NULL]);

	}

	public static function dissociate(Range $eRange, bool $removeFromDate): void {

		$eRange->expects(['shop']);

		Range::model()->beginTransaction();

			if($removeFromDate) {

				Date::model()
					->whereShop($eRange['shop'])
					->where('JSON_CONTAINS(catalogs, \''.$eRange['id'].'\')')
					->update([
						'catalogs' => new \Sql(\series\Task::model()->pdo()->api->jsonRemove('catalogs', $eRange['id']))
					]);

			}

			self::delete($eRange);

		Range::model()->commit();


	}

}
