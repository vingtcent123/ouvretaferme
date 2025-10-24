<?php
new AdaptativeView('select', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->selectBySale($data->eSale);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->createBySale($data->eSale, $data->eItem);
});

new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->createCollectionBySale($data->eSale['farm'], $data->eSale);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->update($data->e);
});

new JsonView('doUpdatePrepared', function($data, AjaxTemplate $t) {
	$t->qs('#item-prepared-switch-'.$data->e['id'])->toggleSwitch('post-prepared', [TRUE, FALSE]);
	$t->qs('#item-count')->innerHtml($data->remaining);
});

new AdaptativeView('summary', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->getSummary($data->eFarm, $data->date, $data->cSale, $data->ccItemProduct, $data->ccItemSale, $data->cPaymentMethod);
});

new AdaptativeView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('.market-main')->innerHtml(new \selling\MarketUi()->displaySale($data->eFarmer, $data->e['sale'], $data->cItemSale, $data->e['sale']['marketParent'], $data->cItemMarket, $data->cPaymentMethod));
	$t->qs('#market-sale-'.$data->e['sale']['id'].'-price')->innerHtml(\util\TextUi::money($data->e['sale']['priceIncludingVat'] ?? 0));

	$t->js()->success('selling', 'Item::deleted');

});

?>
