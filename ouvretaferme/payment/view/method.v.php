<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les moyens de paiement de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = new \farm\FarmUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \payment\MethodUi()->getManageTitle($data->eFarm);
	echo new \payment\MethodUi()->getManage($data->cMethod);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \payment\MethodUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \payment\MethodUi()->update($data->e);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->js()->success('payment', 'Method::updated'.ucfirst($data->e['status']));
	$t->qs('#method-switch-'.$data->e['id'])->toggleSwitch('post-status', [\payment\Method::ACTIVE, \payment\Method::INACTIVE]);
});
?>
