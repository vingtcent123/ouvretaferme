<?php
namespace game;

class DeskUi {

	public function play(?int $image = NULL): string {

		$content = '';

		for($tile = 1; $tile <= 16; $tile++) {
			$content .= '<div class="game-tile game-tile-'.$tile.'">';
				$content .= '<a href="/game/:planting?board=1&tile='.$tile.'" class="game-tile-action">+</a>';
			$content .= '</div>';
		}

		return $this->get($content, $image);

	}

	public function get(string $content = '', ?int $image = NULL): string {

		$image ??= mt_rand(1, 10);

		$h = '<div class="game-desk" style="background-image: url('.\Asset::getPath('game', 'tiles-'.$image.'.jpg', 'image').')">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

}
?>
