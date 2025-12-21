<?php

new AdaptativeView('reconciliate', function($data, PanelTemplate $t) {
	return new \preaccounting\ReconciliateUi()->reconciliate($data->eFarm, $data->cSuggestion);
});

new JsonView('doUpdatePaymentMethod', function($data, AjaxTemplate $t) {
	$t->js()->success('preaccounting', 'Suggestion::paymentMethod.updated');
});

?>
