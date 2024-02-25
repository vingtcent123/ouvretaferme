<?php
/**
 * Pour les boutiques
 */
class ShopTemplate extends MainTemplate {

	public string $template = 'default shop';

	public function __construct() {

		parent::__construct();

		Asset::css('shop', 'shop.css');

	}

}
?>
