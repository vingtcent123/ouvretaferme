<?php
namespace game;

class DeskUi {

	public function play(Player $ePlayer, int $board, \Collection $cTile, \Collection $cGrowing): string {

		$h = '<div class="game-boards">';
			$h .= '<a href="/jouer?board=1" class="btn '.($board === 1 ? 'btn-primary' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.\Asset::icon('1-circle-fill').'</div>';
			$h .= '</a>';
			$h .= '<a href="/jouer?board=2" class="btn '.($board === 2 ? 'btn-primary' : '').' '.($ePlayer->getBoards() <= 1 ? 'disabled' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.($ePlayer->getBoards() <= 1 ? \Asset::icon('lock-fill') : \Asset::icon('2-circle-fill')).'</div>';
			$h .= '</a>';
			$h .= '<a href="/jouer?board=3" class="btn '.($board === 3 ? 'btn-primary' : '').' '.($ePlayer->getBoards() <= 2 ? 'disabled' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.($ePlayer->getBoards() <= 2 ? \Asset::icon('lock-fill') : \Asset::icon('3-circle-fill')).'</div>';
			$h .= '</a>';
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

	public function dashboard(Player $ePlayer, \Collection $cGrowing, \Collection $cFood): string {

		$cGrowingFood = $cGrowing->find(fn($eGrowing) => $eGrowing['harvest'] !== NULL);

		$h = '<div class="game-dashboard">';

			$h .= '<h3>'.encode($ePlayer['name']).'</h3>';

			$startTime = $ePlayer->getDailyTime();

			$h .= '<div class="game-dashboard-element">';
				$h .= '<h4 class="game-dashboard-title">'.s("Temps de travail <br/>disponible").'</h4>';
				$h .= '<div>';
					$h .= '<div class="game-dashboard-value">';
						$h .= '<div class="game-dashboard-item">'.\Asset::icon('clock').'  '.\game\PlayerUi::getTime($startTime - $ePlayer['time']).'</div>';
					$h .= '</div>';
					if($ePlayer['time'] !== 0.0) {
						$h .= '<div class="game-dashboard-more">'.s("(retour à {time} à minuit)", ['time' => \game\PlayerUi::getTime($startTime)]).'</div>';
					}
				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="game-dashboard-element">';
				$h .= '<h4 class="game-dashboard-title">'.s("Nourriture <br/>produite").'</h4>';
				$h .= '<div class="game-dashboard-value game-dashboard-value-list">';

					$hasFood = FALSE;
					$minFood = NULL;
					$canCook = $cFood->find(fn($eFood) => ($eFood['growing']->notEmpty() and $eFood['current'] > 0))->count() === $cGrowingFood->count();

					foreach($cFood as $eFood) {

						if($eFood['growing']->notEmpty()) {

							if($eFood['current'] > 0) {

								$h .= '<div class="game-dashboard-item">';
									$h .= GrowingUi::getVignette($eFood['growing'], '1.5rem').'  '.$eFood['current'];
								$h .= '</div>';

								$hasFood = TRUE;
								$minFood = ($minFood === NULL) ? $eFood['current'] : min($eFood['current'], $minFood);

							}

						} else {

							$canEat = $eFood['current'] > 0;

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
										$h .= '<a data-ajax="/game/action:doEat" class="'.($canEat ? '' : 'disabled').' dropdown-item">'.\Asset::icon('chevron-right').' '.s("Manger une soupe").'</a>';
									$h .= '</div>';

									$hasFood = TRUE;

								$h .= '</div>';

							}

						}

					}

					if($hasFood === FALSE) {
						$h .= '<div class="game-dashboard-item">'.s("Aucun").'</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="game-dashboard-element">';
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
