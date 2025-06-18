<?php
namespace main;

class UserObserverUi {

	public static function emailSignUp(\user\User $eUser) {

		$title = s("Bienvenue sur {siteName} !");

		$text = s("Bonjour,

{how}
Vous pouvez désormais créer la page de votre ferme pour commencer à utiliser le service !

{url}

À tout de suite sur {siteName},
L'équipe", ['how' => \user\UserUi::getSignUpType($eUser), 'url' => \Lime::getUrl()]);

		return [
			$title,
			$text
		];

	}

}
?>
