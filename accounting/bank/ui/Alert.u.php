<?php
namespace bank;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Cashflow::allocate.accountsCheck' => s("Veuillez sélectionner au moins une classe de compte."),
			'Cashflow::allocate.noOperation' => s("Aucune opération n'a pu être enregistrée."),
			'Cashflow::internal' => s("Une erreur interne est survenue."),
			'Cashflow::noSelectedOperation' => s("Sélectionnez au moins une écriture à rattacher."),

			'Import::ofxSize' => s("Votre import ne peut pas excéder 1 Mo, merci de réduire la taille de votre fichier."),
			'Import::ofxError' => s("Une erreur est survenue lors de l'import de votre fichier, merci de réessayer."),
			'Import::nothingImported' => s("Aucun mouvement n'a été importé, n'avez-vous pas déjà importé ce fichier ?"),
			'Import::nothingImportedNoFinancialYear' => s("Aucun mouvement n'a été importé, avez-vous bien créé l'exercice comptable de ces opérations bancaires ?"),

			default => null,
		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Cashflow::allocated' => s("Les écritures ont bien été attribuées !"),
			'Cashflow::deallocated' => s("Les écritures ont bien été annulées et la transaction bancaire remise en attente !"),
			'Cashflow::attached' => s("L'opération bancaire a bien été rattachée !"),

			'Import::full' => s("L'import de votre relevé bancaire a bien été effectué !"),
			'Import::partial' => s("L'import de votre relevé bancaire a bien été partiellement effectué, consultez l'import pour plus de détails."),

			default => null,

		};


	}

}

?>
