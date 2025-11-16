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

	public function dashboard(Player $ePlayer, \Collection $cFood): string {

		$h = '<div class="game-dashboard util-block">';

				$h .= '<h3>'.encode($ePlayer['name']).'</h3>';

			$startTime = \game\Player::getDailyTime($ePlayer['user']);

			$h .= '<div>';
				$h .= '<h4 class="game-dashboard-title">'.s("Temps de travail<br/>disponible").'</h4>';
				$h .= '<div class="game-dashboard-value">';
					$h .= '<div class="game-dashboard-item">'.\game\PlayerUi::getTime($startTime - $ePlayer['time']).'</div>';
				$h .= '</div>';

				if($ePlayer['time'] > 0) {

					$h .= '<div class="game-dashboard-more">'.s("(retour à {time} à minuit)", ['time' => \game\PlayerUi::getTime($startTime)]).'</div>';

				}

			$h .= '</div>';

			$h .= '<div>';
				$h .= '<h4 class="game-dashboard-title">'.s("Légumes<br/>produits").'</h4>';
				$h .= '<div class="game-dashboard-value game-dashboard-value-list">';

					$hasFood = FALSE;

					foreach($cFood as $eFood) {

						if($eFood['current'] > 0) {

							$h .= '<div class="game-dashboard-item">';

								if($eFood['growing']->notEmpty()) {
									$h .= $eFood['current'].'  '.GrowingUi::getVignette($eFood['growing'], '1.5rem');
								} else {
									$h .= p("{value} soupe", "{value} soupes", $eFood['current']);
								}

								$hasFood = TRUE;

							$h .= '</div>';

						}

					}

					if($hasFood === FALSE) {
						$h .= '<div class="game-dashboard-item">'.s("Aucun").'</div>';
					}

				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div>';
				$h .= '<h4 class="game-dashboard-title">'.s("Rennes attirés<br/>le 24 décembre").'</h4>';
				$h .= '<div class="game-dashboard-value">';
					$h .= '<div class="game-dashboard-item">'.$ePlayer['points'].' 🦌</div>';
				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';
		
		return $h;

	}

	public function get(string $content, int $board): string {

		$h = '<div class="game-desk" style="background-image: url('.\Asset::getPath('game', 'board-'.$board.'.jpg', 'image').')">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

}
?>
