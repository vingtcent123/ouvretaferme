<?php
namespace map;

class Plot extends PlotElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'seasonFirst', 'seasonLast', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth'],
			'zone' => ['name', 'coordinates', 'seasonFirst', 'seasonLast'],
		];

	}

	public function getArea(): string {

		if($this['area'] > 1000) {
			return s("{value} ha", sprintf('%.02f', $this['area'] / 10000));
		} else {
			return s("{value} m²", $this['area']);
		}

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canBedLine(): bool {

		$this->expects([
			'zoneFill',
			'zone' => ['coordinates'],
			'coordinates'
		]);

		return (
			($this['zoneFill'] === TRUE and $this['zone']['coordinates'] !== NULL) or
			($this['zoneFill'] === FALSE and $this['coordinates'] !== NULL)
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.check', function(?string $name): bool {

				return ($name !== NULL and Plot::model()->check('name', $name));

			})
			->setCallback('greenhouse.check', function(): bool {

				$this->expects(['mode']);

				if($this['mode'] === Plot::OPEN_FIELD) {
					return TRUE;
				}

				$fw = new \FailWatch();

				$eGreenhouse = new Greenhouse();
				$eGreenhouse->build(['length', 'width'], $_POST, new \Properties('create'));

				$this['greenhouse'] = $eGreenhouse;

				return $fw->ok();

			})
			->setCallback('seasonFirst.check', function(?int $season): bool {

				$this->expects([
					'zone' => ['seasonFirst']
				]);

				return ($season === NULL or $this['zone']['seasonFirst'] === NULL or $season >= $this['zone']['seasonFirst']);

			})
			->setCallback('seasonLast.check', function(?int $season): bool {

				$this->expects([
					'zone' => ['seasonLast']
				]);

				return ($season === NULL or $this['zone']['seasonLast'] === NULL or $season <= $this['zone']['seasonLast']);

			})
			->setCallback('seasonLast.consistency', function(?int $season): bool {
				return (
					$this['seasonFirst'] === NULL or
					$season === NULL or
					$this['seasonFirst'] <= $season
				);
			});

		parent::build($properties, $input, $p);

	}

}
?>