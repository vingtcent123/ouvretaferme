<?php
namespace session;

class UserObserverLib {

	public static function logIn(\user\User $eUser) {

		$sid = session_id();

		Session::model()
			->whereSid($sid)
			->update([
				'user' => $eUser
			]);

	}

	public static function logOut(\user\User $eUser) {

		$sid = session_id();

		Session::model()
			->whereSid($sid)
			->update([
				'user' => NULL
			]);

	}

}
?>
