<?php
namespace main;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'csvSize' => s("Le fichier ne peut pas excéder 1 Mo, merci de réduire la taille de votre fichier."),
			'csvSource' => s("Le fichier que vous avez envoyé n'est pas reconnu, vérifiez qu'il respecte bien le format demandé."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			default => NULL

		};


	}

}
?>
