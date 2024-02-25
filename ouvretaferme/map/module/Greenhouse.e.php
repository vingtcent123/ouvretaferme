<?php
namespace map;

class Greenhouse extends GreenhouseElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'seasonFirst', 'seasonLast'],
			'plot' => ['name', 'zoneFill'],
			'zone' => ['name'],
		];

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canWrite();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'plot.check' => function(Plot $ePlot): bool {

				if($this->offsetExists('farm') === FALSE) {
					return FALSE;
				}

				return Plot::model()
					->select(['zone', 'zoneFill'])
					->whereFarm($this['farm'])
					->get($ePlot);

			}

		]);

	}

}
?>