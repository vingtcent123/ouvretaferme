<?php
new AdaptativeView('/produit/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Produit {value}", encode($data->e['name']));

	$t->tab = 'selling';
	$t->subNav = (new \farm\FarmUi())->getSellingSubNav($data->eFarm);

	echo (new \selling\ProductUi())->display($data->e, $data->cItemTurnover);
	echo (new \selling\ProductUi())->getTabs($data->e, $data->eFarm, $data->cGrid, $data->cItemLast);

});

new AdaptativeView('analyze', function($data, PanelTemplate $t) {
	return (new \selling\AnalyzeUi())->getProduct($data->e, $data->year, $data->cItemTurnover, $data->cItemCustomer, $data->cItemType, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore, $data->search);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \selling\ProductUi())->create($data->eFarm);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \selling\ProductUi())->update($data->e);
});

new AdaptativeView('updateGrid', function($data, PanelTemplate $t) {
	return (new \selling\GridUi())->updateByProduct($data->e, $data->cCustomer);
});

new JsonView('doUpdateGrid', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch();
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cProduct->makeArray(fn($eCustomer) => \selling\ProductUi::getAutocomplete($eCustomer));
	$t->push('results', $results);

});
?>
