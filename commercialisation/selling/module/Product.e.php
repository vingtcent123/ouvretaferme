<?php
namespace selling;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette'],
			'plant' => ['name', 'fqn', 'vignette'],
			'unit' => \selling\Unit::getSelection(),
			'quality' => ['name', 'shortName', 'logo'],
			'stockExpired' => new \Sql('stockUpdatedAt IS NOT NULL AND stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool')
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canCreate(): bool {

		$this->expects(['farm']);

		return $this['farm']->canManage();

	}

	public function canWrite(): bool {

		$this->expects(['farm', 'status']);

		return (
			$this['farm']->canManage() and
			$this['status'] !== Product::DELETED
		);

	}

	public function canManage(): bool {
		return $this['farm']->canManage();
	}

	public function acceptEnableStock(): bool {

		return ($this['stock'] === NULL);

	}

	public function acceptDisableStock(): bool {

		return ($this['stock'] !== NULL);

	}

	public function acceptStock(): bool {

		return ($this['stock'] !== NULL);

	}

	public function getName(string $mode = 'text'): string {

		$this->expects(['name', 'variety']);

		if($mode === 'text') {

			$h = $this['name'];
			if($this['variety'] !== NULL) {
				$h .= ' / '.$this['variety'];
			}

		} else {

			$h = encode($this['name']);
			if($this['variety'] !== NULL) {
				$h .= '<small title="'.s("Variété").'"> / '.encode($this['variety']).'</small>';
			}

		}

		return $h;

	}

	public function calcProMagicPrice(bool $hasVat): ?float {

		$this->expects(['vat', 'proPrice']);

		if($this['privatePrice'] === NULL) {
			return NULL;
		}

		if($hasVat) {
			return $this['privatePrice'] - $this->calcPrivateVat();
		} else {
			return $this['privatePrice'];
		}

	}

	public function calcPrivateMagicPrice(bool $hasVat): ?float {

		$this->expects(['vat', 'proPrice']);

		if($this['proPrice'] === NULL) {
			return NULL;
		}

		if($hasVat) {
			return $this['proPrice'] + $this->calcProVat();
		} else {
			return $this['proPrice'];
		}


	}

	public function calcProVat(): float {

		$this->expects(['vat', 'proPrice']);

		return vat_from_excluding($this['proPrice'], \Setting::get('selling\vatRates')[$this['vat']]);

	}

	public function calcPrivateVat(): float {

		$this->expects(['vat', 'privatePrice']);

		return vat_from_including($this['privatePrice'], \Setting::get('selling\vatRates')[$this['vat']]);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('category.check', function(Category $eCategory): bool {

				$this->expects(['farm']);

				return (
					$eCategory->empty() or (
						Category::model()
							->select('farm')
							->get($eCategory) and
						$eCategory->canRead()
					)
				);

			})
			->setCallback('plant.check', function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() or (
						\plant\Plant::model()
							->select('farm')
							->get($ePlant) and
						$ePlant->canRead()
					)
				);

			})
			->setCallback('unit.check', function(Unit $eUnit) use($p): bool {

				$this->expects(['farm']);

				if($eUnit->empty()) {
					return TRUE;
				}

				if(
					Unit::model()
						->select('farm', 'fqn', 'approximate')
						->get($eUnit) and
					$eUnit->canRead()
				) {

					if($p->for === 'create') {
						return TRUE;
					} else {
						return ($eUnit['approximate'] === FALSE);
					}

				} else {
					return FALSE;
				}

			})
			->setCallback('compositionVisibility.check', function(?string &$visibility): bool {

				$this->expects(['composition']);

				if($this['composition']) {
					return in_array($visibility, [Product::PUBLIC, Product::PRIVATE]);
				} else {
					$visibility = NULL;
					return TRUE;
				}

			})
			->setCallback('vat.check', function(int $vat): bool {
				return array_key_exists($vat, SaleLib::getVatRates($this['farm']));
			})
			->setCallback('proOrPrivate.check', fn() => ((int)$this['pro'] + (int)$this['private']) === 1)
			->setCallback('privatePrice.empty', function(?float &$value) use ($p) {

				if($p->for === 'create') {
					$p->expectsBuilt('private');
				} else {
					$this->expects(['private']);
				}

				if($this['private']) {
					return ($value !== NULL);
				} else {
					$value = NULL;
				}

				return TRUE;

			})
			->setCallback('proPrice.empty', function(?float &$value) use ($p) {

				if($p->for === 'create') {
					$p->expectsBuilt('pro');
				} else {
					$this->expects(['pro']);
				}

				if($this['pro']) {
					return ($value !== NULL);
				} else {
					$value = NULL;
				}

				return TRUE;

			})
			// Quick uniquement
			->setCallback('privatePrice.discount', function(?string $value) use ($p) {

				if(count($p->getBuilt()) !== 0 or $this['privatePriceInitial'] === NULL) {
					return TRUE;
				}

				$this['privatePriceInitial'] = var_filter($value, 'float');
				$p->addBuilt('privatePriceInitial');

				throw new \PropertySkip();

			})
			->setCallback('privatePriceDiscount.check', function(?string $privatePriceDiscount) use($p, $input): bool {

				if($p->isBuilt('privatePrice') === FALSE) {
					return TRUE;
				}

				if(empty($privatePriceDiscount)) {
					$this['privatePriceInitial'] = NULL;
				} else {
					$this['privatePriceInitial'] = $this['privatePrice'];
					$this['privatePrice'] = var_filter($privatePriceDiscount, 'float');
					$p->addBuilt('privatePriceInitial');
				}

				return TRUE;

			})
			// Quick uniquement
			->setCallback('privatePriceDiscount.discount', function(?string $privatePriceDiscount) use($p, $input): bool {

				if(count($p->getBuilt()) !== 0) {
					return TRUE;
				}

				if(empty($privatePriceDiscount)) {
					$this['privatePriceInitial'] = NULL;
				} else {
					$this['privatePrice'] = var_filter($privatePriceDiscount, 'float');
					$p->addBuilt('privatePrice');
				}

				return TRUE;

			})
			->setCallback('privatePriceDiscount.value', function() use($p): bool {

				if($p->isBuilt('privatePrice') === FALSE or $p->isBuilt('privatePriceInitial') === FALSE) {
					return TRUE;
				}

				return $this['privatePriceInitial'] > $this['privatePrice'];

			})
			// Quick uniquement
			->setCallback('proPrice.discount', function(?string $value) use ($p) {

				if(count($p->getBuilt()) !== 0 or $this['proPriceInitial'] === NULL) {
					return TRUE;
				}

				$this['proPriceInitial'] = var_filter($value, 'float');
				$p->addBuilt('proPriceInitial');

				throw new \PropertySkip();

			})
			->setCallback('proPriceDiscount.check', function(?string $proPriceDiscount) use($p, $input): bool {

				if($p->isBuilt('proPrice') === FALSE) {
					return TRUE;
				}

				if(empty($proPriceDiscount)) {
					$this['proPriceInitial'] = NULL;
				} else {
					$this['proPriceInitial'] = $this['proPrice'];
					$this['proPrice'] = var_filter($proPriceDiscount, 'float');
					$p->addBuilt('proPriceInitial');
				}

				return TRUE;

			})
			// Quick uniquement
			->setCallback('proPriceDiscount.discount', function(?string $proPriceDiscount) use($p, $input): bool {

				if(count($p->getBuilt()) !== 0) {
					return TRUE;
				}

				if(empty($proPriceDiscount)) {
					$this['proPriceInitial'] = NULL;
				} else {
					$this['proPrice'] = var_filter($proPriceDiscount, 'float');
					$p->addBuilt('proPrice');
				}

				return TRUE;

			})
			->setCallback('proPriceDiscount.value', function() use($p): bool {

				if($p->isBuilt('proPrice') === FALSE or $p->isBuilt('proPriceInitial') === FALSE) {
					return TRUE;
				}

				return $this['proPriceInitial'] > $this['proPrice'];

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
