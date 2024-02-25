<?php
namespace farm;

class UserObserverLib {

	public static function logIn(\user\User $eUser) {

		InviteLib::acceptByUser($eUser);

	}

	public static function formLog(\stdClass $data) {

		if(get_exists('invite')) {
			$eInvite = \farm\InviteLib::getByKey(GET('invite'));
			if($eInvite->isValid()) {
				$data->email = $eInvite['email'];
			}
		}

	}

	public static function formSignUp(\stdClass $data) {

		$data->chooseRole = TRUE;

		if(get_exists('invite')) {

			$data->eInvite = \farm\InviteLib::getByKey(GET('invite'));

			if($data->eInvite->isValid()) {
				$data->eRole = $data->cRole[$data->eInvite['type']];
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
