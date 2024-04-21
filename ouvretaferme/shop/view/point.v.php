<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {
	return (new \shop\PointUi())->create($data->e);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \shop\PointUi())->update($data->e);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->qs('#point-switch-'.$data->e['id'])->toggleSwitch();
});
?>
