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
?>
