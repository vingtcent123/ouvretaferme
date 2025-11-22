<?php
namespace game;

class TileUi {

	public function get(Tile $e, Player $ePlayer, \Collection $cTile, \Collection $cGrowing): string {

		if($e['growing']->empty()) {
			return $this->getSeedling($e, $ePlayer, $cTile, $cGrowing);
		} else {

			if($e['growing']['harvest'] === NULL) {
				return $this->getGrowingBonus($e);
			} else {
				return $this->getGrowingHarvest($e, $ePlayer, $cTile);
			}
		}


	}

	public function getGrowingBonus(Tile $e): string {

		$h = '<div class="game-tile-action">'.GrowingUi::getVignette($e['growing'], '3rem').'</div>';

		return $h;

	}

	public function getGrowingHarvest(Tile $e, Player $ePlayer, \Collection $cTile): string {

		$eGrowing = $e['growing'];

		if($e->canHarvest()) {

			$h = '<div class="game-tile-action">';
				$h .= $e->getHarvest($cTile).' '.GrowingUi::getVignette($eGrowing, '2rem');
			$h .= '</div>';
			$h .= '<a data-ajax="/game/action:doHarvest" post-id="'.$e['id'].'" class="game-tile-harvest game-tile-harvest-now">';;
				$h .= s("Récolter");
				$h .= '<br/>';
				$h .= \Asset::icon('clock').'  '.PlayerUi::getTime($ePlayer->getHarvestTime($cTile));
			$h .= '</a>';

		} else {

			$h = '<a data-ajax="/game/action:doWeed" post-id="'.$e['id'].'" data-dropdown="bottom-center" class="game-tile-action dropdown-toggle">';
				$h .= $e->getHarvest($cTile).' '.GrowingUi::getVignette($eGrowing, '2rem');
			$h .= '</a>';
			$h .= '<div class="dropdown-list">';
				$h .= '<div class="dropdown-title">'.GrowingUi::getVignette($eGrowing, '2rem').'  '.$eGrowing['name'].'</div>';
				if($e->canHarvest() === FALSE) {
					if($eGrowing['bonusWatering'] !== NULL) {
						$h .= '<a data-ajax="/game/action:doWatering" post-id="'.$e['id'].'" class="'.($ePlayer->canTime(-1 * GameSetting::TIME_WATERING) ? '' : 'disabled').' dropdown-item">';
							$h .= '<div class="flex-justify-space-between">';
								$h .= '<div>';
									$h .= GameSetting::EMOJI_WATERING.'  '.s("Arroser");
									$h .= '<div style="margin-top: 0.25rem; font-size: 0.9rem">'.s("Rendement : +{value}", $eGrowing['bonusWatering'].' '.GrowingUi::getVignette($eGrowing, '1rem')).'</div>';
								$h .= '</div>';
								$h .= '<span>'.\Asset::icon('clock').'  '.PlayerUi::getTime(GameSetting::TIME_WATERING).'</span>';
							$h .= '</div>';
						$h .= '</a>';
					}
					$h .= '<a data-ajax="/game/action:doWeed" post-id="'.$e['id'].'" class="'.($ePlayer->canTime(-1 * GameSetting::TIME_WEED) ? '' : 'disabled').' dropdown-item">';
						$h .= '<div class="flex-justify-space-between">';
							$h .= '<div>';
								$h .= GameSetting::EMOJI_WEED.'  '.s("Désherber");
								$h .= '<div style="margin-top: 0.25rem; font-size: 0.9rem">'.s("Récolte : -{value} jours", GameSetting::BONUS_WEED).'</div>';
							$h .= '</div>';
							$h .= '<span>'.\Asset::icon('clock').'  '.PlayerUi::getTime(GameSetting::TIME_WEED).'</span>';
						$h .= '</div>';
					$h .= '</a>';
				}
			$h .= '</div>';

			$h .= '<div class="game-tile-harvest">';;
				$h .= GameSetting::EMOJI_HARVEST.' ';
				$seconds = strtotime($e['harvestedAt']) - time();
				$h .= '<span class="hide-xs-down">'.\util\DateUi::secondToDuration($seconds, \util\DateUi::LONG, maxNumber: 1).'</span>';
				$h .= '<span class="hide-sm-up">'.\util\DateUi::secondToDuration($seconds, \util\DateUi::SHORT, maxNumber: 1).'</span>';
			$h .= '</div>';

		}

		return $h;

	}

	public function getSeedling(Tile $e, Player $ePlayer, \Collection $cTile, \Collection $cGrowing): string {

		$h = '<a data-dropdown="bottom-center" class="game-tile-action game-tile-action-start">'.\Asset::icon('plus-circle').'</a>';
		$h .= '<div class="dropdown-list dropdown-list-2">';
			$h .= '<div class="dropdown-title flex-justify-space-between">';
				$h .= '<span>'.GameSetting::EMOJI_SEEDLING.'  '.s("Semer").'</span>';
				$h .= '<span>'.\Asset::icon('clock').'  '.PlayerUi::getTime(GameSetting::TIME_PLANTING).'</span>';
			$h .= '</div>';
			foreach($cGrowing as $eGrowing) {

				$can = (
					$ePlayer->canTime(1) and
					(
						$eGrowing['fqn'] !== 'pivoine' or
						$ePlayer->getHarvestTime($cTile) > 1
					)
				);

				$h .= '<a data-ajax="/game/action:doSeedling" post-id="'.$e['id'].'" post-growing="'.$eGrowing['id'].'" class="'.($can ? '' : 'disabled').' dropdown-item">'.GrowingUi::getVignette($eGrowing, '2rem').'  '.$eGrowing['name'].'</a>';
			}
		$h .= '</div>';

		return $h;

	}

}
?>
