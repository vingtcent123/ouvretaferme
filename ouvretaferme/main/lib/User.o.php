<?php
namespace main;

class UserObserverLib {

	public static function sendVerifyEmail(\user\User $eUser, bool $change) {
		\user\MailLib::sendVerify($eUser, $change);
	}

	public static function signUpCreate(\user\User $eUser) {
		\user\MailLib::sendSignUp($eUser);
	}

	public static function close(\user\User $eUser) {
		\user\MailLib::sendClose($eUser);
	}

}
?>
