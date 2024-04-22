<?php
new AdaptativeView('/ferme/{farm}/boutique/{shop}/date/{date}/product:create', function($data, PanelTemplate $t) {

	return (new \shop\ProductUi())->create($data->eFarm, $data->eDate, $data->cProduct);

});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch();
});

?>