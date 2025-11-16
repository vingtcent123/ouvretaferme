<?php
namespace game;

class TileUi {

	public function getPlanting(Tile $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/game/:doPlanting');


			$h .= $form->group(
				content: $form->submit(\s("C'est parti"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-game-plating',
			title: s("Planter une parcelle"),
			body: $h
		);

	}

}
?>
