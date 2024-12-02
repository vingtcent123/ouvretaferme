<?php
new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les catégories de produits de {value}", $data->eFarm['name']);
	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	$t->mainTitle = (new \selling\CategoryUi())->getManageTitle($data->eFarm, $data->cCategory);

	echo (new \selling\CategoryUi())->getManage($data->eFarm, $data->cCategory);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \selling\CategoryUi())->create($data->eFarm);

});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->js()->success('selling', 'category::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \selling\CategoryUi())->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->success('selling', 'Category::updated');
	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('selling', 'Category::deleted');
	$t->ajaxReloadLayer();

});
?>
