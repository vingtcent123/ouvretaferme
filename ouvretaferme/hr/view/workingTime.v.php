<?php
new JsonView('getByUser', function($data, AjaxTemplate $t) {

	$t->qs('#tasks-time')->innerHtml((new \series\TaskUi())->getWeekTime($data->eFarm, $data->week, new Collection(), $data->eUserTime, $data->cUserFarm));

});
?>
