<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->title = s("Site internet de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm);

	if($data->eWebsite->empty()) {
		echo (new \website\ManageUi())->create($data->eFarm);
	} else {
		echo (new \website\ManageUi())->display($data->eWebsite);
		echo (new \website\ManageUi())->configure($data->eWebsite, $data->cWebpage, $data->cMenu, $data->cNews);
	}


});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \website\ManageUi())->update($data->e);

});
?>
