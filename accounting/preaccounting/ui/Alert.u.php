<?php
namespace preaccounting;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Payment::imported' => s("La facture a bien été importée."),
			'Payment::importedSeveral' => s("Les factures ont bien été importées."),
			'Payment::ignored' => s("Le paiement a bien été ignoré."),
			'Payment::ignoredSeveral' => s("Les paiements ont bien été ignorés."),

			'Reconciliation::reconciliate' => s("Le rapprochement sélectionné a bien été réalisé."),
			'Reconciliation::reconciliateSeveral' => s("Les rapprochements sélectionnés ont bien été réalisés."),
			'Reconciliation::ignored' => s("Le rapprochement sélectionné a bien été ignoré."),
			'Reconciliation::ignoredSeveral' => s("Les rapprochements sélectionnés ont bien été ignorés."),
			'Reconciliation::cancelled' => s("Le rapprochement sélectionné a bien été annulé."),

			'Suggestion::paymentMethod.updated' => s("Le moyen de paiement a bien été enregistré."),

			default => null

		};


	}

}
?>
