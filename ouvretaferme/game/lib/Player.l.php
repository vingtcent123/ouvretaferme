<?php
namespace game;

class PlayerLib extends PlayerCrud {

	private static $ePlayerOnline = NULL;

	public static function getPropertiesCreate(): array {
		return ['name'];
	}

	public static function create(Player $e): void {

		try {

			Player::model()->beginTransaction();

				parent::create($e);

				TileLib::createByPlayer($e);
				FoodLib::createByPlayer($e);

			Player::model()->commit();

		} catch(\DuplicateException) {

			Player::model()->rollBack();
			Player::fail('name.duplicate');
		}

	}

	public static function getOnline(): Player {

		if(self::$ePlayerOnline !== NULL) {
			return self::$ePlayerOnline;
		}

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

		self::$ePlayerOnline = $ePlayer;

		return self::$ePlayerOnline;

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

	public static function getPointsRanking(Player $ePlayerOnline): \Collection {

		$position = 1;

		$cPlayer = Player::model()
			->select([
				'position' => fn() => $position++,
				'id',
				'name',
				'points',
			])
			->sort([
				'points' => SORT_DESC,
				new \Sql('id = '.$ePlayerOnline['id'].' DESC')
			])
			->getCollection(0, 20);
		
		if($cPlayer->contains(fn($ePlayer) => $ePlayerOnline->is($ePlayer)) === FALSE) {

			$cPlayer[] = (clone $ePlayerOnline)->merge([
				'position' => Player::model()
					->wherePoints('>', $ePlayerOnline['points'])
					->count() + 1
			]);

		}

		return $cPlayer;

	}

	public static function restart(Player $ePlayer): void {

		$eUser = $ePlayer['user'];

		Player::model()->beginTransaction();

			Player::model()
				->whereUser($eUser)
				->delete();

			Food::model()
				->whereUser($eUser)
				->delete();

			History::model()
				->whereUser($eUser)
				->delete();

			Tile::model()
				->whereUser($eUser)
				->delete();

		Player::model()->commit();


	}

}
?>
