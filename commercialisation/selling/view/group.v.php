<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cGroup->makeArray(fn($eGroup) => \selling\GroupUi::getAutocomplete($eGroup));
	$results[] = \selling\GroupUi::getAutocompleteCreate($data->eFarm);

	$t->push('results', $results);

});

new AdaptativeView('get', function($data, FarmTemplate $t) {

	$t->nav = 'selling';
	$t->subNav = 'customer';

	$t->title = encode($data->e['name']);

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSellingCustomersGroups($data->e['farm']).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= $t->title;
		$h .= '</h1>';
		$h .= new \selling\GroupUi()->getMenu($data->e, 'btn-primary');
	$h .= '</div>';

	$t->mainTitle = $h;

	echo new \selling\GroupUi()->getOne($data->e, $data->cCustomer, $data->cGrid, $data->cGroup);
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
