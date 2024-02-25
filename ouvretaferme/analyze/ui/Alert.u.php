<?php
namespace analyze;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Report::cultivations.check' => s("Veuillez sélectionner au moins une série pour construire le rapport !"),
			'Report::products.check' => s("Veuillez sélectionner au moins un produit vendu pour construire le rapport !"),

			'Report::name.duplicate' => s("Vous avez déjà créé un rapport de même nom pour cette saison."),

			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Report::created' => s("Le rapport a bien été créé."),
			'Report::updated' => s("Le rapport a bien été mis à jour."),
			'Report::deleted' => s("Le rapport a bien été supprimé."),

			default => NULL

		};

	}

}
?>
