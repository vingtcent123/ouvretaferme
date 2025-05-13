<?php
namespace payment;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Stripe::demo.write' => s("Vous ne pouvez pas configurer le paiement sur le site de démo."),

			'Cb::error' => s("Une erreur est intervenue pendant la procédure, la commande n'a pas abouti."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Method::created' => s("Le moyen de paiement a bien été créé."),
			'Method::updatedActive' => s("Le moyen de paiement a bien été activé."),
			'Method::updatedInactive' => s("Le moyen de paiement a bien été désactivé."),
			'Method::updated' => s("Le moyen de paiement a bien été mis à jour."),
			'StripeFarm::created' => s("Votre compte Stripe a bien été paramétré."),
			'StripeFarm::deleted' => s("Les données de votre compte Stripe ont bien été supprimées."),

			default => null

		};


	}

}
?>
