<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cGroup->makeArray(fn($eGroup) => \selling\GroupUi::getAutocomplete($eGroup));
	$t->push('results', $results);

});

new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les catégories de produits de {value}", $data->eFarm['name']);
	$t->nav = 'selling';
	$t->subNav = 'product';

	$t->mainTitle = new \selling\GroupUi()->getManageTitle($data->eFarm, $data->cGroup);

	echo new \selling\GroupUi()->getManage($data->eFarm, $data->cGroup);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \selling\GroupUi()->create($data->eFarm);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('selling', 'Group::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \selling\GroupUi()->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('selling', 'Group::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('selling', 'Group::deleted');
	$t->ajaxReloadLayer();

});
?>
