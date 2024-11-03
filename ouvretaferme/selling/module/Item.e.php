<?php
namespace selling;

class Item extends ItemElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sale' => ['farm', 'hasVat', 'taxes', 'shippingVatRate', 'shippingVatFixed', 'document', 'preparationStatus', 'market', 'marketParent', 'shipping'],
			'customer' => ['name', 'type'],
			'product' => [
				'name', 'variety', 'description', 'vignette', 'size', 'unit', 'plant',
				'privatePrice',
				'quality' => ['name', 'logo']
			],
			'quality' => ['name', 'fqn', 'logo']
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects([
			'sale' => ['preparationStatus', 'marketParent']
		]);

		return (
			$this->canRead() and
			$this['sale']['marketParent']->empty() and
			in_array($this['sale']['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED])
		);

	}

	public function isIntegerUnit(): bool {
		return in_array($this['unit'], [NULL, Item::UNIT, Item::BUNCH, Item::BOX, Item::PLANT, Item::GRAM_250, Item::GRAM_500]);
	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$this->expects([
			'sale' => ['taxes']
		]);

		return parent::build($properties, $input, $callbacks + [

			'vatRate.check' => function(?float &$vatRate): bool {

				if($this['sale']['hasVat'] === FALSE) {
					$vatRate = 0.0;
					return TRUE;
				} else {
					return ($vatRate !== NULL);
				}

			},

			'number.empty' => function(?float $number): bool {

				$this->expects([
					'sale' => ['market']
				]);

				if($this['sale']['market']) {
					return TRUE;
				} else {
					return ($number !== NULL);
				}

			},

			'price.locked' => function(?float $price): bool {

				try {
					$this->expects(['locked']);
				} catch(\Exception) {
					return FALSE;
				}

				return (
					$price !== NULL or
					$this['locked'] === 'price'
				);

			},

		]);

	}

}
?>