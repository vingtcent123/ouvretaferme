<?php
namespace game;

class ActionLib {

	public static function seed(Player $ePlayer, Tile $eTile, Growing $eGrowing): bool {

		if($eTile['growing']->notEmpty()) {
			return FALSE;
		}

		if(
			$eGrowing['fqn'] === 'pivoine' and
			$ePlayer->getHarvestTime(
				TileLib::getByBoard($ePlayer, $eTile['board'])
			) <= 1
		) {
			return FALSE;
		}

		Player::model()->beginTransaction();

			$time = GameSetting::TIME_PLANTING;

			if(PlayerLib::changeTime($ePlayer, -1 * $time)) {

				$eTile->merge([
					'growing' => $eGrowing,
					'watering' => ($eGrowing['bonusWatering'] === NULL ? NULL : 0),
					'plantedAt' => new \Sql('NOW()'),
					'harvestedAt' => $eGrowing['harvest'] ? new \Sql('NOW() + INTERVAL '.$eGrowing['days'].' DAY') : NULL
				]);

				Tile::model()
					->select('growing', 'watering', 'plantedAt', 'harvestedAt')
					->update($eTile);

				$eHistory = new History([
					'user' => $ePlayer['user'],
					'time' => $time,
					'message' => HistoryUi::getMessage('seedling', [GrowingUi::getVignette($eGrowing, '1rem')])
				]);

				History::model()->insert($eHistory);

				$return = TRUE;

			} else {
				throw new \FailAction('game\Action::missingTime');
			}

		Player::model()->commit();

		return $return;

	}

	public static function water(Player $ePlayer, Tile $eTile): bool {

		if(
			$eTile['growing']->empty() or
			$eTile['growing']['bonusWatering'] === NULL or
			$eTile->canHarvest()
		) {
			return FALSE;
		}

		Player::model()->beginTransaction();

			$time = GameSetting::TIME_WATERING;

			if(PlayerLib::changeTime($ePlayer, -1 * $time)) {

				$eTile->merge([
					'watering' => new \Sql('watering + 1'),
				]);

				Tile::model()
					->select('watering')
					->update($eTile);

				$eHistory = new History([
					'user' => $ePlayer['user'],
					'time' => $time,
					'message' => HistoryUi::getMessage('watering', [GrowingUi::getVignette($eTile['growing'], '1rem')])
				]);

				History::model()->insert($eHistory);

				$return = TRUE;

			} else {
				throw new \FailAction('game\Action::missingTime');
			}

		Player::model()->commit();

		return $return;

	}

	public static function weed(Player $ePlayer, Tile $eTile): bool {

		if(
			$eTile['growing']->empty() or
			$eTile->canHarvest()
		) {
			return FALSE;
		}

		Player::model()->beginTransaction();

			$time = GameSetting::TIME_WEED;

			if(PlayerLib::changeTime($ePlayer, -1 * $time)) {

				$eTile->merge([
					'harvestedAt' => new \Sql('harvestedAt - INTERVAL '.GameSetting::BONUS_WEED.' DAY'),
				]);

				Tile::model()
					->select('harvestedAt')
					->update($eTile);

				$eHistory = new History([
					'user' => $ePlayer['user'],
					'time' => $time,
					'message' => HistoryUi::getMessage('weeding', [GrowingUi::getVignette($eTile['growing'], '1rem')])
				]);

				History::model()->insert($eHistory);

				$return = TRUE;

			} else {
				throw new \FailAction('game\Action::missingTime');
			}

		Player::model()->commit();

		return $return;

	}

	public static function harvest(Player $ePlayer, Tile $eTile): bool {

		if(
			$eTile['growing']->empty() or
			$eTile->canHarvest() === FALSE
		) {
			return FALSE;
		}

		Player::model()->beginTransaction();

			$cTile = TileLib::getByBoard($ePlayer, $eTile['board']);
			$time = $ePlayer->getHarvestTime($cTile);

			if(PlayerLib::changeTime($ePlayer, -1 * $time)) {

				$harvest = $eTile->getHarvest($cTile);

				FoodLib::change($ePlayer, 'harvesting', $eTile['growing'], $harvest, $time);

				Tile::model()->update($eTile, [
					'growing' => new Growing(),
					'watering' => NULL,
					'plantedAt' => NULL,
					'harvestedAt' => NULL
				]);

				$return = TRUE;

			} else {
				throw new \FailAction('game\Action::missingTime');
			}

		Player::model()->commit();

		return $return;

	}

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
