<?php
namespace mail;

class AlertUi {

	public static function getError(string $fqn, array $options): mixed {

		return match($fqn) {

			'Campaign::scheduledAt.check' => s("Vous n'avez pas indiqué de date de programmation."),
			'Campaign::scheduledAt.soon' => s("Votre campagne ne peut pas être programmée aussi tôt, veuillez décaler l'envoi."),
			'Campaign::scheduledAt.past' => s("Votre campagne ne peut pas être programmée dans le passé, veuillez décaler l'envoi."),
			'Campaign::to.empty' => s("Merci de renseigner au moins un contact"),
			'Campaign::to.check' => s("Une ou plusieurs adresses e-mail ne sont pas présentes dans votre base de contacts ou ne permettent pas d'envoyer des e-mails promotionnels"),
			'Campaign::to.limitExceeded' => fn(Campaign $e) => s("Vous êtes limités à {toLimit} mails hebdomadaires et cette campagne vous conduira à en envoyer {toAttempt} sur la semaine.", $e),
			'Campaign::createError' => s("Il y a des erreurs à corriger avant de programmer cette campagne."),

			'Contact::email.duplicate' => s("Il y a déjà un contact avec cette adresse e-mail"),
			'Contact::email.check' => fn() => s("L'adresse e-mail {value} est invalide", '<b>'.encode($options[1]).'</b>'),
			'Contact::email.empty' => s("Vous n'avez indiqué aucun contact"),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Campaign::created' => s("La campagne de communication par e-mail a bien été programmée !"),
			'Campaign::updated' => s("La campagne de communication par e-mail a bien été mise à jour !"),
			'Campaign::deleted' => s("La campagne de communication par e-mail a bien été supprimée et ne sera donc pas envoyée !"),
			'Campaign::test' => s("L'e-mail de test a bien été envoyé à l'adresse e-mail de la ferme !"),

			'Contact::created' => s("Le contact a bien été ajouté !"),
			'Contact::createdCollection' => s("Les contacts ont bien été ajoutés !"),
			'Contact::createdNewsletter' => s("Votre inscription à la lettre d'information a bien été prise en compte !"),

			'Customize::created' => s("Le contenu de l'e-mail a bien été enregistré !"),
			'Customize::deleted' => s("Le contenu de l'e-mail a bien été réinitialisé !"),

			default => NULL

		};

	}

}
?>
