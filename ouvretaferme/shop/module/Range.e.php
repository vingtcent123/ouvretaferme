<?php
namespace shop;

class Range extends RangeElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'catalog' => ['name'],
		];

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('catalog.check', function(Catalog $eCatalog): bool {

				$this->expects(['cCatalog']);

				return (
					$eCatalog->notEmpty() and
					$this['cCatalog']->offsetExists($eCatalog['id'])
				);

			})
			->setCallback('department.check', function(Department $eDepartment): bool {

				$this->expects(['shop']);

				return (
					$eDepartment->empty() or
					Department::model()
						->whereShop($this['shop'])
						->exists($eDepartment)
				);

			});

		parent::build($properties, $input, $p);

	}

}
?>