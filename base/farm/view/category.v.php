<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les catégories d'interventions de {value}", $data->eFarm['name']);
	$t->nav = 'settings-production';

	$t->mainTitle = new \farm\CategoryUi()->getManageTitle($data->eFarm, $data->cCategory);
	echo new \farm\CategoryUi()->getManage($data->eFarm, $data->cCategory);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \farm\CategoryUi()->create($data->eFarm);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('farm', 'Category::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \farm\CategoryUi()->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Category::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Category::deleted');
	$t->ajaxReloadLayer();

});
?>
