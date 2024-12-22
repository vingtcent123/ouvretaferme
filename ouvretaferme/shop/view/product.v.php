<?php
new AdaptativeView('createCollection', function($data, PanelTemplate $t) {
	return (new \shop\ProductUi())->create($data->e['farm'], $data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \shop\ProductUi())->update($data->e);

});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {

	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch('post-status', [\shop\Product::ACTIVE, \shop\Product::INACTIVE]);
	$t->qs('#product-available-'.$data->e['id'])->innerHtml((new \shop\ProductUi())->getStatus($data->e, TRUE));

});

?>