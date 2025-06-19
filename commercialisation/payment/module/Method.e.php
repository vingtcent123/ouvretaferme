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
	public function canDelete(): bool {

		return ($this['farm']->empty() or $this['farm']->canWrite());

	}

	public function acceptManualUpdate(): bool {

		if($this->empty()) {
			return TRUE;
		}

		$this->expects(['status', 'fqn']);

		return (
			$this['status'] === Method::ACTIVE and
			$this['fqn'] !== \payment\MethodLib::ONLINE_CARD
		);

	}

}
?>
