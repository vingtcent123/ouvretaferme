<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return new \selling\PaymentLinkUi()->create($data->eElement, $data->cPaymentLink);

});

new AdaptativeView('doCreate', function($data, AjaxTemplate $t) {

	$t->ajaxReload();
	$t->js()->success('selling', 'PaymentLink::created', [
		'actions' => new \selling\PaymentLinkUi()->getSuccessActions($data->e)
	]);

});
