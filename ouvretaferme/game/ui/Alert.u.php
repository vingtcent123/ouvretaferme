<?php
namespace game;

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

			'Action::eaten' => s("Bon appétit !<br/>Vous pouvez maintenant travailler {value} heures de plus !", \game\GameSetting::BONUS_SOUP),
			'Action::notEaten' => s("Il ne s'est rien passé, vous aviez déjà mangé votre dernière soupe !"),
			'Action::cooked' => s("Cuisson terminée !"),
			'Action::notCooked' => s("Préparation impossible, il vous manquait des légumes !"),
			default => NULL

		};


	}

}
?>
