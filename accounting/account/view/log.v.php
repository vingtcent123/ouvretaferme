<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Activité comptable de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/log';

	$t->mainTitle = new \account\LogUi()->getTitle($data->eFarm);

	echo new \account\LogUi()->list($data->eFarm, $data->cLog, $data->page, $data->nLog);

});
?>
