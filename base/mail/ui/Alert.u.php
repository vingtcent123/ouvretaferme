<?php
namespace mail;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {


			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Customize::created' => s("Le contenu de l'e-mail a bien été enregistré !"),

			default => NULL

		};

	}

}
?>
