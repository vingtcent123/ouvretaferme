<?php
new AdaptativeView('importCultivations', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	echo '<h1>'.s("Importer un plan de culture").'</h1>';
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} un plan de culture en CSV de l'un des deux formats suivants :").'</p>';
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

	echo '<h1>'.s("Importer un plan de culture").'</h1>';
	echo (new \series\CsvUi())->getImportFile($data->eFarm, $data->data, $data->cAction);

});
?>
