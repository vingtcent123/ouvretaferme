<?php
namespace farm;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Player::name.duplicate' => \s("Un autre joueur utilise déjà ce nom, trouvez un nom plus original !"),

			default => null

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			default => null

		};


	}

}
?>
