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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'status.prepare' => function(): bool {

				$this->expects(['catalog']);

				return $this['catalog']->notEmpty();

			},

			'limitCustomers.prepare' => function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = (array)($customers ?? []);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', $customers)
					->whereFarm($this['farm'])
					->getColumn('id');

				return TRUE;

			},

			'limitMax.consistency' => function(?int $limitMax, \BuildProperties $p): bool {

				if($p->isBuilt('limitMin') === FALSE) {
					return TRUE;
				}

				return (
					$limitMax === NULL or
					$limitMax >= $this['limitMin']
				);

			},

			'limitEndAt.consistency' => function($limitEndAt, \BuildProperties $p): bool {

				$p->expectsBuilt('limitStartAt');

				if(
					$this['limitStartAt'] === NULL or
					$this['limitEndAt'] === NULL
				) {
					return TRUE;
				}

				return $limitEndAt > $this['limitStartAt'];

			},

		]);

	}

}
?>