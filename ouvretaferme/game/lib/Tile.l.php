<?php
namespace game;

class TileLib extends TileCrud {

	public static function getByUser(\user\User $eUser): \Collection {

		return Tile::model()
			->select(Tile::getSelection())
			->whereUser($eUser)
			->getCollection(index: 'tile');

	}

}
?>
