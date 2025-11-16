<?php
new AdaptativeView('start', function($data, GameTemplate $t) {

	$t->title = s("Sauvez Noël !");

	echo \game\HelpUi::getStory();

	echo new \game\DeskUi()->get('<a href="/game/:new" class="game-intro-start">'.s("Commencer à jouer").'</a>', 1);

});

new AdaptativeView('/jouer', function($data, GameTemplate $t) {

	$t->title = s("Jouer !");

	echo '<h1 class="text-center mb-2">'.s("Des légumes pour les rennes 🦌").'</h1>';

	if(get_exists('start')) {

		echo '<div class="text-center mb-2" style="font-style: italic">';
			echo s("Votre partie est bien créée, {value} !", encode($data->ePlayer['name'])).'<br/>';
			echo s("Il ne vous reste qu'à lire les règles du jeu avant de commencer le travail.");
		echo '</div>';

		echo \game\HelpUi::getRules(TRUE);

		echo new \game\DeskUi()->play(1);

	} else {

		echo '<div class="game-menu">';
			echo '<div class="input-group">';
				echo '<a href="/jouer" class="btn btn-lg btn-'.(get_exists('show') ? 'outline-' : '').'game">'.s("Jouer").'</a>';
				echo '<a href="/jouer?show=story" class="btn btn-lg btn-'.(GET('show') !== 'story' ? 'outline-' : '').'game">'.s("Synopsis").'</a>';
				echo '<a href="/jouer?show=rules" class="btn btn-lg btn-'.(GET('show') !== 'rules' ? 'outline-' : '').'game">'.s("Règles").'</a>';
				if($data->ePlayer->isPremium() === FALSE) {
					echo '<a href="/adherer" class="btn btn-lg btn-outline-game">'.s("Adhérer").'</a>';
				}
			echo '</div>';
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

				echo '<div class="game-dashboard util-block">';

					echo '<h3>'.encode($data->ePlayer['name']).'</h3>';

					echo '<div>';
						echo '<h4 class="game-dashboard-title">'.s("Temps de travail disponible").'</h4>';
						echo '<div class="game-dashboard-value">'.s("{value} h", $data->ePlayer['time']).'</div>';
						echo '<div class="game-dashboard-more">(retour à 8 h dans XX minutes)</div>';
					echo '</div>';

					echo '<div>';
						echo '<h4>'.s("Production").'</h4>';
						echo '<div class="game-dashboard-value">XXX</div>';
					echo '</div>';

				echo '</div>';

				echo new \game\DeskUi()->play(1);

				echo \game\HelpUi::getCrops($data->cGrowing);

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
