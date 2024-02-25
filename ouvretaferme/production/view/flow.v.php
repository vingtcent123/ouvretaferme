<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \production\FlowUi())->create($data->eSequence, $data->cAction);

});

new AdaptativeView('getToolsField', function($data, AjaxTemplate $t) {

	if($data->cToolAvailable->empty()) {
		$t->push('field', '');
	} else {
		$t->push('field', (new \production\FlowUi())->getToolsField(new \util\FormUi(), $data->eFlow));
	}


});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \production\FlowUi())->update($data->eSequence, $data->e, $data->cAction, $data->cToolAvailable);

});

new AdaptativeView('incrementWeekCollection', function($data, PanelTemplate $t) {
	return (new \production\FlowUi())->updateIncrementWeekCollection($data->c);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->js()->moveHistory(-1);
	$t->qs('#flow-wrapper')->outerHtml((new \production\FlowUi())->getTimeline($data->eSequence, $data->events, TRUE));

});

new JsonView(['doPosition', 'doIncrementWeek', 'doDelete'], function($data, AjaxTemplate $t) {

	$t->qs('#flow-wrapper')->outerHtml((new \production\FlowUi())->getTimeline($data->eSequence, $data->events, TRUE));

});
?>
