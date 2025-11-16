<?php
namespace game;

class FoodLib extends FoodCrud {

	public static function getByUser(\user\User $eUser): \Collection {

		return Food::model()
			->select(Food::getSelection() + [
				'growing' => Growing::getSelection()
			])
			->whereUser($eUser)
			->getCollection(index: 'growing')
			->sort(['product' => ['name']], natural: TRUE);


	}

	public static function createByUser(\user\User $eUser): void {

		$cGrowing = GrowingLib::getAll()->filter(fn($eGrowing) => $eGrowing['harvest'] !== NULL);

		foreach($cGrowing as $eGrowing) {

			$eFood = new Food([
				'user' => $eUser,
				'growing' => $eGrowing,
			]);

			Food::model()->insert($eFood);

		}

		$eFood = new Food([
			'user' => $eUser,
			'growing' => new Growing(),
		]);

		Food::model()->insert($eFood);


	}

	public static function add(Player $ePlayer, Growing $eGrowing, Tile $eTile, int $value): void {

		$ePlayer->expects(['user']);

		Food::model()->beginTransaction();

			if($eTile->notEmpty()) {

				//PlayerLib::canTime($eUser);

			} else {
				$time = NULL;
			}

			Food::model()
				->whereUser($ePlayer['user'])
				->whereGrowing($eGrowing)
				->update([
					'current' => new \Sql('current + '.$value),
					'total' => new \Sql('total + '.$value),
				]);

			PlayerLib::updatePoints($ePlayer);

			$eHistory = new History([
				'user' => $ePlayer['user'],
				'time' => $time,
				'message' => HistoryUi::getMessageProduction($eGrowing, $value)
			]);

			History::model()->insert($eHistory);

		Food::model()->commit();

	}

}
?>
