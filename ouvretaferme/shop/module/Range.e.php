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

			});

		parent::build($properties, $input, $p);

	}

}
?>