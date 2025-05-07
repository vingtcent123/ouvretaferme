<?php
namespace selling;

class Item extends ItemElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sale' => ['farm', 'hasVat', 'type', 'taxes', 'shippingVatRate', 'shippingVatFixed', 'document', 'preparationStatus', 'shop', 'shopShared', 'market', 'marketParent', 'compositionOf', 'compositionEndAt', 'shipping', 'deliveredAt'],
			'customer' => ['name', 'type'],
			'farm' => ['name'],
			'unit' => \selling\Unit::getSelection(),
			'product' => [
				'name', 'farm', 'variety', 'description', 'vignette', 'size', 'origin', 'plant',
				'composition', 'compositionVisibility',
				'unit' => \selling\Unit::getSelection(),
				'privatePrice',
				'quality' => ['name', 'logo']
			],
			'quality' => ['name', 'fqn', 'logo']
		];

	}

	public static function containsApproximate(\Collection $cItem) {
		return $cItem->contains(fn($eItem) => ($eItem['product']['unit']->notEmpty() and $eItem['product']['unit']['approximate']));
	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects([
			'sale' => ['preparationStatus', 'marketParent']
		]);

		if(
			$this->canRead() === FALSE or
			$this['sale']['marketParent']->notEmpty()
		) {
			return FALSE;
		}

		if($this['sale']->isComposition()) {
			return $this['sale']->acceptWriteComposition();
		} else {
			return in_array($this['sale']['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED]);
		}

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

	public function build(array $properties, array $input, \Properties $p = new \Properties(), array $callbacks = []): void {

		$this->expects([
			'farm',
			'sale' => ['taxes']
		]);
		
		$p
			->setCallback('product.check', function(Product $eProduct): bool {

				if($eProduct->notEmpty()) {

					return (
						Product::model()
							->select('id', 'name', 'variety', 'farm', 'composition')
							->whereStatus(Product::ACTIVE)
							->get($eProduct) and
						$eProduct->validateProperty('farm', $this['farm'])
					);

				} else {
					return TRUE;
				}


			})
			->setCallback('vatRate.check', function(?float &$vatRate): bool {

				if($this['sale']['hasVat'] === FALSE) {
					$vatRate = 0.0;
					return TRUE;
				} else {
					return ($vatRate !== NULL);
				}

			})
			->setCallback('name.prepare', function(?string &$name) use($p): bool {

				if($p->for === 'create') {

					if($this['product']->notEmpty()) {

						$name = $this['product']->getName();

					}

				}

				return TRUE;

			})
			->setCallback('number.empty', function(?float $number) use($p): bool {

				$this->expects([
					'sale' => ['market'],
				]);

				if(($p->isBuilt('locked') and $this['locked'] === Item::NUMBER) or $this['sale']['market']) {
					return TRUE;
				} else {
					return ($number !== NULL);
				}

			})
			->setCallback('number.division', function(?float $number) use($p): bool {

				return (
					($p->isBuilt('locked') and $this['locked'] !== Item::UNIT_PRICE) or
					$number !== 0.0
				);

			})
			->setCallback('unit.check', function(Unit $eUnit): bool {

				$this->expects(['farm']);

				return (
					$eUnit->empty() or (
						Unit::model()
							->select('farm')
							->get($eUnit) and
						$eUnit->canRead()
					)
				);
				
			})
			->setCallback('unitPrice.division', function(?float $unitPrice) use($p): bool {

				return (
					($p->isBuilt('locked') and $this['locked'] !== Item::NUMBER) or
					$unitPrice !== 0.0
				);

			})
			->setCallback('price.locked', function(?float $price) use($p): bool {

				return (
					$price !== NULL or
					($p->isBuilt('locked') and $this['locked'] === Item::PRICE)
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>