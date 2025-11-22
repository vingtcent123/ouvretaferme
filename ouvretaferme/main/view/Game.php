<?php
class GameTemplate extends MainTemplate {

	public string $template = 'game';
	public ?string $header = '';
	public ?string $footer = '';

	public function __construct() {

		parent::__construct();

		Asset::css('game', 'game.css');

	}

	protected function getHead(): string {

		$h = parent::getHead();
		$h .= \game\DeskUi::getFonts();

		return $h;

	}

}
?>
