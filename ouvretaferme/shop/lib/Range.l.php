<?php
namespace shop;

class RangeLib extends RangeCrud {

	public static function getPropertiesCreate(): array {
		return ['catalog', 'status'];
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
