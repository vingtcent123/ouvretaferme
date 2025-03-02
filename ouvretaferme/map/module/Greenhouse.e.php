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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('plot.check', function(Plot $ePlot): bool {

				if($this->offsetExists('farm') === FALSE) {
					return FALSE;
				}

				return Plot::model()
					->select(['zone', 'zoneFill'])
					->whereFarm($this['farm'])
					->get($ePlot);

			});
			
		parent::build($properties, $input, $p);

	}

}
?>