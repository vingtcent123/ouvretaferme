<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Commercialisation de {value}", $data->e['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->e);


	echo '<h1>';
		echo '<a href="'.\farm\FarmUi::urlSettings($data->e).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		echo s("Réglages de base pour vendre");
	echo '</h1>';
	echo '<br/>';
	echo (new \selling\ConfigurationUi())->update($data->e, $data->cCustomize, $data->eSaleExample);

});
?>
