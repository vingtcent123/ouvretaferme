<?php
namespace game;

class TileLib extends TileCrud {

	public static function createByPlayer(Player $ePlayer): void {

		for($board = 1; $board <= GameSetting::BOARDS; $board++) {
			self::createBoard($ePlayer, $board);
		}

	}

	public static function createBoard(Player $ePlayer, int $board): void {

		for($tile = 1; $tile <= 16; $tile++) {

			$eTile = new Tile([
				'user' => $ePlayer['user'],
				'board' => $board,
				'tile' => $tile
			]);

			Tile::model()->insert($eTile);

		}

	}

	public static function getByBoard(Player $ePlayer, int $board): \Collection {

		return Tile::model()
			->select(Tile::getSelection())
			->whereUser($ePlayer['user'])
			->whereBoard($board)
			->getCollection(index: 'tile');

	}

}
?>
