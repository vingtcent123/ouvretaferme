<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->title = s("Site internet de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = new \farm\FarmUi()->getSettingsSubNav($data->eFarm);

	if($data->eWebsite->empty()) {

		$t->mainTitle = '<h1>'.s("Créer le site internet de la ferme").'</h1>';

		echo new \website\ManageUi()->create($data->eFarm);

	} else {

		$t->mainTitle = new \website\ManageUi()->displayTitle($data->eWebsite);

		echo new \website\ManageUi()->display($data->eWebsite);
		echo new \website\ManageUi()->configure($data->eWebsite, $data->cWebpage, $data->cMenu, $data->cNews);
	}


});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \website\ManageUi()->update($data->e);

});

new AdaptativeView('contact', function($data, PanelTemplate $t) {

	return new \website\ManageUi()->contact($data->e);

});
?>
