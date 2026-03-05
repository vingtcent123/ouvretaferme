<?php
namespace vat;

Class AlertUi {
	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Vat::createOperations.noFinancialYear' => s("Il n'y a pas d'exercice comptable ouvert où écrire les opérations."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Declaration::saved' => s("Les données de votre déclaration ont bien été sauvegardées."),
			'Declaration::reset' => s("Votre déclaration a bien été réinitialisée aux valeurs calculées par {siteName}."),
			'Declaration::declared' => s("Votre déclaration a bien été enregistrée comme déclarée."),
			'Declaration::operationsCreated' => s("Les écritures ont bien été créées !"),

			default => null

		};


	}


}
