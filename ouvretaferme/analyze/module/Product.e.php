<?php
namespace analyze;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => ['name', 'variety', 'composition', 'vignette', 'size']
		];

	}

}
?>