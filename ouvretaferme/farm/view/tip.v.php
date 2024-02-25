<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->title = s("Toutes les astuces");
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm, s("Astuces"));

});

new JsonView('close', function($data, AjaxTemplate $t) {
	$t->qs('#tip-wrapper')->remove(['mode' => 'fadeOut']);
});

?>