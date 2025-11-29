<?php
new AdaptativeView('start', function($data, GameTemplate $t) {

	$t->title = s("Sauvez Noël !");

	echo '<h1>';
		echo s("Des légumes pour les rennes");
	echo '</h1>';

	echo new \game\HelpUi()->getStory();

	echo new \game\DeskUi()->get('<a href="'.($data->eUserOnline->empty() ? '/user/log:form?withSignUp=1' : '/game/:new').'" class="game-intro-start">'.s("Commencer à jouer").'</a>', 1);

});

new AdaptativeView('/jouer', function($data, GameTemplate $t) {

	$t->title = s("Jouer !");

	echo '<h1>';
		echo '<span class="hide-md-down">🎅 </span>';
		echo s("Des légumes pour les rennes");
		echo '<span class="hide-md-down"> 🦌</span>';
	echo '</h1>';

	if($data->ePlayer->isOnline() === FALSE) {

		echo '<h2 class="text-center">'.encode($data->ePlayer['name']).'</h2>';

		echo '<div class="game-menu">';
			echo '<a href="/jouer" class="btn btn-lg btn-outline-game">'.s("Revenir sur ma partie").'</a>';
		echo '</div>';

		echo new \game\DeskUi()->play($data->ePlayer, $data->board, $data->cTile, $data->cGrowing);

	} else if(get_exists('start')) {

		echo '<div class="text-center mb-3 mt-3 font-xl" style="font-style: italic">';
			echo s("Votre partie est bien créée, {value} !", encode($data->ePlayer['name'])).'<br/>';
			echo s("Il ne vous reste qu'à lire les règles du jeu avant de commencer le travail.");
		echo '</div>';

		echo new \game\HelpUi()->getRules($data->ePlayer, TRUE);

		echo new \game\DeskUi()->dashboard($data->ePlayer, $data->cGrowing, $data->cFood);
		echo new \game\DeskUi()->play($data->ePlayer, $data->board, $data->cTile, $data->cGrowing);
		echo new \game\DeskUi()->tabs($data->ePlayer, $data->cPlayerRanking, $data->cPlayerFriend, $data->cGrowing, $data->cFood, $data->cHistory);

	} else {

		echo '<div class="game-menu">';
			echo '<a href="/jouer" class="btn btn-lg '.(get_exists('show') ? 'color-game' : 'btn-game').'">'.s("Jouer").'</a>';
			echo '<a href="/jouer?show=story" class="btn btn-lg '.(GET('show') !== 'story' ? 'color-game' : 'btn-game').'">'.s("Synopsis").'</a>';
			echo '<a href="/jouer?show=rules" class="btn btn-lg '.(GET('show') !== 'rules' ? 'color-game' : 'btn-game').'">'.s("Règles du jeu").'</a>';
			if($data->ePlayer->isPremium() === FALSE) {
				echo match($data->ePlayer->getRole()) {
					'farmer' => '<a href="/adherer" class="btn btn-lg btn-outline-game">'.s("Adhérer").'</a>',
					'customer' => '<a href="/donner" class="btn btn-lg btn-outline-game">'.s("Faire un don").'</a>',
				};
			}
		echo '</div>';

		switch(GET('show')) {

			case 'story' :
				echo new \game\HelpUi()->getStory();
				break;

			case 'rules' :
				echo new \game\HelpUi()->getRules($data->ePlayer);
				echo '<div class="game-intro">';
					echo '<h3>'.s("Tableau des cultures").'</h3>';
					echo new \game\HelpUi()->getCrops($data->cGrowing);
				echo '</div>';
				break;

			default :

				echo new \game\DeskUi()->dashboard($data->ePlayer, $data->cGrowing, $data->cFood);

				foreach(\game\GameSetting::BOARDS_OPENING as $board => $date) {

					if($date === currentDate()) {
						echo '<div class="game-intro text-center mt-2 font-lg" style="font-weight: bold">🎉 '.s("Vous pouvez maintenant jouer avec le plateau {value}", \Asset::icon($board.'-circle-fill')).' 🥳</div>';
					}

				}

				echo new \game\DeskUi()->play($data->ePlayer, $data->board, $data->cTile, $data->cGrowing);
				echo new \game\DeskUi()->tabs($data->ePlayer, $data->cPlayerRanking, $data->cPlayerFriend, $data->cGrowing, $data->cFood, $data->cHistory);

				break;

		}

	}

});

new AdaptativeView('new', function($data, PanelTemplate $t) {
	return new \game\PlayerUi()->create($data->e);
});
?>
