<?php
new AdaptativeView('importCultivations', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	if(get_exists('created')) {

		echo '<div class="util-success">';
			echo '<p>'.s("Les séries contenues dans votre fichier CSV ont bien été ajoutées à votre planification !").'</p>';
			echo '<a href="'.\farm\FarmUi::urlCultivation($data->eFarm, \farm\Farmer::SERIES).'" class="btn btn-success">'.s("Voir ma planification").'</a>';
		echo '</div>';

	}

	echo '<h1>';
		echo '<a href="'.\farm\FarmUi::urlSettings($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		echo s("Importer un plan de culture");
	echo '</h1>';
	echo '<br/>';
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} un plan de culture en CSV en choisissant l'un des deux formats suivants :").'</p>';
		echo '<ul>';
			echo '<li>'.s("le format {siteName}").'</li>';
			echo '<li>'.s("le format Brinjel, qui permet d'importer vos séries depuis ce logiciel ou depuis Qrop").'</li>';
		echo '</ul>';
		echo '<a href="/doc/import" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo (new \series\CsvUi())->getImportCultivations($data->eFarm);

});

new AdaptativeView('importFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	echo '<div class="util-action">';
		echo '<h1>'.p("Votre fichier CSV contient {value} série", "Votre fichier CSV contient {value} séries", count($data->data['import'])).'</h1>';
		echo '<a href="/series/csv:importCultivations?id='.$data->eFarm['id'].'&reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	echo '</div>';
	echo (new \series\CsvUi())->getImportFile($data->eFarm, $data->data, $data->cAction);

});
?>
