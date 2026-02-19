<?php
new JsonView('getByUser', function($data, AjaxTemplate $t) {

	$t->qs('#planning-weekly-time')->innerHtml(new \series\PlanningUi()->getWeekTime($data->eFarm, $data->week, new Collection(), $data->eUserTime, $data->cUserFarm));

});
?>
