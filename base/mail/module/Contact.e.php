<?php
namespace mail;

class Contact extends ContactElement {

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canCommunication();

	}

	public function getActive(): bool {
		if($this->empty()) {
			return TRUE;
		} else {
			return $this['active'];
		}
	}

	public function getOptIn(): ?bool {
		if($this->empty()) {
			return NULL;
		} else {
			return $this['optIn'];
		}
	}

	public function opt(): bool {

		return (
			$this->getActive() and
			$this->getOptIn()
		);

	}

	public function isEmailValid(): bool {

		return (
			$this->notEmpty() and
			$this['delivered'] > 0 and
			(
				$this['failed'] === 0 or
				$this['lastDelivered'] > $this['lastFailed']
			)
		);

	}

	public function isEmailBlocked(): bool {

		return (
			$this->notEmpty() and
			$this['failed'] > 0 and
			(
				$this['delivered'] === 0 or
				$this['lastFailed'] > $this['lastDelivered']
			)
		);

	}

}
?>