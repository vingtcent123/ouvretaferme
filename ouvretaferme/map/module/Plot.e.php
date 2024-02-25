<?php
namespace map;

class Plot extends PlotElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'seasonFirst', 'seasonLast', 'defaultBedLength', 'defaultBedWidth', 'defaultAlleyWidth'],
			'zone' => ['name', 'coordinates', 'seasonFirst', 'seasonLast'],
		];

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

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'name.check' => function(?string $name): bool {

				return ($name !== NULL and Plot::model()->check('name', $name));

			},

			'greenhouse.check' => function(): bool {

				$this->expects(['mode']);

				if($this['mode'] === Plot::OUTDOOR) {
					return TRUE;
				}

				$fw = new \FailWatch();

				$eGreenhouse = new Greenhouse();
				$eGreenhouse->build(['length', 'width'], $_POST, for: 'create');

				$this['greenhouse'] = $eGreenhouse;

				return $fw->ok();

			},

			'seasonFirst.check' => function(?int $season): bool {

				$this->expects([
					'zone' => ['seasonFirst']
				]);

				return ($season === NULL or $this['zone']['seasonFirst'] === NULL or $season >= $this['zone']['seasonFirst']);

			},
			'seasonLast.check' => function(?int $season): bool {

				$this->expects([
					'zone' => ['seasonLast']
				]);

				return ($season === NULL or $this['zone']['seasonLast'] === NULL or $season <= $this['zone']['seasonLast']);

			},
			'seasonLast.consistency' => function(?int $season): bool {
				return (
					$this['seasonFirst'] === NULL or
					$season === NULL or
					$this['seasonFirst'] <= $season
				);
			}

		]);

	}

}
?>