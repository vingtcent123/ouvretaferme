<?php
new AdaptativeView('importProducts', function($data, FarmTemplate $t) {

	$t->title = s("Importer des produits");
	$t->nav = 'settings-commercialisation';

	if(get_exists('created')) {

		echo '<div class="util-block-success">';
			echo '<p>'.s("Les produits contenus dans votre fichier CSV ont bien été ajoutées à votre gamme !").'</p>';
			echo '<a href="'.\farm\FarmUi::urlSellingProducts($data->eFarm).'" class="btn btn-transparent">'.s("Voir mes produits").'</a>';
		echo '</div>';

	}

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Importer des produits");
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} votre gamme de produits au format CSV.<br/>Le fichier que vous importez doit respecter un format qui est décrit dans la documentation.").'</p>';
		echo '<a href="/doc/import:products" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo new \main\CsvUi()->getImportButton($data->eFarm, '/selling/csv:doImportProducts');

});

new AdaptativeView('importProductFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer des produits");
	$t->nav = 'settings-commercialisation';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= p("Votre fichier CSV contient {value} produit", "Votre fichier CSV contient {value} produits", count($data->data['import']));
		$h .= '</h1>';
		$h .= '<a href="/selling/csv:importProducts?id='.$data->eFarm['id'].'&reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	$h .= '</div>';
	
	$t->mainTitle = $h;
	
	echo new \selling\CsvUi()->getProducts($data->eFarm, $data->data);

});

new AdaptativeView('importCustomers', function($data, FarmTemplate $t) {

	$t->title = s("Importer des clients");
	$t->nav = 'settings-commercialisation';

	if(get_exists('created')) {

		echo '<div class="util-block-success">';
			echo '<p>'.s("Les clients contenus dans votre fichier CSV ont bien été ajoutées à votre gamme !").'</p>';
			echo '<a href="'.\farm\FarmUi::urlSellingCustomers($data->eFarm).'" class="btn btn-transparent">'.s("Voir mes clients").'</a>';
		echo '</div>';

	}

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Importer des clients");
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} vos clients au format CSV.<br/>Le fichier que vous importez doit respecter un format qui est décrit dans la documentation.").'</p>';
		echo '<a href="/doc/import:customers" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo new \main\CsvUi()->getImportButton($data->eFarm, '/selling/csv:doImportCustomers');

});

new AdaptativeView('importCustomerFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer des clients");
	$t->nav = 'settings-commercialisation';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= p("Votre fichier CSV contient {value} client", "Votre fichier CSV contient {value} clients", count($data->data['import']));
		$h .= '</h1>';
		$h .= '<a href="/selling/csv:importCustomers?id='.$data->eFarm['id'].'&reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	$h .= '</div>';
	
	$t->mainTitle = $h;
	
	echo new \selling\CsvUi()->getCustomers($data->eFarm, $data->data);

});

new AdaptativeView('importPrices', function($data, FarmTemplate $t) {

	$t->title = s("Importer des prix");
	$t->nav = 'settings-commercialisation';

	if(get_exists('created')) {

		echo '<div class="util-block-success">';
			echo '<p>'.s("Les prix contenus dans votre fichier CSV ont bien été appliqués !").'</p>';
		echo '</div>';

	}

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Importer des prix");
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo '<div class="util-block-help">';
		echo '<p>'.s("Vous pouvez importer sur {siteName} vos prix au format CSV.<br/>Le fichier que vous importez doit respecter un format qui est décrit dans la documentation.").'</p>';
		echo '<a href="/doc/import:prices" class="btn btn-secondary">'.Asset::icon('person-raised-hand').' '.s("Voir la documentation").'</a>';
	echo '</div>';

	echo new \main\CsvUi()->getImportButton($data->eFarm, '/selling/csv:doImportPrices');

});

new AdaptativeView('importPriceFile', function($data, FarmTemplate $t) {

	$t->title = s("Importer des prix");
	$t->nav = 'settings-commercialisation';

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= p("Votre fichier CSV contient {value} prix", "Votre fichier CSV contient {value} prix", count($data->data['import']));
		$h .= '</h1>';
		$h .= '<a href="/selling/csv:importPrices?id='.$data->eFarm['id'].'&reset" class="btn btn-primary">'.s("Téléverser un autre fichier").'</a>';
	$h .= '</div>';
	
	$t->mainTitle = $h;
	
	echo new \selling\CsvUi()->getPrices($data->eFarm, $data->data);

});
?>
