<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
			'date' => ['farm', 'type', 'source', 'catalogs', 'status'],
			'catalog' => ['farm']
		];

	}

	public function getTaxes(): string {
		return \selling\CustomerUi::getTaxes($this['type']);
	}

	public function canWrite(): bool {

		if($this['date']->notEmpty()) {
			return $this['date']->canWrite();
		} else if($this['catalog']->notEmpty()) {
			return $this['catalog']->canWrite();
		} else {
			return FALSE;
		}

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('status.prepare', function(): bool {

				$this->expects(['catalog']);

				return $this['catalog']->notEmpty();

			})
			->setCallback('limitCustomers.prepare', function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = (array)($customers ?? []);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', $customers)
					->whereFarm($this['farm'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('excludeCustomers.prepare', function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = (array)($customers ?? []);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', $customers)
					->whereFarm($this['farm'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('excludeCustomers.consistency', function($customers): bool {

				if(
					($this['limitCustomers'] === [] and $customers === []) or
					$this['limitCustomers'] xor $customers
				) {
					return TRUE;
				} else {
					\Fail::log('limitCustomers.consistency');
					return FALSE;
				}

			})
			->setCallback('limitMax.consistency', function(?float $limitMax) use($p): bool {

				if($p->isBuilt('limitMin') === FALSE) {
					return TRUE;
				}

				return (
					$limitMax === NULL or
					$limitMax >= $this['limitMin']
				);

			})
			->setCallback('limitEndAt.consistency', function($limitEndAt) use($p): bool {

				$p->expectsBuilt('limitStartAt');

				if(
					$this['limitStartAt'] === NULL or
					$this['limitEndAt'] === NULL
				) {
					return TRUE;
				}

				return $limitEndAt > $this['limitStartAt'];

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
