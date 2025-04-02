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

}
