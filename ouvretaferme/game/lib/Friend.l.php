<?php
namespace game;

class FriendLib extends FriendCrud {

	public static function getByPlayer(Player $ePlayer): \Collection {

		$cFriend = Friend::model()
			->select(Friend::getSelection())
			->whereUser($ePlayer['user'])
			->getColumn('friend');

		if($cFriend->notEmpty()) {

			return Player::model()
				->select(Player::getSelection() + [
					'user' => [
						'role' => ['fqn']
					]
				])
				->whereUser('IN', $cFriend)
				->sort([
					'points' => SORT_DESC,
					'id' => SORT_ASC
				])
				->getCollection();

		} else {
			return new \Collection();
		}


	}

	public static function are(Player $ePlayer): bool {

		return Friend::model()
			->whereUser(\user\ConnectionLib::getOnline())
			->whereFriend($ePlayer['user'])
			->exists();


	}

}
?>
