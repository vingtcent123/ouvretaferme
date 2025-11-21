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

	public static function change(Player $ePlayer, string $history, \Collection|Growing $cGrowing, array|int $values, ?float $time = NULL): bool {

		$ePlayer->expects(['user']);

		if($cGrowing instanceof Growing) {
			$cGrowing = new \Collection([$cGrowing]);
		}

		if(is_int($values)) {
			$values = array_fill(0, $cGrowing->count(), $values);
		}

		Food::model()->beginTransaction();

			$position = 0;

			foreach($cGrowing as $eGrowing) {

				$value = $values[$position++] ?? throw new \Exception('Missing value at position '.$position);

				$update = [
					'current' => new \Sql('current + '.$value),
				];

				if($value > 0) {

					$update += [
						'total' => new \Sql('total + '.$value),
					];

				}

				$affected = Food::model()
					->whereUser($ePlayer['user'])
					->whereGrowing($eGrowing)
					->where(new \Sql('current + '.$value.' >= 0'), if: $value < 0)
					->update($update);

				if($affected === 0) {

					Food::model()->rollBack();

					return FALSE;

				}

			}

			PlayerLib::updatePoints($ePlayer);

			$eHistory = new History([
				'user' => $ePlayer['user'],
				'time' => $time,
				'message' => HistoryUi::getMessage($history, $cGrowing, $values)
			]);

			History::model()->insert($eHistory);

		Food::model()->commit();

		return TRUE;

	}

}
?>
