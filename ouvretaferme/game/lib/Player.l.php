<?php
namespace game;

class PlayerLib extends PlayerCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function create(Player $e): void {

		try {

			$e['time'] = $e->getStartTime();

			parent::create($e);

		} catch(\DuplicateException) {
			Player::fail('name.duplicate');
		}

	}

	public static function getOnline(): Player {

		return Player::model()
			->select(Player::getSelection())
			->whereUser(\user\ConnectionLib::getOnline())
			->get();

	}

}
?>
