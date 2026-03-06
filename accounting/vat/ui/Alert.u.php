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
			'Declaration::status.declared' => s("La déclaration a bien été enregistrée déclarée !"),
			'Declaration::status.accounted' => s("La déclaration a bien été enregistrée comptabilisée !"),
			'Declaration::status.paid' => s("Le paiement de la déclaration a bien été enregistré. La déclaration est maintenant close."),

			default => null

		};


	}


}
