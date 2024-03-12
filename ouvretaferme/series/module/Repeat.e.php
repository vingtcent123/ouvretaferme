<?php
namespace series;

class Repeat extends RepeatElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canWrite();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canTask();

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'stop.future' => function(?string $stop): bool {

				return (
					$stop === NULL or
					strcmp($stop, currentDate()) > 0
				);

			},

			// On ne peut qu'avancer une date de fin de répétition
			'stop.consistency' => function(?string $stop) use ($for): bool {

				if($for !== 'update') {
					return TRUE;
				}

				$this->expects(['stop']);

				return (
					$this['stop'] === NULL or
					$stop < $this['stop']
				);

			},

			'stop.series' => function(?string $stop) use ($for): bool {

				if($for !== 'create') {
					return TRUE;
				}

				$this->expects([
					'task' => ['series']
				]);

				if($this['task']['series']->empty()) {
					return TRUE;
				}

				return (
					$stop !== NULL and
					$this['task']['series']->notEmpty() and
					(
						(int)substr($stop, 0, 4) >= $this['task']['series']['season'] - 1 and
						(int)substr($stop, 0, 4) <= $this['task']['series']['season'] + 1
					)
				);

			},

		]);

	}

}
?>