<?php
namespace overview;

Class AlertUi {
	public static function getError(string $fqn): mixed {

		return match($fqn) {

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'VatDeclaration::saved' => s("Les données de votre déclaration ont bien été sauvegardées."),
			'VatDeclaration::reset' => s("Votre déclaration a bien été réinitialisée aux valeurs calculées par {siteName}."),
			'VatDeclaration::declared' => s("Votre déclaration a bien été enregistrée comme déclarée."),
			'VatDeclaration::operationsCreated' => s("Les écritures ont bien été créées !"),

			default => null

		};


	}


}
