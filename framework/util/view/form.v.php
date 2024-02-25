<?php
new JsonView('weekChange', function($data, AjaxTemplate $t) {
	$t->qs('#'.$data->id)->outerHtml(\util\FormUi::weekSelector(
		$data->year, $data->linkWeeks, $data->linkMonths,
		onclickWeeks: $data->onclickWeeks,
		defaultWeek: $data->default
	));
});
?>
