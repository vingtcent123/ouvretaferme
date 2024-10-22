<?php
new AdaptativeView('importCultivations', function($data, FarmTemplate $t) {

	$t->title = s("Importer un plan de culture");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	echo '<h1>'.s("Importer un plan de culture").'</h1>';
	echo '<div class="util-block-help">';
		echo '<h4>'.s("Comment importer un plan de culture ?").'</h4>';
		echo '<p>'.s("Importer un plan de culture revient à importer sur {siteName} un fichier CSV qui contient des listes de séries que vous souhaitez ajouter à votre plan de culture. Deux formats sont utilisables pour importer des séries :").'</p>';
		echo '<ul>';
			echo '<li>'.s("le format {siteName}").'</li>';
			echo '<li>'.s("le format Brinjel, qui permet d'importer vos séries depuis ce logiciel ou depuis Qrop").'</li>';
		echo '</ul>';
	echo '</div>';

	echo '<br/>';

	echo '<h2>'.s("Importer un fichier CSV au format {siteName}").'</h2>';
	echo '<div class="util-block-help">';
		echo '<p>'.s("Soyez attentifs au fait que les fichiers CSV au format {siteName} doivent respecter une nomenclature très précise pour que vos séries soient correctement importées. Si vous ne respectez pas le format, vous obtiendrez un résultat qui ne sera pas satisfaisant. <b>Nous vous conseillons de ne pas utiliser cette fonctionnalité si vous n'êtes pas à l'aise avec les tableurs.</b>").'</p>';
		echo '<p>';
			echo '<a href="/doc/import" class="btn btn-secondary">'.s("Voir la documentation du format CSV").'</a> ';
			echo '<a href="'.Asset::path('series', 'plan.csv').'" data-ajax-navigation="never" class="btn btn-secondary">'.s("Télécharger un exemple CSV").'</a>';
		echo '</p>';
	echo '</div>';
	echo (new \series\CsvUi())->getImportCultivations($data->eFarm);

	echo '<br/>';

	echo '<h2>'.s("Importer un fichier CSV depuis Qrop / Brinjel").'</h2>';
	echo '<div class="util-block-help">';
		echo '<p>'.s("Pour récupérer le fichier CSV de Brinjel à importer sur {siteName} :").'</p>';
		echo '<ul>';
			echo '<li>'.s("Allez dans <b>Paramètres</b>").'</li>';
			echo '<li>'.s("Dans la section <b>Données de la ferme</b>, téléchargez le <b>Plan de culture seul</b>").'</li>';
		echo '</ul>';
		echo '<p>'.s("Pour importer vos données depuis Qrop, vous devez d'abord importer vos données de Qrop vers Brinjel, puis ensuite utiliser le mode opératoire ci-dessus pour importer vos données de Brinjel vers {siteName}.").'</p>';
		echo '<p>'.s("Notez que vous aurez probablement des corrections à faire dans le fichier CSV issu de Brinjel, notamment au niveau des unités de récolte ou des espèces. {siteName} vous fera un rapport des modifications à effectuer après chargement de votre fichier.").'</p>';
		echo '<p>';
			echo '<a href="https://app.brinjel.com/" class="btn btn-secondary" target="_blank">'.s("Aller sur Brinjel").'</a> ';
		echo '</p>';
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
