<?php
namespace game;

class HistoryLib extends HistoryCrud {

	public static function getByUser(\user\User $eUser): \Collection {

		return History::model()
			->select(History::getSelection())
			->whereUser($eUser)
			->sort([
				'createdAt' => SORT_DESC
			])
			->getCollection(0, 20);


	}

}
?>
