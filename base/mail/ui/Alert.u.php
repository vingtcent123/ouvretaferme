<?php
namespace mail;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Contact::email.duplicate' => s("Il y a déjà un contact avec cette adresse e-mail"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Contact::created' => s("Le contact a bien été créé !"),

			'Customize::created' => s("Le contenu de l'e-mail a bien été enregistré !"),
			'Customize::deleted' => s("Le contenu de l'e-mail a bien été réinitialisé !"),

			default => NULL

		};

	}

}
?>
