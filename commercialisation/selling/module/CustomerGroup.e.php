<?php
namespace selling;

class CustomerGroup extends CustomerGroupElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}


	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		$p
			->setCallback('name.comma', function(string $name): bool {
				return str_contains($name, ',') === FALSE;
			});

		parent::build($properties, $input, $p);

	}

}
?>