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

			'stop.season' => function(?string $stop): bool {

				$this->expects([
					'task' => ['season']
				]);

				if($this['task']['season'] === NULL) {
					return TRUE;
				}

				return (
					$stop !== NULL and
					(
						(int)substr($stop, 0, 4) >= $this['task']['season'] - 1 and
						(int)substr($stop, 0, 4) <= $this['task']['season'] + 1
					)
				);

			},

		]);

	}

}
?>