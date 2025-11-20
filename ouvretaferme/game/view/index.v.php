<?php
new AdaptativeView('start', function($data, GameTemplate $t) {

	$t->title = s("Sauvez Noël !");

	echo \game\HelpUi::getStory();

	echo new \game\DeskUi()->get('<a href="/game/:new" class="game-intro-start">'.s("Commencer à jouer").'</a>', 1);

});

new AdaptativeView('/jouer', function($data, GameTemplate $t) {

	$t->title = s("Jouer !");

	echo '<h1>';
		echo '<span class="hide-sm-down">🎅 </span>';
		echo s("Des légumes pour les rennes");
		echo '<span class="hide-sm-down"> 🦌</span>';
	echo '</h1>';

	if(get_exists('start')) {

		echo '<div class="text-center mb-3 mt-3 font-xl" style="font-style: italic">';
			echo s("Votre partie est bien créée, {value} !", encode($data->ePlayer['name'])).'<br/>';
			echo s("Il ne vous reste qu'à lire les règles du jeu avant de commencer le travail.");
		echo '</div>';

		echo \game\HelpUi::getRules(TRUE);

		echo new \game\DeskUi()->play($data->ePlayer, $data->cTile, $data->board);

	} else {

		echo '<div class="game-menu">';
			echo '<a href="/jouer" class="btn btn-lg '.(get_exists('show') ? 'color-game' : 'btn-game').'">'.s("Jouer").'</a>';
			echo '<a href="/jouer?show=story" class="btn btn-lg '.(GET('show') !== 'story' ? 'color-game' : 'btn-game').'">'.s("Synopsis").'</a>';
			echo '<a href="/jouer?show=rules" class="btn btn-lg '.(GET('show') !== 'rules' ? 'color-game' : 'btn-game').'">'.s("Règles du jeu").'</a>';
			if(\game\Player::isPremium($data->ePlayer['user']) === FALSE) {
				echo '<a href="/adherer" class="btn btn-lg btn-outline-game">'.s("Adhérer").'</a>';
			}
		echo '</div>';

		switch(GET('show')) {

			case 'story' :
				echo \game\HelpUi::getStory();
				break;

			case 'rules' :
				echo \game\HelpUi::getRules();
				echo \game\HelpUi::getCrops($data->cGrowing);
				break;

			default :

				echo new \game\DeskUi()->dashboard($data->ePlayer, $data->cGrowing, $data->cFood);

				echo new \game\DeskUi()->play($data->ePlayer, $data->cTile, $data->board);

				echo \game\HelpUi::getCrops($data->cGrowing);
				echo '<br/>';
				echo \game\HistoryUi::display($data->cHistory);

				break;

		}

	}

});

new AdaptativeView('new', function($data, PanelTemplate $t) {
	return new \game\PlayerUi()->create($data->e);
});

new AdaptativeView('planting', function($data, PanelTemplate $t) {
	return new \game\TileUi()->getPlanting($data->eTile);
});
?>
