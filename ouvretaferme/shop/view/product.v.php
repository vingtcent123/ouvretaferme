<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \shop\ProductUi())->create($data->e['farm'], $data->e);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch();
});

?>