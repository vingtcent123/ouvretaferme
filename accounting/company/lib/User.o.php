<?php
namespace company;

class UserObserverLib {

	public static function logIn(\user\User $eUser) {

		InviteLib::acceptByUser($eUser);

	}

	public static function formLog(\stdClass $data) {


	}

	public static function formSignUp(\stdClass $data) {

		$data->chooseRole = TRUE;

		if(get_exists('invite')) {

			$data->eInvite = \company\InviteLib::getByKey(GET('invite'));

			if($data->eInvite->isValid()) {
				$data->eRole = $data->cRole['employee'];
				$data->chooseRole = FALSE;
				$data->eUserOnline['email'] = $data->eInvite['email'];
			} else {
				throw new \RedirectAction('/presentation/invitation');
			}

		} else {
			$data->eInvite = new Invite();
		}
	}

}
?>
