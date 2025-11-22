<?php
namespace game;

class GrowingLib extends GrowingCrud {

	public static function getAll(): \Collection {

		return Growing::model()
			->select(Growing::getSelection())
			->sort([
				new \Sql('harvest IS NULL'),
				'name' => SORT_ASC
			])
			->getCollection(index: 'id');

	}

}
?>
