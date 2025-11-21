<?php
namespace game;

class ActionLib extends FoodCrud {

	public static function cook(Player $ePlayer, int $value): bool {

		if($value < 0) {
			return FALSE;
		}

		$cGrowing = Growing::model()
			->select('id')
			->whereHarvest('!=', NULL)
			->getCollection();

		$values = array_fill(0, $cGrowing->count(), -1 * $value);

		$cGrowing[] = new Growing();
		$values[] = $value;

		return FoodLib::change($ePlayer, 'soup-cook', $cGrowing, $values);

	}

	public static function eat(Player $ePlayer): bool {

		Player::model()->beginTransaction();

		$eaten = FoodLib::change($ePlayer, 'soup-eat', new Growing(), -1);

		if($eaten) {

			PlayerLib::changeTime($ePlayer, GameSetting::BONUS_SOUP);

			Player::model()->commit();

			return TRUE;

		} else {
			return FALSE;
		}

	}

}
?>
