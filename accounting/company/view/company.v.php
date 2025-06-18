<?php
new AdaptativeView('configuration', function($data, CompanyTemplate $t) {

	$t->tab = 'settings';
	$t->subNav = (new \company\CompanyUi())->getSettingsSubNav($data->eCompany);

	$t->title = s("Configuration pour {value}", $data->eCompany['name']);
	$t->canonical = \company\CompanyUi::urlSettings($data->eCompany);

	$t->package('main')->updateNavSettings($t->canonical);

	$t->mainTitle = '<h1>'.s("Paramétrage").'</h1>';
	$t->mainTitleClass = 'hide-lateral-down';

	echo (new \company\CompanyUi())->getSettings($data->eCompany);

});

new AdaptativeView('update', function($data, CompanyTemplate $t) {

	$t->title = s("Réglages de base de {value}", $data->e['name']);
	$t->tab = 'settings';
	$t->subNav = (new \company\CompanyUi())->getSettingsSubNav($data->e);

	$h = '<h1>';
		$h .= '<a href="'.\company\CompanyUi::urlSettings($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base de la ferme");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo (new \company\CompanyUi())->update($data->e);

});
?>
