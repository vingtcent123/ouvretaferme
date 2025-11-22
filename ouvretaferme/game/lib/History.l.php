<?php
namespace game;

class HistoryLib extends HistoryCrud {

	public static function getByPlayer(Player $ePlayer): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereUser($ePlayer['user'])
			->sort([
				'createdAt' => SORT_DESC
			])
			->getCollection(0, 20);


	}

}
?>
