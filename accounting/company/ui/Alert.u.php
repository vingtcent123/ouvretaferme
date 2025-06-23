<?php
namespace company;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'FinancialYear::dates.inconsistency' => s("La date de début de votre exercice comptable doit être antérieure à la date de fin."),

			'Company::disabled' => s("Vous avez désactivé cette fonctionnalité sur votre ferme."),
			'Company::siret.exists' => s("Cette ferme a déjà un compte sur le site, rapprochez-vous de ses dirigeants pour vous ajouter à l'équipe !"),

			'Company::name.check' => s("Merci de renseigner le nom de votre ferme !"),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Company::created' => s("Votre ferme a bien été configurée, à vous de jouer !"),
			'Company::updated' => s("Vos paramètres ont bien été mis à jour !"),
			'Company::closed' => s("Votre ferme a bien été supprimée !"),

			'Subscription::activated' => s("Votre abonnement a bien été activé !"),
			'Subscription::prolongated' => s("Votre abonnement a bien été prolongé !"),
			'Subscription::pack' => s("Votre pack est maintenant actif !"),

			default => null

		};


	}

}
?>
