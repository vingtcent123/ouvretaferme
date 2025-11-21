<?php
namespace game;

class PlayerLib extends PlayerCrud {

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function create(Player $e): void {

		try {

			Player::model()->beginTransaction();

				parent::create($e);

				TileLib::createByUser($e['user']);
				FoodLib::createByUser($e['user']);

			Player::model()->commit();

		} catch(\DuplicateException) {

			Player::model()->rollBack();
			Player::fail('name.duplicate');
		}

	}

	public static function getOnline(): Player {

		$ePlayer = Player::model()
			->select(Player::getSelection())
			->whereUser(\user\ConnectionLib::getOnline())
			->get();

		if($ePlayer->notEmpty()) {

			if($ePlayer['timeUpdatedAt'] !== currentDate()) {

				$ePlayer['time'] = 0;
				$ePlayer['timeUpdatedAt'] = currentDate();

				Player::model()
					->select('time', 'timeUpdatedAt')
					->update($ePlayer);

			}

		}

		return $ePlayer;

	}

	public static function changeTime(Player $e, float $value): bool {

		$affected = Player::model()
			->where(new \Sql('time - '.$value.' <= '.$e->getDailyTime()), if: $value < 0)
			->update($e, [
				'time' => new \Sql('time - '.$value)
			]);

		return ($affected > 0);

	}

	public static function updatePoints(Player $e): void {

		Player::model()->update($e, [
			'points' => self::calculatePoints($e['user'])
		]);

	}

	public static function calculatePoints(\user\User $eUser): int {

		return Food::model()
			->whereUser($eUser)
			->getValue(new \Sql('SUM(IF(growing IS NULL, current * 10, current))', 'int'));

	}

}
?>
