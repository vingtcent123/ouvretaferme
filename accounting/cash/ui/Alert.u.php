<?php
namespace cash;

class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Cash::date.future' => s("La date de l'opération ne peut pas être dans le futur"),

			default => null

		};

	}

	public static function getSuccess(string $fqn, array $options = []): ?string {

		return match($fqn) {

			'Register::created' => s("Le journal de caisse a bien été configuré"),
			'Register::deleted' => s("Le journal de caisse a bien été supprimé"),

			default => null

		};


	}

}
?>
