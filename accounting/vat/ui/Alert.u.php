<?php
namespace vat;

Class AlertUi {
	public static function getError(string $fqn): mixed {

		return match($fqn) {

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
