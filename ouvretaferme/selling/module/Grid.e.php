<?php
namespace selling;

class Grid extends GridElement {

	public static function getSelection(): array {

		return [
			'id',
			'product', 'customer',
			'price', 'packaging',
			'createdAt', 'updatedAt'
		];

	}

}
?>