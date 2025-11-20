<?php
namespace game;

class DeskUi {

	public function play(Player $ePlayer, \Collection $cTile, int $board): string {

		$h = '<div class="game-boards">';
			$h .= '<a href="/jouer?board=1" class="btn '.($board === 1 ? 'btn-primary' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.\Asset::icon('1-circle-fill').'</div>';
			$h .= '</a>';
			$h .= '<a href="/jouer?board=2" class="btn '.($board === 2 ? 'btn-primary' : '').' '.($ePlayer['boards'] <= 1 ? 'disabled' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.($ePlayer['boards'] <= 1 ? \Asset::icon('lock-fill') : \Asset::icon('2-circle-fill')).'</div>';
			$h .= '</a>';
			$h .= '<a href="/jouer?board=3" class="btn '.($board === 3 ? 'btn-primary' : '').' '.($ePlayer['boards'] <= 2 ? 'disabled' : '').' game-board btn-lg">';
				$h .= s("Plateau");
				$h .= '<div class="game-board-label">'.($ePlayer['boards'] <= 2 ? \Asset::icon('lock-fill') : \Asset::icon('3-circle-fill')).'</div>';
			$h .= '</a>';
		$h .= '</div>';

		if($ePlayer['boards'] < $board) {
			return $h;
		}

		$content = '';

		for($tile = 1; $tile <= 16; $tile++) {
			$content .= '<div class="game-tile game-tile-'.$tile.'">';
				$content .= '<a href="/game/:planting?board=1&tile='.$tile.'" class="game-tile-action">+</a>';
			$content .= '</div>';
		}

		$h .= $this->get($content, $board);

		return $h;

	}

	public function dashboard(Player $ePlayer, \Collection $cGrowing, \Collection $cFood): string {

		$cGrowingFood = $cGrowing->find(fn($eGrowing) => $eGrowing['harvest'] !== NULL);

		$h = '<div class="game-dashboard">';

			$h .= '<h3>'.encode($ePlayer['name']).'</h3>';

			$startTime = \game\Player::getDailyTime($ePlayer['user']);

			$h .= '<div class="game-dashboard-element">';
				$h .= '<h4 class="game-dashboard-title">'.s("Temps de travail <br/>disponible").'</h4>';
				$h .= '<div class="game-dashboard-value">';
					$h .= '<div class="game-dashboard-item">'.\Asset::icon('clock').'  '.\game\PlayerUi::getTime($startTime - $ePlayer['time']).'</div>';
				$h .= '</div>';

				if($ePlayer['time'] > 0) {

					$h .= '<div class="game-dashboard-more">'.s("(retour à {time} à minuit)", ['time' => \game\PlayerUi::getTime($startTime)]).'</div>';

				}

			$h .= '</div>';

			$h .= '<div class="game-dashboard-element">';
				$h .= '<h4 class="game-dashboard-title">'.s("Nourriture <br/>produite").'</h4>';
				$h .= '<div class="game-dashboard-value game-dashboard-value-list">';

					$hasFood = FALSE;
					$canCook = $cFood->find(fn($eFood) => $eFood['current'] > 0)->count() === $cGrowingFood->count();

					foreach($cFood as $eFood) {

						if($eFood['growing']->notEmpty()) {

							if($eFood['current'] > 0) {
								$h .= '<div class="game-dashboard-item">';
									$h .= GrowingUi::getVignette($eFood['growing'], '1.5rem').'  '.$eFood['current'];
								$h .= '</div>';
							}

						} else {

							$canEat = $eFood['current'] > 0;

							if(
								$canEat or
								$canCook
							) {

								$h .= '<div class="game-dashboard-item">';

									if($eFood['growing']->notEmpty()) {
										$h .= $eFood['current'].'  '.GrowingUi::getVignette($eFood['growing'], '1.5rem');
									} else {
										$h .= '<a class="dropdown-toggle" data-dropdown="bottom-end">'.\Asset::icon('cup-hot').'  '.p("{value} soupe", "{value} soupes", $eFood['current']).'</a>';
										$h .= '<div class="dropdown-list">';
											$h .= '<div class="dropdown-subtitle">'.s("Côté cuisine").'</div>';
											$h .= '<div class="dropdown-text">';
												foreach($cGrowingFood as $eGrowing) {
													$h .= '-1 '.GrowingUi::getVignette($eGrowing, '1.5rem').'   ';
												}
												$h .= \Asset::icon('arrow-right').'   ';
												$h .= '+1 '.\Asset::icon('cup-hot');
											$h .= '</div>';
											$h .= '<a href="" class="'.($canCook ? '' : 'disabled').' dropdown-item">'.\Asset::icon('cup-hot').'  '.s("Cuisiner une soupe").'</a>';
											$h .= '<div class="dropdown-divider"></div>';
											$h .= '<div class="dropdown-subtitle">'.s("Côté salon").'</div>';
											$h .= '<div class="dropdown-text">';
												$h .= '-1 '.\Asset::icon('cup-hot').' '.\Asset::icon('arrow-right').' ';
												$h .= s("+{value} de temps de travail disponible", PlayerUi::getTime(GameSetting::BONUS_SOUP)).'   ';
											$h .= '</div>';
											$h .= '<a href="" class="'.($canEat ? '' : 'disabled').' dropdown-item">'.s("Manger une soupe").'</a>';
										$h .= '</div>';
									}

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
