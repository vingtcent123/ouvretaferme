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

			'StripeFarm::created' => s("Votre compte Stripe a bien été paramétré."),
			'StripeFarm::deleted' => s("Les données de votre compte Stripe ont bien été supprimées."),

			default => null

		};


	}

}
?>
