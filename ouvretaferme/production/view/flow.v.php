<?php
new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new \production\FlowUi())->create($data->e, $data->cAction);

});

new AdaptativeView('getFields', function($data, AjaxTemplate $t) {

	if($data->eFlow['hasTools']->empty()) {
		$t->push('tools', '');
	} else {
		$t->push('tools', (new \util\FormUi())->dynamicGroup($data->eFlow, 'toolsList'));
	}

	if($data->eFlow['cMethod']->empty()) {
		$t->push('methods', '');
	} else {
		$t->push('methods', (new \util\FormUi())->dynamicGroup($data->eFlow, 'method'));
	}


});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \production\FlowUi())->update($data->eSequence, $data->e, $data->cAction);

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
