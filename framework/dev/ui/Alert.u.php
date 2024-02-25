<?php
namespace dev;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'403' => \user\ConnectionLib::isLogged() ? s("Vous n'êtes pas autorisé à afficher cette page.") : s("Veuillez vous connecter pour afficher cette page."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Error.closedByMessage' => s("Les erreurs correspondantes ont bien été validées !"),
			default => NULL

		};


	}

}
?>
