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

				$e['code'] = dechex($e['user']['id']).bin2hex(random_bytes(2));

				parent::create($e);

				TileLib::createByPlayer($e);
				FoodLib::createByPlayer($e);

			Player::model()->commit();

		} catch(\DuplicateException) {

			Player::model()->rollBack();
			Player::fail('name.duplicate');
		}

	}

	public static function getByUser(\user\User $eUser): Player {

		return Player::model()
			->select(Player::getSelection())
			->whereUser($eUser)
			->get();

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

			$ePlayer['user'] = \user\ConnectionLib::getOnline();

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

	public static function resetTime(): void {

			Player::model()
				->whereTimeUpdatedAt('!=', currentDate())
				->update([
					'time' => 0,
					'timeUpdatedAt' => currentDate()
				]);

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
				'position' => function() use (&$position) {
					return $position++;
				},
				'id',
				'name',
				'points',
				'user' => [
					'role' => ['fqn']
				]
			])
			->whereName('NOT IN', GameSetting::ADMIN)
			->wherePoints('>', 0)
			->sort([
				'points' => SORT_DESC,
				new \Sql('id = '.$ePlayerOnline['id'].' DESC'),
				'id' => SORT_ASC
			])
			->getCollection(0, 20);

		if(
			$cPlayer->contains(fn($ePlayer) => $ePlayerOnline->is($ePlayer)) === FALSE and
			$ePlayerOnline['points'] > 0
		) {

			$cPlayer[] = (clone $ePlayerOnline)->merge([
				'position' => Player::model()
					->whereName('NOT IN', GameSetting::ADMIN)
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

	public static function addFriend(Player $ePlayer, string $code): bool {

		$ePlayerFriend = Player::model()
			->select(Player::getSelection())
			->whereCode($code)
			->get();

		if(
			$ePlayerFriend->empty() or
			$ePlayerFriend->is($ePlayer)
		) {
			return FALSE;
		}

		try {

			Friend::model()->beginTransaction();

			$eFriend = new Friend([
				'user' => $ePlayerFriend['user'],
				'friend' => $ePlayer['user']
			]);

			Friend::model()->insert($eFriend);

			$eFriend = new Friend([
				'user' => $ePlayer['user'],
				'friend' => $ePlayerFriend['user']
			]);

			Friend::model()->insert($eFriend);

			Friend::model()->commit();

		} catch(\DuplicateException) {
			Friend::model()->rollBack();
		}

		return TRUE;

	}

	public static function motivate(Player $ePlayer, Player $ePlayerFriend): bool {

		Friend::model()->beginTransaction();

			if(
				Friend::model()
				->whereUser($ePlayer['user'])
				->whereFriend($ePlayerFriend['user'])
				->exists() and
				($ePlayer['giftSentAt'] !== currentDate() or in_array($ePlayer['name'], GameSetting::ADMIN)) and
				$ePlayerFriend['giftReceivedAt'] !== currentDate()
			) {

				self::changeTime($ePlayerFriend, 1);

				Player::model()->update($ePlayer, [
					'giftSentAt' => new \Sql('CURRENT_DATE'),
				]);

				Player::model()->update($ePlayerFriend, [
					'giftReceivedAt' => new \Sql('CURRENT_DATE')
				]);

			}

		Friend::model()->commit();

		return TRUE;

	}

	public static function removeFriend(Player $ePlayer, Player $ePlayerFriend): bool {

		Friend::model()->beginTransaction();

			Friend::model()
				->whereUser($ePlayer['user'])
				->whereFriend($ePlayerFriend['user'])
				->delete();

			Friend::model()
				->whereUser($ePlayerFriend['user'])
				->whereFriend($ePlayer['user'])
				->delete();

		Friend::model()->commit();

		return TRUE;

	}

}
?>
