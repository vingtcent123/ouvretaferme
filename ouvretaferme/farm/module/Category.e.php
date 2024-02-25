<?php
namespace farm;

class Category extends CategoryElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {
		$this->expects(['farm']);
		return $this['farm']->canManage();
	}
}
?>