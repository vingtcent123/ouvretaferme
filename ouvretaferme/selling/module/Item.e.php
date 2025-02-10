<?php
namespace selling;

class Item extends ItemElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sale' => ['farm', 'hasVat', 'type', 'taxes', 'shippingVatRate', 'shippingVatFixed', 'document', 'preparationStatus', 'market', 'marketParent', 'shipping'],
			'customer' => ['name', 'type'],
			'unit' => ['fqn', 'by', 'singular', 'plural', 'short', 'type'],
			'product' => [
				'name', 'variety', 'description', 'vignette', 'size', 'origin', 'plant',
				'unit' => ['fqn', 'by', 'singular', 'plural', 'short', 'type'],
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

	public function canDelete(): bool {

		$this->expects([
			'sale' => ['marketParent']
		]);

		if($this['sale']['marketParent']->empty()) {
			return $this->canWrite();
		} else {

			return (
				$this->canRead() and
				$this['sale']['preparationStatus'] === Sale::DRAFT
			);

		}

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$this->expects([
			'farm',
			'sale' => ['taxes']
		]);

		return parent::build($properties, $input, $callbacks + [

			'product.check' => function(Product $eProduct): bool {

				if($eProduct->notEmpty()) {

					return (
						Product::model()
							->select('id', 'name', 'farm')
							->whereStatus(Product::ACTIVE)
							->get($eProduct) and
						$eProduct->validateProperty('farm', $this['farm'])
					);

				} else {
					return TRUE;
				}


			},

			'vatRate.check' => function(?float &$vatRate): bool {

				if($this['sale']['hasVat'] === FALSE) {
					$vatRate = 0.0;
					return TRUE;
				} else {
					return ($vatRate !== NULL);
				}

			},

			'name.prepare' => function(?string &$name): bool {

				if($this['product']->notEmpty()) {
					$name = $this['product']['name'];
				}

				return TRUE;

			},

			'number.empty' => function(?float $number, \BuildProperties $p): bool {

				$this->expects([
					'sale' => ['market'],
				]);

				if(($p->isBuilt('locked') and $this['locked'] === Item::NUMBER) or $this['sale']['market']) {
					return TRUE;
				} else {
					return ($number !== NULL);
				}

			},

			'number.division' => function(?float $number, \BuildProperties $p): bool {

				return (
					($p->isBuilt('locked') and $this['locked'] !== Item::UNIT_PRICE) or
					$number !== 0.0
				);

			},

			'unit.check' => function(Unit $eUnit): bool {

				$this->expects(['farm']);

				return (
					$eUnit->empty() or (
						Unit::model()
							->select('farm')
							->get($eUnit) and
						$eUnit->canRead()
					)
				);
				
			},

			'unitPrice.division' => function(?float $unitPrice, \BuildProperties $p): bool {

				return (
					($p->isBuilt('locked') and $this['locked'] !== Item::NUMBER) or
					$unitPrice !== 0.0
				);

			},

			'price.locked' => function(?float $price, \BuildProperties $p): bool {

				return (
					$price !== NULL or
					($p->isBuilt('locked') and $this['locked'] === Item::PRICE)
				);

			},

		]);

	}

}
?>