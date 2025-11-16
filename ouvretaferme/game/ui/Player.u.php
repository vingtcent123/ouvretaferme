<?php
namespace game;

class PlayerUi {

	public static function getTime(float $time): string {
		return \series\TaskUi::convertTime($time, showMinutes: NULL);
	}

	public function create(Player $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/game/:doNew');

			$h .= $form->dynamicGroup($e, 'name');

			$h .= $form->group(
				content: $form->submit(s("C'est parti"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-game-create',
			title: \s("Commencer à jouer"),
			body: $h
		);

	}


	public static function p(string $property): \PropertyDescriber {

		$d = Player::model()->describer($property, [
			'name' => s("Choisissez un nom de joueur"),
		]);

		switch($property) {

			case 'name' :
				$d->labelAfter = \util\FormUi::info(s("Ce n'est pas forcément votre nom réel !"));
				$d->placeholder = s("Ex. : Toto");
				break;

		}

		return $d;

	}

}
?>
