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
		return \selling\CustomerUi::getTaxes($this['type']);
	}

	public function canWrite(): bool {

		if($this['date']->notEmpty()) {
			return $this['date']->canWrite();
		} else if($this['catalog']->notEmpty()) {
			return $this['catalog']->canWrite();
		} else {
			throw new \Exception('No date, no catalog');
		}

	}

}
?>