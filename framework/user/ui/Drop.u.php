<?php
namespace user;

/**
 * Handle user signup and profile edition
 */
class DropUi {

	/**
	 * Display button to close its account
	 */
	public function close(User $eUser, bool $canCloseDelay): string {

		$eUser->expects(['deletedAt']);

		$h = '';

		if($eUser['deletedAt']) {

			$h .= '<div class="util-danger">'.$this->getCloseMessage($eUser['deletedAt']).'</div>';

			$h .= '<a data-ajax="/user/close:do" class="btn btn-primary">'.s("Annuler la fermeture de mon compte").'</a>';

		} else {

			$h .= '<div class="util-info">'.s("Lorsque vous fermez votre compte, vous disposez d'un délai de réflexion de {value} jours pour revenir sur votre décision. Passé ce délai, votre compte est désactivé, vos données seront supprimées et vous ne pourrez plus vous connecter.", \Setting::get('user\closeTimeout')).'</div>';

			if($canCloseDelay) {
				$h .= '<a data-ajax="/user/close:do" class="btn btn-danger">'.s("Fermer mon compte").'</a>';
			} else {
				$h .= '<b>'.s("Par sécurité, vous ne pouvez fermer votre compte que {value} minutes après vous être connecté. Vous devez donc vous déconnecter et vous reconnecter sur cette page pour effectuer cette action.", \Setting::get('user\closeTimeLimit')).'</b>';
			}

		}

		return $h;

	}

	/**
	 * Returns a message when a user has closed is account
	 */
	public function getCloseMessage($deletedAt): string {

		return s("Vous avez choisi de fermer votre compte. Il sera définitivement clôturé le {date} et vous ne pourrez plus vous y connecter. Vous pouvez continuer à l'utiliser librement avant cette date.", ['date' => \util\DateUi::numeric($deletedAt, \util\DateUi::DATE)]);

	}

}
?>
