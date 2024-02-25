<?php
namespace mail;

/**
 * Alert messages
 */
class AlertUi {

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'confirmationSent' => s("Un email de confirmation d'adresse vient de vous être envoyé."),
			'confirmationSentOther' => s("Un email de confirmation d'adresse vient de lui être envoyé."),
			default => NULL

		};

	}

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'captchaInvalid' => s("Veuillez confirmer que vous n'êtes pas un robot."),
			default => NULL

		};

	}

}
?>
