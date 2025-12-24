<?php
namespace game;

class DeskUi {

	public static function getFonts(): string {

		$h = '<link rel="preconnect" href="https://fonts.googleapis.com">';
		$h .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
		$h .= '<link href="https://fonts.googleapis.com/css2?family='.urlencode('Mystery Quest').'" rel="stylesheet">';

		return $h;

	}

	public function play(Player $ePlayer, int $board, \Collection $cTile, \Collection $cGrowing): string {

		$player = $ePlayer->isOnline() ? '' : '&player='.$ePlayer['id'];

		$h = '';

		$h .= '<div class="game-boards">';

			for($position = 1; $position <= GameSetting::BOARDS; $position++) {

				if($ePlayer->getBoards() >= $position) {

					$h .= '<a href="/jouer?board='.$position.''.$player.'" class="btn '.($board === $position ? 'btn-primary' : '').' game-board btn-lg">';
						$h .= s("Plateau");
						$h .= '<div class="game-board-label">'.\Asset::icon($position.'-circle-fill').'</div>';
					$h .= '</a>';

				} else {

					$h .= '<div class="btn game-board-disabled game-board btn-lg" data-alert="'.s("Ouverture le {value} !", \util\DateUi::numeric(GameSetting::BOARDS_OPENING[$position])).'">';
						$h .= s("Plateau");
						$h .= '<div class="game-board-label">'.\Asset::icon($position.'-circle-fill').'</div>';
					$h .= '</div>';

				}
			}

		$h .= '</div>';

		if($ePlayer->getBoards() < $board) {
			return $h;
		}

		$content = '';

		for($tile = 1; $tile <= 16; $tile++) {

			$eTile = $cTile[$tile];

			$content .= '<div class="game-tile game-tile-'.$tile.' '.($eTile['growing']->notEmpty() ? 'game-tile-growing' : '').'">';
				$content .= new TileUi()->get($eTile, $ePlayer, $cTile, $cGrowing);
			$content .= '</div>';

		}

		$h .= $this->get($content, $board);

		return $h;

	}

