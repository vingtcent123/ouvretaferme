<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'assets';

	$t->title = s("Importer des immobilisations {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAsset($data->eFarm).'/csv';

	$mainTitle = '<h1>';
		$mainTitle .= '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/immobilisations"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$mainTitle .= s("Importer mes immobilisations");
	$mainTitle .= '</h1>';

	$t->mainTitle = $mainTitle;

	if(get_exists('created')) {

		echo '<div class="util-block-success">';
			echo '<p>'.s("Les immobilisations contenues dans votre fichier CSV ont bien été ajoutées à votre ferme !").'</p>';
			echo '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/immobilisations" class="btn btn-transparent">'.s("Voir toutes mes immobilisations").'</a>';
		echo '</div>';

	}

	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} un fichier CSV avec toutes vos immobilisations. Le fichier doit répondre à un format que vous pourrez trouver dans la documentation").'</p>';
		echo '<a href="/doc/accounting:asset#import" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo new \main\CsvUi()->getImportButton($data->eFarm, \company\CompanyUi::urlAsset($data->eFarm).'/csv:doImportAssets');

});

new AdaptativeView('importFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer mes immobilisations");

	$t->nav = 'accounting';
	$t->subNav = 'assets';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/immobilisations"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= p("Votre fichier CSV contient {value} immobilisation", "Votre fichier CSV contient {value} immobilisations", count($data->data['import']));
		$h .= '</h1>';
		$h .= '<a href="'.\company\CompanyUi::urlAsset($data->eFarm).'/csv?reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	$h .= '</div>';

	$t->mainTitle = $h;

	echo new \asset\CsvUi()->getImportFile($data->eFarm, $data->data);

});
