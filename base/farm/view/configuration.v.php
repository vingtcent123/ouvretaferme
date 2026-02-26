<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Les réglages de base de {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base");
	$h .= '</h1>';
	
	$t->mainTitle = $h;

	if($data->eFarm->isVerified()) {
		echo new \farm\ConfigurationUi()->update($data->e, $data->cAccount);
	} else {
		echo new \farm\FarmUi()->getLegalForm($data->eFarm, onlyCountry: TRUE);
	}

});

new AdaptativeView('updateOrderForm', function($data, FarmTemplate $t) {

	$t->title = s("Les devis de {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Devis");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\ConfigurationUi()->updateOrderForm($data->e, $data->eSaleExample, $data->cCustomize);

});

new AdaptativeView('updateDeliveryNote', function($data, FarmTemplate $t) {

	$t->title = s("Les bons de livraison de {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Bons de livraison");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\ConfigurationUi()->updateDeliveryNote($data->e, $data->eSaleExample, $data->cCustomize);

});

new AdaptativeView('updateInvoice', function($data, FarmTemplate $t) {

	$t->title = s("Les factures de {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Factures");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\ConfigurationUi()->updateInvoice($data->e, $data->eSaleExample, $data->cCustomize);

});

new AdaptativeView('updateVat', function($data, FarmTemplate $t) {

	$t->title = s("Le paramétrage de TVA de {value}", $data->e['name']);
	$t->nav = 'settings-accounting';

	$h = '<h1>';
		$h .= '<a href="javascript: history.back()"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Paramétrage de la TVA");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\ConfigurationUi()->updateVat($data->e);

});

new AdaptativeView('updateVatNumber', function($data, PanelTemplate $t) {

	return new \farm\ConfigurationUi()->updateVatNumberPanel($data->eFarm);

});

new AdaptativeView('updateInvoiceMentions', function($data, PanelTemplate $t) {

	return new \farm\ConfigurationUi()->updateInvoiceMention($data->eFarm);

});
?>
