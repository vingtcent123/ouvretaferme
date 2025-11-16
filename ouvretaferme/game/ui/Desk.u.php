<?php
namespace game;

class DeskUi {

	public function play(int $board): string {

		$content = '';

		for($tile = 1; $tile <= 16; $tile++) {
			$content .= '<div class="game-tile game-tile-'.$tile.'">';
				$content .= '<a href="/game/:planting?board=1&tile='.$tile.'" class="game-tile-action">+</a>';
			$content .= '</div>';
		}

		return $this->get($content, $board);

	}

	public function get(string $content, int $board): string {

		$h = '<div class="game-desk" style="background-image: url('.\Asset::getPath('game', 'board-'.$board.'.jpg', 'image').')">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

}
?>
