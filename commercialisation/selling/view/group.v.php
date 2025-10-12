<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cGroup->makeArray(fn($eGroup) => \selling\GroupUi::getAutocomplete($eGroup));
	$results[] = \selling\GroupUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les groupes de clients de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlSellingCustomersGroups($data->eFarm);

	$t->nav = 'selling';
	$t->subNav = 'customer';
	$t->subNavTarget = $t->canonical;

	$t->mainTitle = new \farm\FarmUi()->getSellingCustomersTitle($data->eFarm, \farm\Farmer::GROUP);

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
