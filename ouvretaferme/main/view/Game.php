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

	protected function getHead(): string {

		$h = parent::getHead();

		$h .= '<link rel="preconnect" href="https://fonts.googleapis.com">';
		$h .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
		$h .= '<link href="https://fonts.googleapis.com/css2?family='.urlencode('Mystery Quest').'" rel="stylesheet">';

		return $h;

	}

}
?>
