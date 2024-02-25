<?php
namespace hr;

class WorkingTime extends WorkingTimeElement {

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm', 'user']);

		return (
			$this['farm']->canManage() or
			($this['farm']->canWork() and $this['user']->isOnline())
		);

	}

}
?>