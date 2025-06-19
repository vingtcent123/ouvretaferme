<?php
namespace mail;

class Customize extends CustomizeElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

}
?>