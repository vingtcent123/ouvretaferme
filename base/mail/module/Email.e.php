<?php
namespace mail;

class Email extends EmailElement {

	public function isBlocked(): bool {
		return in_array($this['status'], [Email::ERROR_BLOCKED, Email::ERROR_BOUNCE, Email::ERROR_SPAM, Email::ERROR_PROVIDER, Email::ERROR_INVALID]);
	}

}
?>