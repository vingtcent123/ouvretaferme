<?php
namespace bank;

Class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'BankAccount::label.numbers' => s("Le numéro d'un compte de banque doit être composé uniquement de chiffres et commencer par {value}", \account\AccountSetting::BANK_ACCOUNT_CLASS),
			'BankAccount::label.duplicate' => s("Ce numéro de compte est déjà utilisé par un autre compte bancaire."),

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

			'BankAccount::deleted' => s("Le compte bancaire et les opérations bancaires associées."),

			default => null,

		};


	}

}

?>
