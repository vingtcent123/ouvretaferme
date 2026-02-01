<?php
namespace cash;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			default => null

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Register::created' => s("Le cahier de caisse a bien été configuré"),
			'Register::deleted' => s("Le cahier de caisse a bien été supprimé"),

			default => null

		};


	}

}
?>
