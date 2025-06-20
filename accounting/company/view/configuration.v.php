<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->tab = 'settings';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->title = s("Configuration pour {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlSettings($data->eFarm);

	$t->package('main')->updateNavSettings($t->canonical);

	$t->mainTitle = '<h1>'.s("Paramétrage").'</h1>';
	$t->mainTitleClass = 'hide-lateral-down';

	$t->hasCRM = TRUE;

	echo new \company\CompanyUi()->getSettings($data->eFarm);

});
?>
