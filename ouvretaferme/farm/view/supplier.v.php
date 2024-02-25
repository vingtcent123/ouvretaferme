<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cSupplier->makeArray(fn($eSupplier) => \farm\SupplierUi::getAutocomplete($eSupplier));
	$t->push('results', $results);

});

new AdaptativeView('manage', function($data, FarmTemplate $t) {

	$t->title = s("Les fournisseurs de {value}", $data->eFarm['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->eFarm, s("Fournisseurs de semences et plants"));

	echo (new \farm\SupplierUi())->manage($data->eFarm, $data->cSupplier, $data->search);

});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \farm\SupplierUi())->create($data->e);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('farm', 'Supplier::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \farm\SupplierUi())->update($data->e);

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('farm', 'Supplier::deleted');
	$t->ajaxReloadLayer();

});
?>
