<?php
namespace mail;

class Contact extends ContactElement {

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