<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Commercialisation de {value}", $data->e['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->e);


	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettings($data->e).'"  class="h-button">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base pour vendre");
	$h .= '</h1>';
	
	$t->mainTitle = $h;
	
	echo (new \selling\ConfigurationUi())->update($data->e, $data->cCustomize, $data->eSaleExample);

});
?>
