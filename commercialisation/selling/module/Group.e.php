<?php
namespace selling;

class Group extends GroupElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}


	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		parent::build($properties, $input, $p);

	}

}
?>