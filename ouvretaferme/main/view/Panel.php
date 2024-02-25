<?php
/**
 * Affichage d'une panel de navigation
 */
class PanelTemplate extends BaseTemplate {

	use SmartPanelTemplate;

	public function __construct() {

		parent::__construct();

		$this->template = 'panel';

		\Asset::css('main', 'design.css');

	}

}
?>
