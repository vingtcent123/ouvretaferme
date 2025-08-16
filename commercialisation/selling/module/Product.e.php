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

		$this->expects(['vat', 'privatePrice']);

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


			->setCallback('privatePriceInitial.check', function() use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'privatePriceInitial'));
				return TRUE;

			})
			->setCallback('privatePriceInitial.value', function(?float $privatePriceInitial): bool {

				if($privatePriceInitial === NULL) {
					return TRUE;
				}

				// Si Quick
				if($this->isQuick()) {
					return $privatePriceInitial > $this['privatePrice'];
				}

				return TRUE;

			})

			// Set si quick
			->setCallback('privatePrice.check', function() use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'privatePrice'));
				return TRUE;

			})
			// Vérifie la cohérence des données, valable pour quick et pas quick
			->setCallback('privatePrice.value', function(?float $privatePrice) use($p): bool {

				// Empty déjà checké
				if($privatePrice === NULL or $p->for === 'create') {
					return TRUE;
				}

				// Si Quick
				if($this->isQuick()) {
					if($this['privatePriceInitial'] === NULL) {
						return TRUE;
					}
					return $privatePrice < $this['privatePriceInitial'];
				}

				// Si pas quick, pas de vérification (sera faite dans privatePriceDiscount.value)
				return TRUE;

			})
			->setCallback('privatePrice.setValue', function(?float $privatePrice) use($p): bool {

				// Si Quick + Existence d'un prix remisé => va dans privatePriceInitial (sinon dans privatePrice et c'est privatePriceDiscount qui gèreà-)
				if($this->isQuick() and $privatePrice !== NULL and $this['privatePriceInitial']) {

					$this['privatePriceInitial'] = $privatePrice;
					$p->addBuilt('privatePriceInitial');

					throw new \PropertySkip();
				}

				return TRUE;
			})
			// Formatte privatePriceDiscount et set si quick
			->setCallback('privatePriceDiscount.check', function(?string &$privatePriceDiscount) use($input): bool {

				$privatePriceDiscount = empty($privatePriceDiscount) ? NULL : (float)$privatePriceDiscount;

				$this->setQuick((($input['property'] ?? NULL) === 'privatePriceDiscount'));

				// Si Quick, il faut avoir un prix initial
				if($this->isQuick() and $this['privatePriceInitial'] === NULL) {
					return FALSE;
				}

				return TRUE;

			})
			// Vérifie la cohérence des données
			->setCallback('privatePriceDiscount.value', function(?float $privatePriceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					if($privatePriceDiscount === NULL) {
						return TRUE;
					}

					return $this['privatePriceInitial'] > $privatePriceDiscount;
				}

				// Si pas quick
				if($p->isBuilt('privatePrice') === FALSE) {
					return TRUE;
				}

				if($privatePriceDiscount === NULL) {
					return TRUE;
				}
				return $this['privatePrice'] > $privatePriceDiscount;

			})
			// Set les valeurs dans l'élement
			->setCallback('privatePriceDiscount.setValue', function(?float $privatePriceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					// Reset du prix remisé
					if($privatePriceDiscount === NULL and $this['privatePriceInitial'] !== NULL) {

						$this['privatePrice'] = $this['privatePriceInitial'];
						$this['privatePriceInitial'] = NULL;
						$p->addBuilt('privatePriceInitial');
						$p->addBuilt('privatePrice');
						throw new \PropertySkip();

					}

					// Modif du prix remisé
					if($privatePriceDiscount !== NULL) {

						$this['privatePrice'] = $privatePriceDiscount;
						$p->addBuilt('privatePrice');
						throw new \PropertySkip();

					}
				}

				if($this->isQuick() === FALSE and $p->isBuilt('privatePrice') === FALSE) {
					throw new \PropertySkip();
				}

				// Reset du prix remisé, privatePrice a déjà été setté correctement
				if($privatePriceDiscount === NULL) {

					if($this['privatePriceInitial'] !== NULL) {

						$this['privatePriceInitial'] = NULL;
						$p->addBuilt('privatePriceInitial');

					}

					throw new \PropertySkip();
				}

				$this['privatePriceInitial'] = $this['privatePrice'];
				$this['privatePrice'] = $privatePriceDiscount;
				$p->addBuilt('privatePriceInitial');
				$p->addBuilt('privatePrice');

				throw new \PropertySkip();

			})


			->setCallback('proPriceInitial.check', function() use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'proPriceInitial'));
				return TRUE;

			})
			->setCallback('proPriceInitial.value', function(?float $proPriceInitial): bool {

				if($proPriceInitial === NULL) {
					return TRUE;
				}

				// Si Quick
				if($this->isQuick()) {
					return $proPriceInitial > $this['proPrice'];
				}

				return TRUE;

			})


			// Set si quick
			->setCallback('proPrice.check', function() use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'proPrice'));
				return TRUE;

			})
			// Vérifie la cohérence des données, valable pour quick et pas quick
			->setCallback('proPrice.value', function(?float $proPrice) use($p): bool {

				// Empty déjà checké
				if($proPrice === NULL or $p->for === 'create') {
					return TRUE;
				}

				// Si Quick
				if($this->isQuick()) {
					if($this['proPriceInitial'] === NULL) {
						return TRUE;
					}
					return $proPrice > $this['proPrice'];
				}

				// Si pas quick, pas de vérification (sera faite dans proPriceDiscount.value)
				return TRUE;

			})
			->setCallback('proPrice.setValue', function(?float $proPrice) use($p): bool {

				// Si Quick + Existence d'un prix remisé => va dans privatePriceInitial (sinon dans privatePrice et c'est privatePriceDiscount qui gèrera)
				if($this->isQuick() and $proPrice !== NULL and $this['proPriceInitial']) {

					$this['proPriceInitial'] = $proPrice;
					$p->addBuilt('proPriceInitial');

					throw new \PropertySkip();
				}

				return TRUE;
			})
			// Formatte proPriceDiscount
			->setCallback('proPriceDiscount.check', function(?string &$proPriceDiscount) use($input): bool {

				$proPriceDiscount = empty($proPriceDiscount) ? NULL : (float)$proPriceDiscount;
				$this->setQuick((($input['property'] ?? NULL) === 'proPriceDiscount'));

				// Si Quick, il faut avoir un prix initial
				if($this->isQuick() and $this['proPriceInitial'] === NULL) {
					return FALSE;
				}

				return TRUE;

			})
			// Vérifie la cohérence des données
			->setCallback('proPriceDiscount.value', function(?float $proPriceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					if($proPriceDiscount === NULL) {
						return TRUE;
					}

					return $this['proPriceInitial'] > $proPriceDiscount;
				}

				// Si pas quick
				if($p->isBuilt('proPrice') === FALSE) {
					return TRUE;
				}

				if($proPriceDiscount === NULL) {
					return TRUE;
				}
				return $this['proPrice'] > $proPriceDiscount;

			})
			// Set les valeurs dans l'élement
			->setCallback('proPriceDiscount.setValue', function(?float $proPriceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					// Reset du prix remisé
					if($proPriceDiscount === NULL and $this['proPriceInitial'] !== NULL) {

						$this['proPrice'] = $this['proPriceInitial'];
						$this['proPriceInitial'] = NULL;
						$p->addBuilt('proPriceInitial');
						$p->addBuilt('proPrice');
						throw new \PropertySkip();

					}

					// Modif du prix remisé
					if($proPriceDiscount !== NULL) {

						$this['proPrice'] = $proPriceDiscount;
						$p->addBuilt('proPrice');
						throw new \PropertySkip();

					}
				}

				if($this->isQuick() === FALSE and $p->isBuilt('proPrice') === FALSE) {
					throw new \PropertySkip();
				}

				// proPrice a déjà été setté
				if($proPriceDiscount === NULL) {

					if($this['proPriceInitial'] !== NULL) {

						$this['proPriceInitial'] = NULL;
						$p->addBuilt('proPriceInitial');

					}

					throw new \PropertySkip();
				}

				$this['proPriceInitial'] = $this['proPrice'];
				$this['proPrice'] = $proPriceDiscount;
				$p->addBuilt('proPriceInitial');
				$p->addBuilt('proPrice');

				throw new \PropertySkip();

			});

		parent::build($properties, $input, $p);

	}

}
?>
