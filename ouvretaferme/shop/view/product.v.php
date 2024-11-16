<?php
new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#product-switch-'.$data->e['id'])->toggleSwitch();
});

?>