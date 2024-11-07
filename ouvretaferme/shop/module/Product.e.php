<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
			'date' => ['type', 'farm']
		];

	}
	public function canWrite(): bool {

		return $this['date']->canWrite();

	}

}
?>