<?php
namespace selling;

class Unit extends UnitElement {

	public function canRead(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->empty() or
			$this['farm']->canWrite()
		);

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canManage();

	}

	public function isInteger(): bool {

		return (
			$this->empty() or
			$this['type'] === Unit::INTEGER
		);

	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('singular.duplicate', function(string $name): bool {

				return Unit::model()
					->whereFarm(NULL)
					->whereSingular($name)
					->exists() === FALSE;

			});
		
		parent::build($properties, $input, $p);

	}

}
?>