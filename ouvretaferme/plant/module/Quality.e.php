<?php
namespace plant;

class Quality extends QualityElement {

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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {

				return (
					(\Privilege::can('plant\admin') and $eFarm->empty()) or
					$eFarm->canManage()
				);

			},

			'plant.check' => function(Plant $ePlant): bool {

				$this->expects(['farm']);

				return (
					(\Privilege::can('plant\admin') and $ePlant->empty()) or
					Plant::model()
						->where('farm IS NULL or farm = '.Plant::model()->format($this['farm']))
						->exists($ePlant)
				);

			}

		]);

	}

}
?>