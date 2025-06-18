<?php
namespace accounting;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Account::class.duplicate' => s("Cette classe de compte existe déjà."),
			'Account::class.unknown' => s("Le premier numéro de cette classe n'est pas dans le plan comptable. La classe doit commencer par un chiffre de 1 à 7."),
			'Account::class.size' => s("La classe doit contenir entre 4 et 8 chiffres."),
			'Account::class.numeric' => s("La classe doit être composée de chiffres uniquement."),

			'FinancialYear::startDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::endDate.check' => s("Cette date est incluse dans un autre exercice."),
			'FinancialYear::startDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),
			'FinancialYear::endDate.loseOperations' => s("En modifiant cette date, certaines écritures ne seront plus rattachées à un exercice existant."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Account::created' => s("La classe de compte personnalisée a bien été créée."),
			'Account::deleted' => s("La classe de compte personnalisée a bien été supprimée."),

			'FinancialYear::created' => s("L'exercice comptable a bien été créé."),
			'FinancialYear::updated' => s("L'exercice comptable a bien été mis à jour."),
			'FinancialYear::closedAndCreated' => s("L'exercice comptable a bien été clôturé et le suivant créé."),
			'FinancialYear::closed' => s("L'exercice comptable a bien été clôturé."),

			default => null

		};


	}

}
?>
