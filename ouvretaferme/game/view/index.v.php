<?php
new AdaptativeView('start', function($data, GameTemplate $t) {

	$t->title = s("Sauvez Noël !");

	echo \game\HelpUi::getSummary();

	echo new \game\DeskUi()->get('<a href="/game/:new" class="game-intro-start">'.s("Commencer à jouer").'</a>', tile: 1	);

});

new AdaptativeView('/jouer', function($data, GameTemplate $t) {

	$t->title = s("Jouer !");

	echo '<h1 class="text-center mb-2">'.s("Des légumes pour les rennes 🦌").'</h1>';

	if($data->cTile->empty()) {
		echo '<div class="text-center mb-2" style="font-style: italic">';
			echo s("Votre partie est bien créée, {value} !", encode($data->ePlayer['name'])).'<br/>';
			echo s("Il ne vous reste qu'à lire les règles du jeu avant de commencer le travail.");
		echo '</div>';
		echo \game\HelpUi::getRules(TRUE);
	} else {
		echo '<div>';
			echo "synposys, regles, adhérer";
		echo '</div>';

		echo '<div class="game-dashboard util-block">';

			echo '<h3>'.encode($data->ePlayer['name']).'</h3>';

			echo '<div>';
				echo '<h4 class="game-dashboard-title">'.s("Temps de travail disponible").'</h4>';
				echo '<div class="game-dashboard-value">'.s("{value} <small> h</small>", $data->ePlayer['time']).'</div>';
				echo '<div class="game-dashboard-more">(retour à 8<small> h</small> dans XX minutes)</div>';
			echo '</div>';

			echo '<div>';
				echo '<h4>'.s("Production").'</h4>';
				echo '<div class="game-dashboard-value">XXX</div>';
			echo '</div>';

		echo '</div>';

	}

	$content = '';

	for($tile = 1; $tile <= 16; $tile++) {
		$content .= '<div class="game-tile game-tile-'.$tile.'">';
			$content .= '<a href="" class="game-tile-action">'.Asset::icon('plus-lg').'</a>';
		$content .= '</div>';
	}

	echo new \game\DeskUi()->get($content);

});

new AdaptativeView('new', function($data, PanelTemplate $t) {
	return new \game\PlayerUi()->create($data->e);
});
?>
