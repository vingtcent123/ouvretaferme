<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("L'équipe de {value}", $data->eFarm['name']);
	$t->tab = 'home';
	$t->subNav = (new \farm\FarmUi())->getPlanningSubNav($data->eFarm);

	echo (new \farm\FarmerUi())->manage($data->eFarm, $data->cFarmer, $data->cFarmerInvite, $data->cFarmerGhost);

});

new AdaptativeView('show', function($data, FarmTemplate $t) {

	$t->title = \user\UserUi::name($data->eFarmer['user']);
	$t->tab = 'home';
	$t->subNav = (new \farm\FarmUi())->getPlanningSubNav($data->eFarmer['farm']);

	echo (new \farm\FarmerUi())->showUser($data->eFarmer, $data->cPresence, $data->cAbsence);

});

new AdaptativeView('createUser', function($data, PanelTemplate $t) {
	return (new \farm\FarmerUi())->createUser($data->eFarm);
});

new AdaptativeView('updateUser', function($data, PanelTemplate $t) {
	return (new \farm\FarmerUi())->updateUser($data->eFarm, $data->eUserOnline);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \farm\FarmerUi())->create($data->e, $data->eFarmerLink);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \farm\FarmerUi())->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});
?>
