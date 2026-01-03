<?php
new AdaptativeView('/configuration/accounting', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Paramétrer la comptabilité pour {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlSettingsAccounting($data->eFarm);

	$t->mainTitle = new \farm\FarmUi()->getSettingsTitle($data->eFarm, s("Paramétrer la comptabilité"), 'accounting').'</h1>';

	echo new \company\CompanyUi()->getSettings($data->eFarm);

});
?>
