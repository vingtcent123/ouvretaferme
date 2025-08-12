<?php
namespace mail;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Campaign::scheduledAt.past' => s("Votre campagne ne peut pas être programmée aussi tôt, veuillez décaler l'envoi."),
			'Campaign::to.empty' => s("Merci de renseigner au moins un contact"),
			'Campaign::to.check' => s("Une ou plusieurs adresses e-mail ne sont pas présentes dans votre base de contacts"),
			'Campaign::createError' => s("Il y a des erreurs à corriger avant de programmer cette campagne."),

			'Contact::email.duplicate' => s("Il y a déjà un contact avec cette adresse e-mail"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Campaign::created' => s("La campagne de communication par e-mail a bien été programmée !"),

			'Contact::created' => s("Le contact a bien été créé !"),
			'Contact::createdNewsletter' => s("Votre inscription à la lettre d'information a bien été prise en compte !"),

			'Customize::created' => s("Le contenu de l'e-mail a bien été enregistré !"),
			'Customize::deleted' => s("Le contenu de l'e-mail a bien été réinitialisé !"),

			default => NULL

		};

	}

}
?>
