<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Réglages de base de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlSettingsAccounting($data->eFarm);

	$t->mainTitle = new \farm\FarmUi()->getSettingsTitle($data->eFarm, s("Paramétrer la comptabilité"), 'accounting').'</h1>';

	$h = '<h1>';
		$h .= '<a href="'.\company\CompanyUi::urlSettings($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		$h .= s("Réglages de base");
	$h .= '</h1>';

	$t->mainTitle = $h;

	echo new \company\CompanyUi()->update($data->eFarm);

	echo new \account\DropboxUi()->updateConnection($data->eFarm, $data->partners['dropbox']);

});
?>
