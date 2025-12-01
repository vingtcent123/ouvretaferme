<?php
namespace shop;

class Range extends RangeElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'catalog' => ['name', 'products'],
			'shop' => ['farm', 'shared', 'opening'],
		];

	}

	public function canCreate(): bool {

		$this->expects(['shop', 'farm']);

		return $this['shop']->canShareRead($this['farm']);

	}

	public function canWrite(): bool {

		$this->expects(['shop', 'farm']);

		return (
			(
				$this['farm']->canWrite() and
				$this['shop']->canShareRead($this['farm'])
			) or
			$this['farm']->canManage()
		);

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

			})
			->setCallback('datesList.check', function(mixed $list) {

				$this->expects(['cDateAvailable']);

				$cDateAvaible = $this['cDateAvailable'];
				$cDate = new \Collection();

				foreach((array)$list as $date) {

					if($cDateAvaible->offsetExists($date) === FALSE) {
						return FALSE;
					}

					$cDate[] = $cDateAvaible[$date];

				}

				$this['cDate'] = $cDate;

				return TRUE;

			});

		parent::build($properties, $input, $p);

	}

}
?>