<?php
namespace payment;

class Method extends MethodElement {

	public function canRead(): bool {

		$this->expects(['farm']);

		return (
			$this['farm']->empty() === FALSE and
			$this['farm']->canWrite()
		);

	}
	public function canUse(): bool {

		return (
			($this['farm']->empty() or $this['farm']->canWrite())
			and $this['status'] === Method::ACTIVE
		);

	}

}
?>
