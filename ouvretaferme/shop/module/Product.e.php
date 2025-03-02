<?php
namespace shop;

class Product extends ProductElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'product' => \selling\Product::getSelection(),
			'date' => ['farm', 'type', 'source', 'catalogs'],
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
			->setCallback('limitMax.consistency', function(?int $limitMax) use ($p): bool {

				if($p->isBuilt('limitMin') === FALSE) {
					return TRUE;
				}

				return (
					$limitMax === NULL or
					$limitMax >= $this['limitMin']
				);

			})
			->setCallback('limitEndAt.consistency', function($limitEndAt) use ($p): bool {

				$p->expectsBuilt('limitStartAt');

				if(
					$this['limitStartAt'] === NULL or
					$this['limitEndAt'] === NULL
				) {
					return TRUE;
				}

				return $limitEndAt > $this['limitStartAt'];

			});
		
		parent::build($properties, $input, $p);

	}

}
?>