<?php
namespace asset;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Asset::amount.check' => s("Le montant de cession est obligatoire"),
			'Asset::date.check' => s("La date de cession est obligatoire"),
			'Asset::status.check' => s("Le motif de cession est obligatoire"),
			'Asset::amortizableBase.checkValue' => s("La base amortissable ne peut être supérieure à la valeur de l'immobilisation"),
			'Asset::economicDuration.degressive' => s("La durée doit être au moins égale à 3 ans en cas d'amortissement dégressif"),
			'Asset::startDate.missing' => s("La date de mise en service est nécessaire pour les calcul des amortissements linéaires"),
			'Asset::economicMode.incompatible' => s("La classe sélectionnée ne permet pas d'amortissement"),
			'Asset::fiscalMode.incompatible' => s("La classe sélectionnée ne permet pas d'amortissement"),

			default => null,
		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Asset::asset.created' => s("L'immobilisation a bien été enregistrée"),
			'Asset::grant.created' => s("La subvention a bien été enregistrée"),
			'Asset::disposed' => s("L'immobilisation a bien été cédée"),

			default => null,

		};


	}

}

?>
