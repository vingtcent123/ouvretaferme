<?php
namespace company;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Company::disabled' => s("Vous avez désactivé cette fonctionnalité sur votre ferme."),
			'Company::siret.exists' => s("Cette ferme a déjà un compte sur le site, rapprochez-vous de ses dirigeants pour vous ajouter à l'équipe !"),

			'Company::name.check' => s("Merci de renseigner le nom de votre ferme !"),
			'Employee::demo.write' => s("Vous ne pouvez pas modifier l'équipe sur la démo !"),
			'Employee::user.check' => s("Vous n'avez pas sélectionné d'utilisateur."),
			'Employee::email.check' => s("Cette adresse e-mail est invalide."),
			'Employee::email.duplicate' => s("Il y a déjà un utilisateur rattaché à votre ferme avec cette adresse e-mail..."),
			'Employee::deleteItself' => s("Vous ne pouvez pas vous sortir vous-même de la ferme."),

			'Invite::email.duplicate' => s("Une invitation a déjà été lancée pour cette adresse e-mail..."),
			'Invite::email.duplicateCustomer' => s("Cette adresse e-mail est déjà utilisée pour un autre client de votre ferme..."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Company::created' => s("Votre ferme a bien été créée, à vous de jouer !"),
			'Company::updated' => s("Votre ferme a bien été mise à jour !"),
			'Company::closed' => s("Votre ferme a bien été supprimée !"),

			'Employee::userCreated' => s("L'utilisateur a bien été créé et peut désormais être ajouté dans l'équipe de votre ferme !"),
			'Employee::userUpdated' => s("L'utilisateur a bien été mis à jour !"),
			'Employee::userDeleted' => s("L'utilisateur a bien été supprimé !"),
			'Employee::created' => s("L'utilisateur a bien été ajouté à l'équipe de votre ferme !"),
			'Employee::deleted' => s("L'utilisateur a bien été retiré de l'équipe de votre ferme !"),

			'Invite::created' => s("Un email a bien été envoyé pour rejoindre l'équipe de votre ferme !"),
			'Invite::extended' => s("L'invitation a bien été prolongée et un e-mail avec un nouveau lien a été renvoyé !"),
			'Invite::deleted' => s("L'invitation à rejoindre votre ferme a bien été supprimée !"),

			'Subscription::activated' => s("Votre abonnement a bien été activé !"),
			'Subscription::prolongated' => s("Votre abonnement a bien été prolongé !"),
			'Subscription::pack' => s("Votre pack est maintenant actif !"),

			default => null

		};


	}

}
?>
