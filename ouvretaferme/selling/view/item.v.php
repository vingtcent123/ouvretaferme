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
?>
