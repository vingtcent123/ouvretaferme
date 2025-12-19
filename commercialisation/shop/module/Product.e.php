<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
			'date' => ['farm', 'type', 'catalogs', 'status'],
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

	public function acceptRelation(): bool {

		return ($this['product']['profile'] !== \selling\Product::COMPOSITION);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('status.prepare', function(): bool {

				$this->expects(['catalog']);

				return $this['catalog']->notEmpty();

			})
			->setCallback('parentName.prepare', function(?string &$name): bool {

				if($name === NULL) {
					return FALSE;
				}

				return TRUE;

			})
			->setCallback('children.check', function(?array $children) use ($p): bool {

				$this->expects(['farm', 'date', 'catalog']);

				if($children === NULL) {
					return FALSE;
				}

				array_walk($children, fn(&$value) => $value = (int)$value);

				$cProductChildren = ProductLib::getByIds($children, sort: new \Sql('FIELD(id, '.implode(', ', $children).')'))
					->validateProperty('farm', $this['farm'])
					->validate('acceptRelation');
				
				if($this['date']->notEmpty()) {
					$cProductChildren->validateProperty('date', $this['date']);
				}
				
				if($this['catalog']->notEmpty()) {
					$cProductChildren->validateProperty('catalog', $this['catalog']);
				}

				if(
					Relation::model()
						->whereCatalog($this['catalog'])
						// Produit déjà dans un groupe
						->whereChild('IN', $cProductChildren)
						// En cas d'édition, on ignore le contenu du group actuel
						->whereChild('NOT IN', fn() => $this['cRelation']->getColumn('child'), if: $p->for === 'update')
						->exists()
				) {
					throw new \FailException('Product::children.alreadyUsed');
				}

				$position = 1;

				$eProductParent['category'] = $cProductChildren->first()['product']['category'];

				$cRelation = new \Collection();

				foreach($cProductChildren as $eProductChild) {

					if($eProductChild['product']['category']->is($eProductParent['category']) === FALSE) {
						throw new \FailException('Product::children.categoryConsistency');
					}

					$eRelation = new Relation([
						'farm' => $this['farm'],
						'date' => $this['date'],
						'catalog' => $this['catalog'],
						'child' => $eProductChild,
						'position' => $position++
					]);

					$cRelation[] = $eRelation;

				}

				if($cRelation->empty()) {
					throw new \FailException('Product::children.empty');
				}
				
				$this['cRelation'] = $cRelation;

				return TRUE;

			})
			->setCallback('limitCustomers.prepare', function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', (array)($customers ?? []))
					->whereFarm($this['farm'])
					->whereType($this['type'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('excludeCustomers.prepare', function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', (array)($customers ?? []))
					->whereFarm($this['farm'])
					->whereType($this['type'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('limitGroups.prepare', function(mixed &$groups): bool {

				$this->expects(['farm']);

				$groups = \selling\CustomerGroup::model()
					->select('id')
					->whereId('IN', (array)($groups ?? []))
					->whereFarm($this['farm'])
					->whereType($this['type'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('excludeGroups.prepare', function(mixed &$groups): bool {

				$this->expects(['farm']);

				$groups = \selling\CustomerGroup::model()
					->select('id')
					->whereId('IN', (array)($groups ?? []))
					->whereFarm($this['farm'])
					->whereType($this['type'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('excludeCustomers.consistency', function($customers): bool {

				if(
					($this['limitCustomers'] === [] and $this['limitGroups'] === [] and $this['excludeGroups'] === [] and $customers === []) or
					($this['limitCustomers'] or $this['limitGroups']) xor ($this['excludeGroups'] or $customers)
				) {
					return TRUE;
				} else {
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
