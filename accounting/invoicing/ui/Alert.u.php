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

			'Sale::ignored' => s("La vente a bien été ignorée."),
			'Sale::ignored.market' => s("Le marché a bien été ignoré."),

			default => null

		};


	}

}
?>
