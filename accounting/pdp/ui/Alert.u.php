<?php
namespace pdp;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Connection::lost' => s("La connexion avec Super PDP a expiré."),

			'Address::identifier.check' => s("Veuillez respecter le format de l'adresse."),

			default => null

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Address::created' => s("L'adresse a bien été créée"),
			'Address::deleted' => s("L'adresse a bien été supprimée"),

			'Pdp::connected' => s("Votre ferme est bien connectée à la plateforme agréée !"),

			default => null

		};


	}

}
?>
