<?php
namespace game;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): mixed {

		return match($fqn) {

			'Action::missingTime' => s("Vous n'avez plus assez de temps disponible !"),

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

			'Action::friendAdded' => s("Vous avez un nouvel ami !"),
			'Action::friendNotAdded' => s("Le code que vous avez saisi correspond à un ami imaginaire !"),

			'Action::planted' => s("C'est semé !"),
			'Action::notPlanted' => s("Semis impossible !"),

			'Action::watered' => s("C'est arrosé !<br/>La culture est plus productive."),
			'Action::notWatered' => s("Arrosage impossible !"),

			'Action::weeded' => s("C'est désherbé !<br/>Vous allez récolter cette parcelle plus tôt."),
			'Action::notWeeded' => s("Désherbage impossible !"),

			'Action::harvested' => s("C'est récolté !<br/>Vous attirez donc quelques rennes supplémentaires."),
			'Action::notHarvested' => s("Récolte impossible !"),

			default => NULL

		};


	}

}
?>
