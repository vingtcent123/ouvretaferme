<?php
namespace game;

class TileLib extends TileCrud {

	public static function createBoard(\user\User $eUser, int $board): void {

		for($tile = 1; $tile <= 16; $tile++) {

			$eTile = new Tile([
				'user' => $eUser,
				'board' => $board,
				'tile' => $tile
			]);

			Tile::model()->insert($eTile);

		}

	}

	public static function getOne(\user\User $eUser, int $board, int $tile): Tile {

		return Tile::model()
			->select(Tile::getSelection())
			->whereUser($eUser)
			->whereBoard($board)
			->whereTile($tile)
			->get();

	}

	public static function getByUser(\user\User $eUser): \Collection {

		return Tile::model()
			->select(Tile::getSelection())
			->whereUser($eUser)
			->getCollection(index: 'tile');

	}

}
?>
