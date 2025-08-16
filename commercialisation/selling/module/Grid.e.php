<?php
namespace selling;

class Grid extends GridElement {

	public static function getSelection(): array {

		return [
			'id',
			'product', 'customer',
			'price', 'priceInitial', 'packaging',
			'createdAt', 'updatedAt'
		];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('price.value', function(?float $price) use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'price'));

				if($this->isQuick() === FALSE or $this['priceInitial'] === NULL) {
					return TRUE;
				}

				return $price < $this['priceInitial'];

			})
			->setCallback('priceDiscount.check', function (?string &$priceDiscount) use($input): bool {

				$this->setQuick((($input['property'] ?? NULL) === 'priceDiscount'));

				if(empty($priceDiscount)) {

					$priceDiscount = NULL;

				} else {

					$priceDiscount = (float)$priceDiscount;

				}

				return TRUE;

			})
			->setCallback('priceDiscount.noInitial', function (?float $priceDiscount) use($p): bool {

				if($this->isQuick() or $p->isBuilt('price') === FALSE or $priceDiscount === NULL) {
					return TRUE;
				}

				return $this['price'] !== NULL;

			})
			->setCallback('priceDiscount.value', function (?float $priceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					if($priceDiscount === NULL) {
						return TRUE;
					}

					return $this['priceInitial'] > $priceDiscount;
				}

				// Si pas quick
				if($p->isBuilt('price') === FALSE) {
					return TRUE;
				}

				if($priceDiscount === NULL) {
					return TRUE;
				}
				return $this['price'] > $priceDiscount;

			})
			->setCallback('priceDiscount.setValue', function (?float $priceDiscount) use($p): bool {

				// Si Quick
				if($this->isQuick()) {

					// Reset du prix remisé
					if($priceDiscount === NULL and $this['priceInitial'] !== NULL) {

						$this['price'] = $this['priceInitial'];
						$this['priceInitial'] = NULL;
						$p->addBuilt('priceInitial');
						$p->addBuilt('price');
						throw new \PropertySkip();

					}

					// Modif du prix remisé
					if($priceDiscount !== NULL) {

						$this['price'] = $priceDiscount;
						$p->addBuilt('price');
						throw new \PropertySkip();

					}
				}

				if($this->isQuick() === FALSE and $p->isBuilt('price') === FALSE) {
					throw new \PropertySkip();
				}

				// price a déjà été setté
				if($priceDiscount === NULL) {

					$this['priceInitial'] = NULL;
					$p->addBuilt('priceInitial');
					throw new \PropertySkip();

				}

				$this['priceInitial'] = $this['price'];
				$this['price'] = $priceDiscount;
				$p->addBuilt('priceInitial');
				$p->addBuilt('price');

				throw new \PropertySkip();

			});

		parent::build($properties, $input, $p);
	}

}
?>
