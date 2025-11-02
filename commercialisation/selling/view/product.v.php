<?php
new AdaptativeView('/produit/{id}', function($data, FarmTemplate $t) {

	$t->title = s("Produit {value}", encode($data->e['name']));

	$t->nav = 'selling';
	$t->subNav = 'product';

	$t->mainTitle = new \selling\ProductUi()->displayTitle($data->e, $data->switchComposition);

	if($data->e['status'] === \selling\Product::DELETED) {
		echo '<div class="util-danger mb-1">'.s("Ce produit a été supprimé et n'est plus disponible.").'</div>';
	} else {
		echo new \selling\ProductUi()->display($data->e);
		echo new \selling\ProductUi()->getAnalyze($data->e, $data->cItemYear);
		echo new \selling\ProductUi()->getTabs($data->e, $data->cSaleComposition, $data->cGrid, $data->cItemLast);
	}

});

new AdaptativeView('analyze', function($data, PanelTemplate $t) {
	return new \selling\AnalyzeUi()->getProduct($data->e, $data->switchComposition, $data->year, $data->cItemYear, $data->cItemCustomer, $data->cItemType, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore, $data->search);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\ProductUi()->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\ProductUi()->update($data->e);
});


new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch('post-status', [\selling\Product::ACTIVE, \selling\Product::INACTIVE]);
});

new JsonView('query', function($data, AjaxTemplate $t) {

	$results = $data->cProduct->makeArray(fn($eCustomer) => \selling\ProductUi::getAutocomplete($eCustomer));
	$t->push('results', $results);

});
?>
