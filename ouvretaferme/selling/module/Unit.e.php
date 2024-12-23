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

}
?>