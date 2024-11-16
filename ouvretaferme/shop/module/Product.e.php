<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
			'date' => ['farm', 'type', 'source', 'catalogs'],
			'catalog' => ['farm']
		];

	}

	public function getTaxes(): string {

		return match($this['type']) {
			Product::PRIVATE => s("TTC"),
			Product::PRO => s("HT"),
		};

	}

	public function canWrite(): bool {

		return $this['date']->canWrite();

	}

}
?>