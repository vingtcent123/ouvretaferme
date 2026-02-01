<?php
namespace bank;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'BankAccount::description.check' => s("Veuillez donner un nom à votre compte bancaire."),
			'BankAccount::description.duplicate' => s("Ce nom a déjà été donné à un compte bancaire, choisissez-en un autre ?"),

			'Cashflow::allocate.accountsCheck' => s("Veuillez sélectionner au moins un numéro de compte."),
			'Cashflow::allocate.noOperation' => s("Aucune opération n'a pu être enregistrée."),
			'Cashflow::internal' => s("Une erreur interne est survenue."),
			'Cashflow::noSelectedOperation' => s("Sélectionnez au moins une écriture à rattacher."),
			'Cashflow::thirdPartyRequiredForAttach' => s("Indiquez le tiers lié à cette opération."),
			'Cashflow::operationsRequiredForAttach' => s("Sélectionnez au moins une écriture."),

			'Import::ofxSize' => s("Votre import ne peut pas excéder 1 Mo, merci de réduire la taille de votre fichier."),
			'Import::ofxError' => s("Une erreur est survenue lors de l'import de votre fichier. Est-ce bien un fichier OFX ?"),
			'Import::nothingImported' => s("Aucun mouvement n'a été importé, n'avez-vous pas déjà importé ce fichier ?"),
			'Import::nothingImportedNoFinancialYear' => s("Aucun mouvement n'a été importé, avez-vous bien créé l'exercice comptable de ces opérations bancaires ?"),
			'Import::account.check' => s("Choisissez un compte existant ou la création d'un nouveau compte automatiquement"),

			default => null,
		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Cashflow::allocated' => s("Les écritures ont bien été attribuées."),
			'Cashflow::copied' => s("Les écritures comptables ont bien été copiées à l'identique."),
			'Cashflow::deallocated.delete' => s("L'opération bancaire a bien été dissociée et les écritures supprimées."),
			'Cashflow::deallocated.dissociate' => s("L'opération bancaire a bien été dissociée des écritures."),
			'Cashflow::deleted' => s("L'opération bancaire a bien été supprimée."),
			'Cashflow::undeleted' => s("L'opération bancaire a bien été remise."),
			'Cashflow::attached' => s("L'opération bancaire a bien été rattachée !"),

			'Import::full' => s("L'import de votre relevé bancaire a bien été effectué !"),
			'Import::partial' => s("L'import de votre relevé bancaire a bien été partiellement effectué, consultez l'import pour plus de détails."),
			'Import::createdAndAccountSelected' => s("L'import de votre relevé bancaire a bien été réalisé, et le compte bancaire paramétré."),

			'BankAccount::deleted' => s("Le compte bancaire et les opérations bancaires associées."),
			'BankAccount::created' => s("Le compte bancaire a bien été créé."),

			default => null,

		};


	}

}

?>
