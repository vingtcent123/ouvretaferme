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

	public function isInStock(): bool {

		$this->expects(['stock', 'sold']);

		return ($this['stock'] === NULL or $this['stock'] > $this['sold']);

	}

	public function getRemainingStock(): float {

		return ($this['stock'] - $this['sold']);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'stock.prepare' => function(?float &$stock): bool {

				$this->expects([
					'date' => ['type']
				]);

				if(
					$stock !== NULL and
					$this['date']['type'] === Date::PRO
				) {
					$stock = (int)$stock;
				}

				return TRUE;

			}

		]);

	}

}
?>