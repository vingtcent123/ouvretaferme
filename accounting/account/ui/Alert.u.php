<?php
namespace account;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Account::class.duplicate' => s("Cet numéro de compte existe déjà."),
			'Account::class.unknown' => s("Le premier numéro de ce compte n'est pas dans le plan comptable. Le compte doit commencer par un chiffre de 1 à 7."),
			'Account::class.size' => s("Le numéro de compte doit contenir entre 4 et 8 chiffres."),
			'Account::class.numeric' => s("Le numéro de compte doit être composé de chiffres uniquement."),

			'FinancialYear::startDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::endDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::startDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),
			'FinancialYear::endDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),

			'ThirdParty::name.duplicate' => s("Ce tiers existe déjà, utilisez-le directement ?"),
			'ThirdParty::clientAccountLabel.check' => s("Ce compte client a déjà été attribué, choisissez-en un autre."),
			'ThirdParty::supplierAccountLabel.check' => s("Ce compte client a déjà été attribué, choisissez-en un autre."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Account::created' => s("Le numéro de compte personnalisé a bien été créé."),
			'Account::deleted' => s("Le numéro de compte personnalisé a bien été supprimé."),

			'FinancialYear::created' => s("L'exercice comptable a bien été créé."),
			'FinancialYear::updated' => s("L'exercice comptable a bien été mis à jour."),
			'FinancialYear::closed' => s("L'exercice comptable a bien été clôturé."),
			'FinancialYear::open' => s("Le bilan d'ouverture a bien été effectué."),
			'FinancialYear::reopen' => s("L'exercice comptable a bien été rouvert ! Faites bien attention..."),
			'FinancialYear::reclose' => s("L'exercice comptable a bien été refermé."),

			'ThirdParty::created' => s("Le tiers a bien été créé."),
			'ThirdParty::deleted' => s("Le tiers a bien été supprimé."),

			default => null

		};


	}

}
?>
