<?php
namespace shop;

class Shared extends SharedElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\FarmElement::getSelection()
		];

	}

}
?>