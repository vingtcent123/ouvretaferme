<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Activité de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlAccount($data->eFarm).'/log';

	$t->mainTitle = new \account\LogUi()->getTitle($data->eFarm);

	echo new \account\LogUi()->getManage($data->eFarm, $data->cLog, $data->page, $data->nLog);

});
?>
