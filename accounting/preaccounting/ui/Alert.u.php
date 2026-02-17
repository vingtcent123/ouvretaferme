<?php
namespace preaccounting;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Import::importNotBelongsToFinancialYear' => s("Les dates des paiements ne correspondent pas à l'exercice comptable."),
			'Import::importNoFinancialYear' => s("Il n'y a pas d'exercice comptable dans lequel importer les paiements."),
			'Import::importCannotWriteInFinancialYear' => s("Il n'est plus possible de créer des écritures dans cet exercice comptable."),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Reconciliation::reconciliate' => s("Le rapprochement sélectionné a bien été réalisé."),
			'Reconciliation::reconciliateSeveral' => s("Les rapprochements sélectionnés ont bien été réalisés."),
			'Reconciliation::ignored' => s("Le rapprochement sélectionné a bien été ignoré."),
			'Reconciliation::ignoredSeveral' => s("Les rapprochements sélectionnés ont bien été ignorés."),
			'Reconciliation::cancelled' => s("Le rapprochement sélectionné a bien été annulé."),

			'Suggestion::paymentMethod.updated' => s("Le moyen de paiement a bien été enregistré."),

			'Payment::ignored' => s("Ce paiement a bien été ignoré et ne sera ni proposé à l'import à l'avenir, ni importé."),

			'Preaccounting::imported' => s("L'import a bien été réalisé."),

			default => null

		};


	}

}
?>
