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

				$p->expectsBuilt('private');

				if($this['private']) {
					return ($value !== NULL);
				} else {
					$value = NULL;
				}

			})
			->setCallback('proPrice.empty', function(?float &$value) use ($p) {

				$p->expectsBuilt('pro');

				if($this['pro']) {
					return ($value !== NULL);
				} else {
					$value = NULL;
				}

			});
		
		parent::build($properties, $input, $p);

	}

}
?>