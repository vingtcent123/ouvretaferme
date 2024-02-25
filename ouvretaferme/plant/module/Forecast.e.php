<?php
namespace plant;

class Forecast extends ForecastElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'plant' => ['fqn', 'name', 'vignette']
		];

	}

	public function canRead(): bool {

		return $this->canAnalyze();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canAnalyze();

	}


	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'farm.check' => function(\farm\Farm $eFarm): bool {

				return (
					(\Privilege::can('plant\admin') and $eFarm->empty()) or
					$eFarm->canManage()
				);

			},

			'plant.check' => function(\plant\Plant $ePlant): bool {

				return (
					$ePlant->empty() === FALSE and
					\plant\Plant::model()
						->select('farm')
						->get($ePlant) and
					$ePlant->canRead()
				);

			},

			'proPart.consistency' => function(int $part) use ($properties): bool {

				if(
					in_array('privatePart', $properties) and
					in_array('proPart', $properties)
				) {
					return ($this['privatePart'] + $part === 100);
				} else {
					return TRUE;
				}

			},

		]);

	}

}
?>