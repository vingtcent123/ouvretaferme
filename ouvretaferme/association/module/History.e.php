<?php
namespace association;

class History extends HistoryElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => \farm\Farm::getSelection(),
		];

	}

}
?>
