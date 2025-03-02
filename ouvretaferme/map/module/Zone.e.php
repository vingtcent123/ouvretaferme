<?php
namespace map;

class Zone extends ZoneElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'seasonFirst', 'seasonLast', 'placeLngLat', 'rotationYears'],
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

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('use.empty', function(?string $use): bool {

				return ($use !== NULL);

			})
			->setCallback('seasonFirst.check', function(?int $season): bool {
				$this->expects([
					'farm' => ['seasonFirst']
				]);
				return ($season === NULL or $season >= $this['farm']['seasonFirst']);
			})
			->setCallback('seasonLast.check', function(?int $season): bool {
				$this->expects([
					'farm' => ['seasonLast']
				]);
				return ($season === NULL or $season <= $this['farm']['seasonLast']);
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