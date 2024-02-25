<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
		];

	}
	public function canWrite(): bool {

		return DateLib::getShopById($this['date']['id'])->canWrite();

	}

	public function isInStock(): bool {

		$this->expects(['stock', 'sold']);

		return ($this['stock'] === NULL or $this['stock'] > $this['sold']);

	}

	public function getRemainingStock(): float {

		return ($this['stock'] - $this['sold']);

	}

}
?>