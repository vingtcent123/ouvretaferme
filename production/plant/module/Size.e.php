<?php
namespace plant;

class Size extends SizeElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'plant' => ['fqn', 'name'],
			'farm' => ['name']
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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('farm.check', function(\farm\Farm $eFarm): bool {

				return (
					(PlantSetting::getPrivilege('admin') and $eFarm->empty()) or
					$eFarm->canManage()
				);

			})
			->setCallback('plant.check', function(Plant $ePlant): bool {

				$this->expects(['farm']);

				return (
					(PlantSetting::getPrivilege('admin') and $ePlant->empty()) or
					Plant::model()
						->where('farm IS NULL or farm = '.Plant::model()->format($this['farm']))
						->exists($ePlant)
				);

			});
		
		parent::build($properties, $input, $p);

	}

}
?>
