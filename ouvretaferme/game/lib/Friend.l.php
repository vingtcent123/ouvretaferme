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
				->select(Player::getSelection())
				->whereUser('IN', $cFriend)
				->sort(['points' => SORT_DESC])
				->getCollection();

		} else {
			return new \Collection();
		}


	}

}
?>
