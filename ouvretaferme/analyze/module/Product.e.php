<?php
namespace analyze;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\ProductElement::getSelection()
		];

	}

}
?>