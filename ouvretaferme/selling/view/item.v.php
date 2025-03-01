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

new AdaptativeView('summary', function($data, PanelTemplate $t) {
	return new \selling\ItemUi()->getByDeliverDay($data->eFarm, $data->date, $data->cSale, $data->ccItemProduct, $data->ccItemSale);
});

// TODO : refactoriser
new AdaptativeView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('.market-main')->innerHtml(new \selling\MarketUi()->displaySale($data->e['sale'], $data->cItemSale, $data->e['sale']['marketParent'], $data->cItemMarket));
	$t->qs('#market-sale-'.$data->e['sale']['id'].'-price')->innerHtml(\util\TextUi::money($data->e['sale']['priceIncludingVat'] ?? 0));

	$t->js()->success('selling', 'Item::deleted');

});

?>
