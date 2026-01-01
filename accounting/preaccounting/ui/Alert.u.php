<?php
namespace preaccounting;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Sale::imported' => s("La vente a bien été importée."),
			'Sale::importedSeveral' => s("Les ventes ont bien été importées."),
			'Sale::imported.market' => s("Le marché a bien été importé."),
			'Sale::imported.marketSeveral' => s("Les marchés ont bien été importés."),
			'Invoice::imported' => s("La facture a bien été importée."),
			'Invoice::importedSeveral' => s("Les factures ont bien été importées."),

			'Invoice::ignored' => s("La facture a bien été ignorée."),
			'Invoice::ignoredSeveral' => s("Les factures ont bien été ignorées."),
			'Sale::ignored' => s("La vente a bien été ignorée."),
			'Sales::ignoredSeveral' => s("Les ventes ont bien été ignorées."),
			'Sale::ignored.market' => s("Le marché a bien été ignoré."),
			'Market::ignoredSeveral' => s("Les marchés ont bien été ignorés."),

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
