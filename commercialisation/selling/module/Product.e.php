<?php
namespace selling;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette', 'hasAccounting'],
			'unprocessedPlant' => ['name', 'fqn', 'vignette'],
			'unit' => \selling\Unit::getSelection(),
			'quality' => ['name', 'shortName', 'logo'],
			'stockExpired' => new \Sql('stockUpdatedAt IS NOT NULL AND stockUpdatedAt < NOW() - INTERVAL 7 DAY', 'bool'),
			'proAccount', 'privateAccount',
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

	public function acceptDuplicate(): bool {

		return ($this['profile'] !== Product::COMPOSITION);

	}

	public function getName(string $mode = 'text'): string {

		$this->expects(['name', 'unprocessedVariety', 'mixedFrozen']);

		if($mode === 'text') {

			$h = $this['name'];
			if($this['unprocessedVariety'] !== NULL) {
				$h .= ' / '.$this['unprocessedVariety'];
			}

		} else {

			$h = encode($this['name']);
			if($this['mixedFrozen']) {
				$h .= ' '.ProductUi::getFrozenIcon();
			}
			if($this['unprocessedVariety'] !== NULL) {
				$h .= '<small title="'.s("Variété").'"> / '.encode($this['unprocessedVariety']).'</small>';
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

		return vat_from_excluding($this['proPrice'], SellingSetting::VAT_RATES[$this['vat']]);

	}

	public function calcPrivateVat(): float {

		$this->expects(['vat', 'privatePrice']);

		return vat_from_including($this['privatePrice'], SellingSetting::VAT_RATES[$this['vat']]);

	}

	public static function getProfiles(string $property): array {
		return match($property) {
			'compositionVisibility' => [Product::COMPOSITION],
			'unprocessedPlant' => [Product::UNPROCESSED_PLANT],
			'unprocessedVariety' => [Product::UNPROCESSED_PLANT],
			'mixedFrozen' => [Product::UNPROCESSED_ANIMAL, Product::PROCESSED_FOOD],
			'processedPackaging' => [Product::PROCESSED_FOOD, Product::PROCESSED_PRODUCT],
			'processedAllergen' => [Product::PROCESSED_FOOD],
			'processedComposition' => [Product::PROCESSED_FOOD, Product::PROCESSED_PRODUCT],
		};
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('category.check', function(Category $eCategory) use ($p): bool {

				$this->expects(['farm']);

				if($p->for === 'update') {
					$this['oldCategory'] = $this['category'];
				}

				return (
					$eCategory->empty() or (
						Category::model()
							->select('farm')
							->get($eCategory) and
						$eCategory->canRead()
					)
				);

			})
			->setCallback('processedComposition.prepare', function(?string &$composition): bool {

				if(in_array($this['profile'], Product::getProfiles('processedComposition')) === FALSE) {
					$composition = NULL;
				}

				return TRUE;

			})
			->setCallback('processedAllergen.prepare', function(?string &$allergen): bool {

				if(in_array($this['profile'], Product::getProfiles('processedAllergen')) === FALSE) {
					$allergen = NULL;
				}

				return TRUE;

			})
			->setCallback('processedPackaging.prepare', function(?string &$packaging): bool {

				if(in_array($this['profile'], Product::getProfiles('processedPackaging')) === FALSE) {
					$packaging = NULL;
				}

				return TRUE;

			})
			->setCallback('profile.consistency', function(?string $profile) use ($p): bool {

				return ($p->for === 'create' or $profile !== Product::COMPOSITION);

			})
			->setCallback('mixedFrozen.prepare', function(?bool &$frozen): bool {

				if(in_array($this['profile'], Product::getProfiles('mixedFrozen')) === FALSE) {
					$frozen = NULL;
				}

				return TRUE;

			})
			->setCallback('unprocessedVariety.prepare', function(?string &$variety): bool {

				if(in_array($this['profile'], Product::getProfiles('unprocessedVariety')) === FALSE) {
					$variety = NULL;
				}

				return TRUE;

			})
			->setCallback('unprocessedPlant.prepare', function(\plant\Plant &$ePlant): bool {

				if(in_array($this['profile'], Product::getProfiles('unprocessedPlant')) === FALSE) {
					$ePlant = new \plant\Plant();
				}

				return TRUE;

			})
			->setCallback('unprocessedPlant.check', function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() or (
						\plant\Plant::model()
							->select('farm')
							->get($ePlant) and
						$ePlant->canRead()
					)
				);

			})
			->setCallback('compositionVisibility.check', function(?string &$visibility): bool {

				if(in_array($this['profile'], Product::getProfiles('compositionVisibility')) === FALSE) {
					$visibility = NULL;
					return TRUE;
				} else {
					return in_array($visibility, [Product::PUBLIC, Product::PRIVATE]);
				}

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
			->setCallback('vat.check', function(?int $vat): bool {
				return array_key_exists($vat, SaleLib::getVatRates($this['farm']));
			})
			->setCallback('proOrPrivate.check', function() use ($p): bool {

				if($p->isBuilt('profile') === FALSE) {
					throw new \PropertySkip();
				}

				if($this['profile'] === Product::COMPOSITION) {
					return ((int)$this['pro'] + (int)$this['private']) === 1;
				} else {
					return TRUE;
				}

			})
			->setCallback('privatePrice.prepare', function (?float &$privatePrice): bool {

				if($this['private'] === FALSE) {
					$privatePrice = NULL;
				}

				return TRUE;

			})
			->setCallback('privatePrice.empty', function(?float &$value) use ($p) {

				if(
					$p->isBuilt('profile') === FALSE or
					$this['private'] === FALSE or
					$this['profile'] !== Product::COMPOSITION or
					$p->isInvalid('proOrPrivate')
				) {
					return TRUE;
				}

				return ($value !== NULL);

			})
			->setCallback('privatePriceDiscount.prepare', function (?string &$privatePriceDiscount): bool {

				if(
					$this['private'] === FALSE or
					$privatePriceDiscount === ''
				) {
					$privatePriceDiscount = NULL;
				} else {
					$privatePriceDiscount = (float)$privatePriceDiscount;
				}

				return TRUE;

			})
			->setCallback('privatePriceDiscount.value', function(?float $privatePriceDiscount) use($p): bool {

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

				if($p->isBuilt('privatePrice') === FALSE) {
					throw new \PropertySkip();
				}

				// Reset du prix remisé, privatePrice a déjà été setté correctement
				if($privatePriceDiscount === NULL) {

					if($p->for === 'update' and $this['privatePriceInitial'] !== NULL) {

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

			->setCallback('proPrice.prepare', function (?float &$proPrice): bool {

				if($this['pro'] === FALSE) {
					$proPrice = NULL;
				}

				return TRUE;

			})
			->setCallback('proPriceDiscount.prepare', function (?string &$proPriceDiscount): bool {

				if(
					$this['pro'] === FALSE or
					$proPriceDiscount === ''
				) {
					$proPriceDiscount = NULL;
				} else {
					$proPriceDiscount = (float)$proPriceDiscount;
				}

				return TRUE;

			})
			// Vérifie la cohérence des données
			->setCallback('proPriceDiscount.value', function(?float $proPriceDiscount) use($p): bool {

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

				if($p->isBuilt('proPrice') === FALSE) {
					throw new \PropertySkip();
				}

				// proPrice a déjà été setté
				if($proPriceDiscount === NULL) {

					if($p->for === 'update' and $this['proPriceInitial'] !== NULL) {

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

			})
			->setCallback('proPrice.empty', function(?float &$value) use ($p) {

				if(
					$p->isBuilt('profile') === FALSE or
					$this['pro'] === FALSE or
					$this['profile'] !== Product::COMPOSITION or
					$p->isInvalid('proOrPrivate')
				) {
					return TRUE;
				}

				return ($value !== NULL);

			})
			->setCallback('proOrPrivatePrice.empty', function() use ($p) {

				if(
					$p->isBuilt('proPrice') and
					$p->isBuilt('privatePrice') and
					$p->isBuilt('proOrPrivate') and
					($this['pro'] or $this['private'])
				) {
					return $this['proPrice'] !== NULL or $this['privatePrice'] !== NULL;
				} else {
					return TRUE;
				}

			})
			->setCallback('proAccount.check', function(\account\Account|\company\GenericAccount $eAccount) {

				return $eAccount->empty() or \account\AccountLib::getById($eAccount['id'])->notEmpty();

			})
			->setCallback('privateAccount.check', function(\account\Account|\company\GenericAccount $eAccount) {

				return $eAccount->empty() or \account\AccountLib::getById($eAccount['id'])->notEmpty();

			});

		parent::build($properties, $input, $p);

	}

}
?>