	public function tabs(Player $ePlayer, \Collection $cPlayerRanking, \Collection $cPlayerFriend, \Collection $cGrowing, \Collection $cFood, \Collection $cHistory): string {

		$h = '<div class="tabs-h" id="game-tabs" onrender="'.encode('Lime.Tab.restore(this, "game-crops")').'">';

			$h .= '<div class="tabs-item">';
				$h .= '<a onclick="Lime.Tab.select(this)" class="tab-item" data-tab="game-crops">';
					$h .= '<span class="hide-xs-down">'.s("Tableau des cultures").'</span>';
					$h .= '<span class="hide-sm-up">'.s("Cultures").'</span>';
				$h .= '</a>';
				$h .= '<a onclick="Lime.Tab.select(this)" class="tab-item" data-tab="game-friends">'.s("Amis").' <small class="tab-item-count">'.$cPlayerFriend->count().'</small></a>';
				$h .= '<a onclick="Lime.Tab.select(this)" class="tab-item" data-tab="game-rankings">'.s("Classements").'</a>';
				$h .= '<a onclick="Lime.Tab.select(this)" class="tab-item hide-xs-down" data-tab="game-history">'.s("Historique").'</a>';
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="game-crops">';
				$h .= new \game\HelpUi()->getCrops($cGrowing);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="game-friends">';
				$h .= new \game\FriendUi()->display($cPlayerFriend, $ePlayer);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="game-rankings">';
				$h .= new \game\PlayerUi()->getPointsRanking($cPlayerRanking, $ePlayer);
				$h .= new \game\PlayerUi()->getFoodRankings($cFood);
			$h .= '</div>';

			$h .= '<div class="tab-panel" data-tab="game-history">';
				$h .= new \game\HistoryUi()->display($cHistory);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function dashboard(Player $ePlayer, \Collection $cGrowing, \Collection $cFood): string {

		$cGrowingFood = $cGrowing->find(fn($eGrowing) => $eGrowing['harvest'] !== NULL);

		$h = '';

		if(currentDate() === GameSetting::END) {
			$h .= '<div class="text-center">';
				$h .= '<h3>'.s("Dernier jour !").'</h3>';
				$h .= '<p>'.s("Vous pouvez jouer jusqu'à {value}.", GameSetting::END_TIME).'<br/>'.s("Ensuite, ce sont les rennes qui travaillent 🦌").'</p>';
			$h .= '</div>';
		}

		$h .= '<div class="game-dashboard">';

			$h .= '<h3 style="grid-area: title">'.encode($ePlayer['name']).'</h3>';

			$h .= '<div class="game-dashboard-element" style="grid-area: time">';
				$h .= '<h4 class="game-dashboard-title">'.s("Temps de travail <br/>disponible").'</h4>';
				$h .= '<div>';
					$h .= '<div class="game-dashboard-value">';
						$h .= '<div class="game-dashboard-item">'.\Asset::icon('clock').'  '.\game\PlayerUi::getTime($ePlayer->getRemainingTime()).'</div>';
						if($ePlayer['time'] !== 0.0) {
							if(currentDate() === GameSetting::END) {
								$h .= '<div class="game-dashboard-more">'.s("dernier jour !").'</div>';
							} else {
								$h .= '<div class="game-dashboard-more">'.s("retour à {time} à minuit", ['time' => \game\PlayerUi::getTime($ePlayer->getDailyTime())]).'</div>';
							}
						}
					$h .= '</div>';
					if(
						$ePlayer->isPremium() === FALSE and
						$ePlayer->getRemainingTime() < 3
					) {
						$h .= '<div class="game-dashboard-more">';
							$h .= match($ePlayer->getRole()) {
								'farmer' => '<a href="/adherer" class="color-game">'.s("Adhérer pour passer à {value} de travail par jour", \game\PlayerUi::getTime(GameSetting::TIME_DAY_PREMIUM)).'</a>',
								'customer' => '<a href="/donner" class="color-game">'.s("Faire un don pour passer à {value} de travail par jour", \game\PlayerUi::getTime(GameSetting::TIME_DAY_PREMIUM)).'</a>',
							};
						$h .= '</div>';
					}
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="game-dashboard-element" style="grid-area: production">';
				$h .= '<div class="game-dashboard-value game-dashboard-value-list">';

					$minFood = NULL;
					$canCook = $cFood->find(fn($eFood) => ($eFood['growing']->notEmpty() and $eFood['current'] > 0))->count() === $cGrowingFood->count();

					foreach($cFood as $eFood) {

						if($eFood['growing']->notEmpty()) {

							$h .= '<div class="game-dashboard-item">';
								$h .= GrowingUi::getVignette($eFood['growing'], '1.5rem').'  '.$eFood['current'];
							$h .= '</div>';

							$minFood = ($minFood === NULL) ? $eFood['current'] : min($eFood['current'], $minFood);

						} else {

							$soup = $eFood['current'];
							$canEat = $soup > 0;

							if(
								$canEat or
								$canCook
							) {

								$h .= '<div class="game-dashboard-item">';

									$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end" data-dropdown-hover="true">'.\Asset::icon('cup-hot').'  '.p("{value} soupe", "{value} soupes", $eFood['current']).'</a>';
									$h .= '<div class="dropdown-list">';
										$h .= '<div class="dropdown-subtitle">'.s("Côté cuisine").'</div>';
										$h .= '<div class="dropdown-text">';
											foreach($cGrowingFood as $eGrowing) {
												$h .= '-1 '.GrowingUi::getVignette($eGrowing, '1.5rem').'   ';
											}
											$h .= \Asset::icon('arrow-right').'   ';
											$h .= '+1 '.\Asset::icon('cup-hot');
										$h .= '</div>';
										$h .= '<a data-ajax="/game/action:doCook" post-value="1" class="'.($canCook ? '' : 'disabled').' dropdown-item">'.\Asset::icon('chevron-right').' '.s("Cuisiner une soupe").'  '.\Asset::icon('cup-hot').'</a>';

										if($canCook) {

											if($minFood >= 5) {
												$h .= '<a data-ajax="/game/action:doCook" post-value="5" class="dropdown-item">'.\Asset::icon('chevron-right').' '.s("Cuisiner 5 soupes").'  '.str_repeat(\Asset::icon('cup-hot'), 5).'</a>';
											}

											if($minFood > 5) {
												$h .= '<a data-ajax="/game/action:doCook" post-value="'.$minFood.'" class="dropdown-item" data-confirm="'.s("C'est beaucoup, vous êtes sûr ?").'" style="max-width: 25rem">'.\Asset::icon('chevron-right').' '.s("Cuisiner {value} soupes", $minFood).'  '.str_repeat(\Asset::icon('cup-hot'), $minFood).'</a>';
											}

										}
										
										$h .= '<div class="dropdown-divider"></div>';
										$h .= '<div class="dropdown-subtitle">'.s("Côté salon").'</div>';
										$h .= '<div class="dropdown-text">';
											$h .= '-1 '.\Asset::icon('cup-hot').' '.\Asset::icon('arrow-right').' ';
											$h .= s("+{value} de temps de travail disponible", PlayerUi::getTime(GameSetting::BONUS_SOUP)).'   ';
										$h .= '</div>';
										if($ePlayer->isPremium()) {
											$h .= '<a data-ajax="/game/action:doEat" post-value="1" class="'.($canEat ? '' : 'disabled').' dropdown-item">'.\Asset::icon('chevron-right').' '.s("Manger une soupe").'  '.\Asset::icon('cup-hot').'</a>';
											if($soup >= 5) {
												$h .= '<a data-ajax="/game/action:doEat" post-value="5" class="dropdown-item">'.\Asset::icon('chevron-right').' '.s("Manger 5 soupes").'  '.str_repeat(\Asset::icon('cup-hot'), 5).'</a>';
											}
										} else {
											$h .= '<a href="/adherer" class="dropdown-item">'.\Asset::icon('chevron-right').' '.s("Disponible pour les adhérents !").'</a>';
										}
									$h .= '</div>';

								$h .= '</div>';

							}

						}

					}

				$h .= '</div>';

			$h .= '</div>';

			$h .= '<div class="game-dashboard-element" style="grid-area: points">';
				$h .= '<h4 class="game-dashboard-title">'.s("Rennes attirés <br/>le 24 décembre").'</h4>';
				$h .= '<div class="game-dashboard-value">';
					$h .= '<div class="game-dashboard-item">🦌 '.$ePlayer['points'].'</div>';
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';
		
		return $h;

	}

	public function get(string $content, int $board): string {

		$h = '<div class="game-desk-wrapper">';
			$h .= '<div class="game-desk" style="background-image: url('.\Asset::getPath('game', 'board-'.$board.'.jpg', 'image').')">';
				$h .= $content;
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

}
?>
