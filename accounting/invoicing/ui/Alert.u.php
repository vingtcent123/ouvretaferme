<?php
namespace invoicing;

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
			'Sale::imported.market' => s("Le marché a bien été importé."),
			'Invoice::imported' => s("La facture a bien été importée."),

			'Invoice::ignored' => s("La facture a bien été ignorée."),
			'Invoice::ignoredSeveral' => s("Les factures ont bien été ignorées."),
			'Sale::ignored' => s("La vente a bien été ignorée."),
			'Sales::ignoredSeveral' => s("Les ventes ont bien été ignorées."),
			'Sale::ignored.market' => s("Le marché a bien été ignoré."),
			'Market::ignoredSeveral' => s("Les marchés ont bien été ignorés."),

			default => null

		};


	}

}
?>
