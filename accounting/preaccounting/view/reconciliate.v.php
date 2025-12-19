<?php

new JsonView('doUpdatePaymentMethod', function($data, AjaxTemplate $t) {
	$t->js()->success('preaccounting', 'Suggestion::paymentMethod.updated');
});

?>
