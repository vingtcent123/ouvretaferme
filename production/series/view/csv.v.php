<?php
new AdaptativeView('importCultivations', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->nav = 'settings-production';

	if(get_exists('created')) {

		echo '<div class="util-block-success">';
			echo '<p>'.s("Les séries contenues dans votre fichier CSV ont bien été ajoutées à votre planification !").'</p>';
			echo '<a href="'.\farm\FarmUi::urlCultivationSeries($data->eFarm).'" class="btn btn-transparent">'.s("Voir ma planification").'</a>';
		echo '</div>';

	}

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsProduction($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Importer un plan de culture");
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} un plan de culture en CSV en choisissant l'un des deux formats suivants :").'</p>';
		echo '<ul>';
			echo '<li>'.s("le format {siteName}").'</li>';
			echo '<li>'.s("le format Brinjel, qui permet d'importer vos séries depuis ce logiciel ou depuis Qrop").'</li>';
		echo '</ul>';
		echo '<a href="/doc/import:series" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo new \main\CsvUi()->getImportButton($data->eFarm, '/series/csv:doImportCultivations');

});

new AdaptativeView('importFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->nav = 'settings-production';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSettingsProduction($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= p("Votre fichier CSV contient {value} série", "Votre fichier CSV contient {value} séries", count($data->data['import']));
		$h .= '</h1>';
		$h .= '<a href="/series/csv:importCultivations?id='.$data->eFarm['id'].'&reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	$h .= '</div>';
	
	$t->mainTitle = $h;
	
	echo new \series\CsvUi()->getImportFile($data->eFarm, $data->data, $data->cAction);

});
?>
