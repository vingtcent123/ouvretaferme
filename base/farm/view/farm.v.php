<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->create();

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \farm\FarmUi()->update($data->e);

});

new AdaptativeView('updateProduction', function($data, FarmTemplate $t) {

	$t->title = s("Les réglages de base de {value}", $data->e['name']);
	$t->nav = 'settings-production';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsProduction($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\FarmUi()->updateProduction($data->e);

});

new AdaptativeView('updateEmail', function($data, FarmTemplate $t) {

	$t->title = s("Les e-mails envoyés par {value}", $data->e['name']);
	$t->nav = 'settings-commercialisation';

	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsCommercialisation($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("E-mails");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \farm\FarmUi()->updateEmail($data->e);

});

new AdaptativeView('calendarMonth', function($data, AjaxTemplate $t) {

	$t->qs('#farm-update-calendar-month')->innerHtml(new \series\CultivationUi()->getListSeason($data->e, date('Y')));

});

new AdaptativeView('export', function($data, PanelTemplate $t) {
	
	return new \farm\FarmUi()->export($data->e, $data->year);

});
?>
