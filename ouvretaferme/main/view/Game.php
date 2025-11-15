<?php
class GameTemplate extends MainTemplate {

	public string $template = 'game';
	public ?string $header = '';
	public ?string $footer = '';

	public function __construct() {

		parent::__construct();

		Asset::css('game', 'game.css');
		Asset::js('game', 'game.js');

	}

}
?>
