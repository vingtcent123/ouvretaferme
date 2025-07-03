<?php
namespace main;

class UserObserverLib {

	public static function canUpdate(\user\User $eUser, \Collection $cUserAuth, array &$values) {

		if(OTF_DEMO) {
			$values['password'] = FALSE;
			$values['drop'] = FALSE;
			$values['email'] = FALSE;
		}

	}

	public static function sendVerifyEmail(\user\User $eUser, bool $change) {
		\user\EmailLib::sendVerify($eUser, $change);
	}

	public static function signUpCreate(\user\User $eUser) {
		\user\EmailLib::sendSignUp($eUser);
	}

	public static function close(\user\User $eUser) {
		\user\EmailLib::sendClose($eUser);
	}

}
?>
