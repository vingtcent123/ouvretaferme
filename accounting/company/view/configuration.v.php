<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Les réglages de comptabilité pour {value}", $data->eFarm['name']);


	$h = '<h1>';
		$h .= '<a href="'.\farm\FarmUi::urlSettingsAccounting($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de comptabilité");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \company\CompanyUi()->getSettings($data->eFarm);

});
?>
