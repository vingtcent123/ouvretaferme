<?php
namespace game;

class DeskUi {

	public function get(string $content = '', ?int $tile = NULL): string {

		$tile ??= mt_rand(1, 24);

		$h = '<div class="game-desk" style="background-image: url('.\Asset::getPath('game', 'tiles-'.$tile.'.jpg', 'image').')">';
			$h .= $content;
		$h .= '</div>';

		return $h;

	}

}
?>
