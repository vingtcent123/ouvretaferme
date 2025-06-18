<?php
namespace asset;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Asset::amount.check' => s("Le montant de cession est obligatoire"),
			'Asset::date.check' => s("La date de cession est obligatoire"),
			'Asset::status.check' => s("Le motif de cession est obligatoire"),

			default => null,
		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Asset::disposed' => s("L'immobilisation a bien été cédée"),

			default => null,

		};


	}

}

?>
