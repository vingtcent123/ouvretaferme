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

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Company::created' => s("Votre ferme a bien été configurée, à vous de jouer !"),

			'Beta::registered' => s("Votre candidature a bien été enregistrée, nous prendrons contact avec vous d'ici quelques jours ! Nous vous remercions pour votre soutien."),

			default => null

		};


	}

}
?>
