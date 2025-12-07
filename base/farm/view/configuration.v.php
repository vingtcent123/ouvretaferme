<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Les réglages de base de {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base");
	$h .= '</h1>';
	
	$t->mainTitle = $h;

	if($data->eFarm->isTax()) {
		echo new \farm\ConfigurationUi()->update($data->e, $data->cCustomize, $data->eSaleExample, $data->cAccount);
	} else {
		echo new \farm\ConfigurationUi()->updateTax($data->eFarm);
	}

});
?>
