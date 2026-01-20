<?php
namespace selling;

class Item extends ItemElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'sale' => SaleElement::getSelection(),
			'customer' => ['name', 'type'],
			'farm' => ['name', 'hasAccounting', 'legalCountry'],
			'unit' => \selling\Unit::getSelection(),
			'product' => ProductElement::getSelection() + [
				'unit' => \selling\Unit::getSelection(),
			],
			'quality' => ['name', 'fqn', 'logo'],
			'account',
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
			'sale' => ['preparationStatus']
		]);

		if(
			$this->canRead() === FALSE or
			$this['sale']->isMarketSale()
		) {
			return FALSE;
		}

		if($this['sale']->isComposition()) {
			return $this['sale']->acceptUpdateComposition();
		} else {
			return (
				in_array($this['sale']['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED])
			);
		}

	}

	public function canWriteAccounting(): bool {

		$this->expects([
			'sale' => ['preparationStatus']
		]);

		if(
			$this->canRead() === FALSE or
			$this['sale']->isMarketSale()
		) {
			return FALSE;
		}

		if($this['sale']->isComposition()) {
			return $this['sale']->acceptUpdateComposition();
		} else {
			return in_array($this['sale']['preparationStatus'], [Sale::DRAFT, Sale::CONFIRMED, Sale::PREPARED, Sale::DELIVERED]);
		}

	}

	public function canDelete(): bool {

		$this->expects([
			'sale' => ['profile']
		]);

		if($this['sale']->isMarketSale() === FALSE) {
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
							->select(ProductElement::getSelection())
							->whereStatus(Product::ACTIVE)
							->whereFarm($this['farm'])
							->get($eProduct)
					);

				} else {
					return TRUE;
				}


			})
			->setCallback('product.composition', function(Product $eProduct) use ($p): bool {

				if(
					$p->isInvalid('product') or
					$eProduct->empty() or
					$this['sale']->isComposition() === FALSE
				) {
					return TRUE;
				}

				return ($eProduct['profile'] !== Product::COMPOSITION);

			})
			->setCallback('nature.check', function(?string $nature) use ($p): bool {

				if($p->isInvalid('product')) {
					return TRUE;
				}

				if($this['product']->notEmpty()) {
					return TRUE;
				} else {
					return in_array($nature, [Item::GOOD, Item::SERVICE]);
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
			->setCallback('quality.prepare', function(?string &$quality): bool {

				if($this['nature'] === Item::SERVICE) {
					$quality = Item::NO;
				}

				return TRUE;

			})
			->setCallback('name.prepare', function(?string &$name) use($p): bool {

				if($p->for === 'create') {

					if(
						$p->isBuilt('product') and
						$this['product']->notEmpty()
					) {

						$name = $this['product']->getName();

					}

				}

				return TRUE;

			})
			->setCallback('packaging.prepare', function(?float &$packaging): bool {

				if($this['nature'] === Item::SERVICE) {
					$packaging = NULL;
				}

				return TRUE;

			})
			->setCallback('number.empty', function(?float $number) use($p): bool {

				$this->expects([
					'sale' => ['profile'],
				]);

				if(($p->isBuilt('locked') and $this['locked'] === Item::NUMBER) or $this['sale']->isMarket()) {
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
			->setCallback('unit.prepare', function(Unit &$eUnit): bool {

				if($this['nature'] === Item::SERVICE) {
					$eUnit = new Unit();
				}

				return TRUE;

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
			->setCallback('unitPriceDiscount.check', function(?string &$unitPriceDiscount) use($p, $input): bool {

				if($p->isBuilt('unitPrice') === FALSE) {
					$unitPriceDiscount = NULL;
					return TRUE;
				}

				if(empty($unitPriceDiscount)) {
					$unitPriceDiscount = NULL;
				} else {
					$unitPriceDiscount = (float)$unitPriceDiscount;
				}

				return TRUE;

			})
			->setCallback('unitPriceDiscount.value', function(?float &$unitPriceDiscount) use($p): bool {

				if($p->isBuilt('unitPrice') === FALSE or $unitPriceDiscount === NULL) {
					return TRUE;
				}

				return $this['unitPrice'] > $unitPriceDiscount;

			})
			->setCallback('unitPriceDiscount.setValue', function(?float $unitPriceDiscount) use($p): bool {

				if($p->isBuilt('unitPrice') === FALSE) {
					return TRUE;
				}

				if($unitPriceDiscount === NULL) {
					$this['unitPriceInitial'] = NULL;
					$p->addBuilt('unitPriceInitial');
					return TRUE;
				}

				$this['unitPriceInitial'] = $this['unitPrice'];
				$this['unitPrice'] = $unitPriceDiscount;
				$p->addBuilt('unitPriceInitial');

				return TRUE;

			})
			->setCallback('price.locked', function(?float $price) use($p): bool {

				return (
					$price !== NULL or
					($p->isBuilt('locked') and $this['locked'] === Item::PRICE)
				);

			})
			->setCallback('account.check', function(?\account\Account $eAccount): bool {

				if($eAccount->empty()) {
					return TRUE;
				}

				$eAccount = \account\AccountLib::getById($eAccount['id']);

				return (
					\account\AccountLabelLib::isFromClass($eAccount['class'], \account\AccountSetting::PRODUCT_SOLD_ACCOUNT_CLASS) or
					in_array((int)mb_substr($eAccount['class'], 0, 3), \account\AccountSetting::WAITING_ACCOUNT_CLASSES)
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>
