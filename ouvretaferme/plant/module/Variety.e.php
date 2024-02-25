<?php
namespace plant;

class Variety extends VarietyElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'plant' => ['fqn', 'name'],
			'supplierSeed' => ['name'],
			'supplierPlant' => ['name']
		];

	}

	public function canRead(): bool {

		$this->expects(['farm']);

		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->notEmpty() and
			$this['farm']->canManage()
		);

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {

				return (
					(\Privilege::can('plant\admin') and $eFarm->empty()) or
					$eFarm->canManage()
				);

			},

			'plant.check' => function(Plant $ePlant): bool {

				return (
					(\Privilege::can('plant\admin') and $ePlant->empty()) or
					Plant::model()
						->where('farm IS NULL or farm = '.Plant::model()->format($this['farm']))
						->exists($ePlant)
				);

			},

			'supplierSeed.check' => function(\farm\Supplier $eSupplier): bool {

				$this->expects(['farm']);

				return (
					$eSupplier->empty() or
					\farm\Supplier::model()
						->whereFarm($this['farm'])
						->exists($eSupplier)
				);

			},

			'supplierPlant.check' => function(\farm\Supplier $eSupplier): bool {

				$this->expects(['farm']);

				return (
					$eSupplier->empty() or
					\farm\Supplier::model()
						->whereFarm($this['farm'])
						->exists($eSupplier)
				);

			}

		]);

	}

}
?>