<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {

	if(get_exists('user')) {
		$t->js()->replaceHistory(LIME_URL);
	}

	return (new \series\TimesheetUi())->update($data->eFarm, $data->cTask, $data->cUser, $data->eUserSelected, $data->eTimesheet);

});

new JsonView('update', function($data, AjaxTemplate $t) {

	$t->ajaxReloadLayer();
	$t->ajaxReload(purgeLayers: FALSE);

});
?>
