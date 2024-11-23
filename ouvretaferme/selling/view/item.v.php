<?php
new AdaptativeView('add', function($data, PanelTemplate $t) {

	return (new \selling\ItemUi())->add($data->e);

});

new JsonView('one', function($data, AjaxTemplate $t) {

	$t->qs('#item-add-list')->insertAdjacentHtml('afterbegin', \selling\ItemUi::addOne($data->eItem, $data->eGrid));
	$t->package('selling')->activateLastItem();

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \selling\ItemUi())->update($data->e);
});

new AdaptativeView('getDeliveredAt', function($data, PanelTemplate $t) {
	return (new \selling\ItemUi())->getByDeliverDay($data->eFarm, $data->date, $data->cSale, $data->ccItemProduct, $data->ccItemSale);
});

// TODO : refactoriser
new AdaptativeView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('.market-main')->innerHtml((new \selling\MarketUi())->displaySale($data->e['sale'], $data->cItemSale, $data->e['sale']['marketParent'], $data->cItemMarket));
	$t->qs('#market-sale-'.$data->e['sale']['id'].'-price')->innerHtml(\util\TextUi::money($data->e['sale']['priceIncludingVat'] ?? 0));

	$t->js()->success('selling', 'Item::deleted');

});

?>
