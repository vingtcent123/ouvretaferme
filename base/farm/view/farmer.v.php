<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Gérer l'équipe de la ferme");
	$t->nav = 'settings-commercialisation';

	$t->mainTitle = new \farm\FarmerUi()->getManageTitle($data->eFarm);

	echo new \farm\FarmerUi()->getManage($data->eFarm, $data->cFarmer, $data->cFarmerInvite, $data->cFarmerGhost);

});

new AdaptativeView('show', function($data, FarmTemplate $t) {

	$t->title = $data->eFarmer['user']->getName();
	$t->nav = 'settings-commercialisation';

	$t->mainTitle = new \farm\FarmerUi()->getUserTitle($data->eFarmer);

	echo new \farm\FarmerUi()->getUser($data->eFarmer, $data->cPresence, $data->cAbsence);

});

new AdaptativeView('createUser', function($data, PanelTemplate $t) {
	return new \farm\FarmerUi()->createUser($data->eFarm);
});

new AdaptativeView('updateUser', function($data, PanelTemplate $t) {
	return new \farm\FarmerUi()->updateUser($data->eFarm, $data->eUserOnline);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \farm\FarmerUi()->create($data->e, $data->eFarmerLink);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \farm\FarmerUi()->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});
?>
