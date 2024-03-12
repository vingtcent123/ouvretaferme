<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \farm\FarmUi())->create();

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \farm\FarmUi())->update($data->e);

});

new AdaptativeView('updateSeries', function($data, PanelTemplate $t) {

	return (new \farm\FarmUi())->updateSeries($data->e);

});

new AdaptativeView('updateFeature', function($data, PanelTemplate $t) {

	return (new \farm\FarmUi())->updateFeature($data->e);

});

new AdaptativeView('calendarMonth', function($data, AjaxTemplate $t) {

	$t->qs('#farm-update-calendar-month')->innerHtml((new \series\CultivationUi())->getListSeason($data->e, date('Y')));

});

new AdaptativeView('export', function($data, FarmTemplate $t) {

	$t->title = s("Exporter les données de {value}", $data->e['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->e);

	echo '<h1>'.s("Exporter les données").'</h1>';
	echo (new \farm\FarmUi())->export($data->e, $data->year);

});
?>
