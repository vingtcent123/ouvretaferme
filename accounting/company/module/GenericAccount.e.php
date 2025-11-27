<?php
namespace company;

class GenericAccount extends GenericAccountElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
				'vatAccount' => ['id', 'class', 'vatRate'],
			];

	}
}
?>
