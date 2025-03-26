<?php
namespace shop;

class Share extends ShareElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\FarmElement::getSelection()
		];

	}

}
?>